<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Assessment\CreateApplicationRequest;
use App\Mail\AssessmentLinkMail;
use App\Models\AssessmentApplication;
use App\Models\AssessmentResult;
use App\Models\AssessmentTest;
use App\Services\Assessment\AssessmentScoringService;
use App\Traits\ScopesByClient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AssessmentController extends Controller
{
    use ScopesByClient;

    public function __construct(
        private readonly AssessmentScoringService $scoringService
    ) {}

    // =========================================================================
    // Catálogo de testes
    // =========================================================================

    /**
     * GET /api/assessments
     * Lista todos os instrumentos ativos.
     */
    public function index(): JsonResponse
    {
        try {
            $tests = AssessmentTest::active()
                ->with('dimensions:id,assessment_test_id,slug,name,description,weight,order')
                ->orderBy('name')
                ->get(['id', 'slug', 'name', 'description', 'type', 'version', 'disclaimer']);

            return response()->json(['success' => true, 'data' => $tests]);
        } catch (\Exception $e) {
            Log::error('Assessment::index ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao listar instrumentos.'], 500);
        }
    }

    /**
     * GET /api/assessments/{slug}
     * Detalhe de um instrumento com dimensões e perguntas (sem gabarito SJT).
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $test = AssessmentTest::where('slug', $slug)
                ->with([
                    'dimensions:id,assessment_test_id,slug,name,description,weight,order',
                    'questions' => fn($q) => $q->with([
                        'options:id,assessment_question_id,label,text,order', // score oculto
                    ])->orderBy('order'),
                ])
                ->firstOrFail();

            return response()->json(['success' => true, 'data' => $test]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Instrumento não encontrado.'], 404);
        }
    }

    // =========================================================================
    // Aplicações
    // =========================================================================

    /**
     * GET /api/assessments/applications
     * Lista aplicações visíveis para o usuário autenticado.
     */
    public function listApplications(Request $request): JsonResponse
    {
        try {
            $query = AssessmentApplication::with([
                'test:id,slug,name,type',
                'result:id,assessment_application_id,overall_score,quality_index,calculated_at',
                'candidate:id,name,email',
            ])->orderByDesc('created_at');

            // Scoping multitenant: client só vê suas aplicações
            $user = $request->user();
            if (!in_array($user->role, ['admin', 'operational'], true)) {
                $query->where('recruitment_client_id', $user->recruitment_client_id);
            }

            if ($request->filled('test_id')) {
                $query->where('assessment_test_id', $request->integer('test_id'));
            }
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            $applications = $query->paginate(30);

            return response()->json(['success' => true, 'data' => $applications]);
        } catch (\Exception $e) {
            Log::error('Assessment::listApplications ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao listar aplicações.'], 500);
        }
    }

    /**
     * POST /api/assessments/applications
     * Cria uma aplicação (gera o link de acesso público).
     */
    public function createApplication(CreateApplicationRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Admin/operational pode vincular a qualquer empresa.
            // Client só pode vincular à sua própria empresa (ou nenhuma).
            if (in_array($user->role, ['admin', 'operational'], true)) {
                $clientId = $request->input('recruitment_client_id'); // pode ser null
            } else {
                $clientId = $user->recruitment_client_id; // sempre a empresa do usuário
            }

            $application = AssessmentApplication::create([
                'assessment_test_id'   => $request->integer('assessment_test_id'),
                'recruitment_client_id'=> $clientId,
                'candidate_id'         => $request->input('candidate_id'),
                'respondent_name'      => $request->input('respondent_name'),
                'respondent_email'     => $request->input('respondent_email'),
                'application_type'     => $request->input('application_type', 'candidate'),
                'expires_at'           => $request->input('expires_at'),
                'metadata'             => $request->input('metadata'),
                'status'               => 'pending',
                // token gerado automaticamente pelo model booted()
            ]);

            $publicUrl = config('app.frontend_url') . '/assessment/' . $application->token;

            // Envia e-mail se houver endereço do respondente
            $emailSent = false;
            if ($application->respondent_email) {
                try {
                    $application->loadMissing('test');
                    Mail::to($application->respondent_email)
                        ->send(new AssessmentLinkMail($application, $publicUrl));
                    $emailSent = true;
                } catch (\Exception $mailEx) {
                    Log::warning('Assessment: falha ao enviar e-mail', [
                        'application_id' => $application->id,
                        'email'          => $application->respondent_email,
                        'error'          => $mailEx->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Aplicação criada com sucesso.',
                'data'    => [
                    'id'         => $application->id,
                    'token'      => $application->token,
                    'public_url' => $publicUrl,
                    'expires_at' => $application->expires_at,
                    'email_sent' => $emailSent,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Assessment::createApplication ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao criar aplicação.'], 500);
        }
    }

    // =========================================================================
    // Re-envio de link
    // =========================================================================

    /**
     * POST /api/assessments/applications/{id}/resend
     * Reenvia o e-mail com o link para o respondente.
     */
    public function resend(int $id): JsonResponse
    {
        try {
            $application = AssessmentApplication::with('test')->findOrFail($id);

            if (!$this->canAccessApplication($application)) {
                return response()->json(['success' => false, 'message' => 'Não encontrado.'], 404);
            }

            if (!$application->respondent_email) {
                return response()->json(['success' => false, 'message' => 'Nenhum e-mail cadastrado para esta aplicação.'], 422);
            }

            if ($application->status === 'completed') {
                return response()->json(['success' => false, 'message' => 'Este mapeamento já foi respondido.'], 422);
            }

            $publicUrl = config('app.frontend_url') . '/assessment/' . $application->token;
            Mail::to($application->respondent_email)
                ->send(new AssessmentLinkMail($application, $publicUrl));

            Log::info('Assessment: link reenviado', ['application_id' => $application->id]);

            return response()->json(['success' => true, 'message' => 'Link reenviado com sucesso.']);
        } catch (\Exception $e) {
            Log::error('Assessment::resend ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao reenviar e-mail.'], 500);
        }
    }

    // =========================================================================
    // Prorrogar expiração
    // =========================================================================

    /**
     * PATCH /api/assessments/applications/{id}/extend
     * Atualiza a data de expiração. Body: { "expires_at": "2025-12-31" }
     */
    public function extend(int $id, Request $request): JsonResponse
    {
        try {
            $request->validate(['expires_at' => 'required|date|after:now']);

            $application = AssessmentApplication::findOrFail($id);

            if (!$this->canAccessApplication($application)) {
                return response()->json(['success' => false, 'message' => 'Não encontrado.'], 404);
            }

            $application->update([
                'expires_at' => $request->input('expires_at'),
                'status'     => $application->status === 'expired' ? 'pending' : $application->status,
            ]);

            return response()->json([
                'success'    => true,
                'message'    => 'Data de expiração atualizada.',
                'expires_at' => $application->expires_at,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Data inválida. Informe uma data futura.'], 422);
        } catch (\Exception $e) {
            Log::error('Assessment::extend ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao atualizar expiração.'], 500);
        }
    }

    // =========================================================================
    // Métricas
    // =========================================================================

    /**
     * GET /api/assessments/stats
     * Retorna métricas agregadas para o dashboard.
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $isAdmin = in_array($user->role, ['admin', 'operational'], true);

            $base = AssessmentApplication::query();
            if (!$isAdmin) {
                $base->where('recruitment_client_id', $user->recruitment_client_id);
            }

            $total      = (clone $base)->count();
            $completed  = (clone $base)->where('status', 'completed')->count();
            $pending    = (clone $base)->whereIn('status', ['pending', 'started'])->count();
            $expired    = (clone $base)->where('status', 'expired')->count();

            // Score médio e distribuição de classificações
            $resultIds = (clone $base)->where('status', 'completed')
                ->pluck('id');

            // Busca os scores para média e distribuição de classificações em PHP
            // (classification é accessor, não coluna real)
            $results  = AssessmentResult::whereIn('assessment_application_id', $resultIds)
                ->get(['overall_score']);

            $avgScore = $results->avg('overall_score');

            // Distribuição calculada em PHP com o accessor
            $classificationDist = $results->groupBy(fn($r) => $r->classification)
                ->map(fn($g) => $g->count());

            // Por instrumento — usa ? para bind posicional
            $byTest = (clone $base)
                ->join('assessment_tests', 'assessment_applications.assessment_test_id', '=', 'assessment_tests.id')
                ->selectRaw(
                    'assessment_tests.name, assessment_tests.slug, count(*) as total, sum(case when assessment_applications.status = ? then 1 else 0 end) as completed',
                    ['completed']
                )
                ->groupBy('assessment_tests.id', 'assessment_tests.name', 'assessment_tests.slug')
                ->get();

            return response()->json([
                'success' => true,
                'data'    => [
                    'total'               => $total,
                    'completed'           => $completed,
                    'pending'             => $pending,
                    'expired'             => $expired,
                    'completion_rate'     => $total > 0 ? round($completed / $total * 100, 1) : 0,
                    'avg_score'           => $avgScore ? round($avgScore, 1) : null,
                    'classification_dist' => $classificationDist,
                    'by_test'             => $byTest,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Assessment::stats ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao carregar métricas.'], 500);
        }
    }

    // =========================================================================
    // Comparação de candidatos
    // =========================================================================

    /**
     * POST /api/assessments/applications/compare
     * Recebe um array de IDs de aplicações concluídas e retorna dados rankeados
     * para exibição lado a lado.
     * Body: { "ids": [1, 2, 3] }
     */
    public function compare(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ids'   => 'required|array|min:2|max:20',
                'ids.*' => 'integer',
            ]);

            $ids = $request->input('ids');

            $applications = AssessmentApplication::with([
                'test:id,slug,name,type',
                'test.dimensions:id,assessment_test_id,slug,name,order',
                'candidate:id,name,email',
                'result',
            ])->whereIn('id', $ids)
              ->where('status', 'completed')
              ->get();

            if ($applications->count() < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'São necessárias pelo menos 2 aplicações concluídas para comparar.',
                ], 422);
            }

            // Verifica acesso a cada aplicação
            foreach ($applications as $app) {
                if (!$this->canAccessApplication($app)) {
                    return response()->json(['success' => false, 'message' => 'Acesso negado a uma das aplicações.'], 403);
                }
            }

            // Coleta todas as dimensões do(s) instrumento(s)
            $allDimensions = collect();
            foreach ($applications as $app) {
                foreach ($app->test->dimensions as $dim) {
                    $allDimensions->put($dim->slug, ['slug' => $dim->slug, 'name' => $dim->name]);
                }
            }
            $allDimensions = $allDimensions->sortBy('name')->values();

            // Monta os dados de cada candidato
            $candidates = $applications->map(function ($app) use ($allDimensions) {
                $result      = $app->result;
                $dimScores   = $result?->dimension_scores ?? [];

                $dimensions = $allDimensions->map(function ($dim) use ($dimScores) {
                    $d = $dimScores[$dim['slug']] ?? null;
                    return [
                        'slug'           => $dim['slug'],
                        'name'           => $dim['name'],
                        'score'          => $d ? round($d['score'], 1) : null,
                        'classification' => $d['classification'] ?? null,
                    ];
                })->values();

                return [
                    'application_id'   => $app->id,
                    'respondent_name'  => $app->respondent_name ?? $app->candidate?->name ?? 'Sem nome',
                    'respondent_email' => $app->respondent_email ?? $app->candidate?->email,
                    'test_name'        => $app->test->name,
                    'test_slug'        => $app->test->slug,
                    'completed_at'     => $app->completed_at,
                    'overall_score'    => $result ? round($result->overall_score, 1) : null,
                    'classification'   => $result?->classification,
                    'quality_index'    => $result?->quality_index,
                    'dimensions'       => $dimensions,
                    'metadata'         => $app->metadata,
                ];
            })
            ->sortByDesc('overall_score')
            ->values();

            return response()->json([
                'success' => true,
                'data'    => [
                    'candidates'  => $candidates,
                    'dimensions'  => $allDimensions->values(),
                    'test_name'   => $applications->first()->test->name,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'IDs inválidos.'], 422);
        } catch (\Exception $e) {
            Log::error('Assessment::compare ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao comparar candidatos.'], 500);
        }
    }

    /**
     * POST /api/assessments/applications/compare/pdf
     * Gera PDF do relatório consolidado com os candidatos selecionados.
     * Body: { "ids": [1, 2, 3], "job_title": "Dev Sênior" }
     */
    public function comparePdf(Request $request)
    {
        try {
            $request->validate([
                'ids'   => 'required|array|min:1|max:20',
                'ids.*' => 'integer',
            ]);

            $ids = $request->input('ids');

            $applications = AssessmentApplication::with([
                'test:id,slug,name,type,disclaimer',
                'test.dimensions:id,assessment_test_id,slug,name,order',
                'candidate:id,name,email',
                'result',
                'recruitmentClient:id,name',
            ])->whereIn('id', $ids)
              ->where('status', 'completed')
              ->get();

            foreach ($applications as $app) {
                if (!$this->canAccessApplication($app)) {
                    return response()->json(['success' => false, 'message' => 'Acesso negado.'], 403);
                }
            }

            // Coleta dimensões únicas
            $allDimensions = collect();
            foreach ($applications as $app) {
                foreach ($app->test->dimensions as $dim) {
                    $allDimensions->put($dim->slug, ['slug' => $dim->slug, 'name' => $dim->name, 'order' => $dim->order]);
                }
            }
            $allDimensions = $allDimensions->sortBy('order')->values();

            // Monta candidatos rankeados
            $candidates = $applications->map(function ($app) use ($allDimensions) {
                $result    = $app->result;
                $dimScores = $result?->dimension_scores ?? [];
                $dims      = $allDimensions->map(fn($dim) => [
                    'slug'  => $dim['slug'],
                    'name'  => $dim['name'],
                    'score' => isset($dimScores[$dim['slug']]) ? round($dimScores[$dim['slug']]['score'], 1) : null,
                ])->values();

                return [
                    'app'          => $app,
                    'result'       => $result,
                    'overall_score'=> $result ? round($result->overall_score, 1) : 0,
                    'dimensions'   => $dims,
                ];
            })->sortByDesc('overall_score')->values();

            $jobTitle  = $request->input('job_title') ?? ($applications->first()->metadata['job_title'] ?? null);
            $testName  = $applications->first()->test->name;
            $client    = $applications->first()->recruitmentClient;
            $disclaimer= $applications->first()->test->disclaimer;

            $pdf = Pdf::loadView('pdf.assessment-comparison', compact(
                'candidates', 'allDimensions', 'jobTitle', 'testName', 'client', 'disclaimer'
            ))->setPaper('a4', 'landscape');

            $filename = 'Relatorio_Comparativo_' . str_replace(' ', '_', $jobTitle ?? 'Candidatos') . '_' . now()->format('Y-m-d') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('Assessment::comparePdf ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao gerar PDF.'], 500);
        }
    }

    /**
     * GET /api/assessments/applications/{id}
     * Detalhe de uma aplicação com resultado (se calculado).
     */
    public function showApplication(int $id): JsonResponse
    {
        try {
            $application = AssessmentApplication::with([
                'test:id,slug,name,type,disclaimer',
                'test.dimensions:id,assessment_test_id,slug,name,weight,order',
                'candidate:id,name,email',
                'result',
            ])->findOrFail($id);

            if (!$this->canAccessApplication($application)) {
                return response()->json(['success' => false, 'message' => 'Não encontrado.'], 404);
            }

            return response()->json(['success' => true, 'data' => $application]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Não encontrado.'], 404);
        }
    }

    /**
     * DELETE /api/assessments/applications/{id}
     */
    public function destroyApplication(int $id): JsonResponse
    {
        try {
            $application = AssessmentApplication::findOrFail($id);

            if (!$this->canAccessApplication($application)) {
                return response()->json(['success' => false, 'message' => 'Não encontrado.'], 404);
            }

            $application->delete();

            return response()->json(['success' => true, 'message' => 'Aplicação excluída.']);
        } catch (\Exception $e) {
            Log::error('Assessment::destroyApplication ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao excluir.'], 500);
        }
    }

    // =========================================================================
    // Resultado
    // =========================================================================

    /**
     * GET /api/assessments/applications/{id}/result
     * Retorna o resultado calculado de uma aplicação.
     */
    public function result(int $id): JsonResponse
    {
        try {
            $application = AssessmentApplication::with([
                'test:id,slug,name,type,version,disclaimer',
                'test.dimensions:id,assessment_test_id,slug,name,weight,order',
                'candidate:id,name,email',
                'result',
            ])->findOrFail($id);

            if (!$this->canAccessApplication($application)) {
                return response()->json(['success' => false, 'message' => 'Não encontrado.'], 404);
            }

            if (!$application->result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resultado ainda não calculado.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'application' => $application->only([
                        'id', 'status', 'respondent_name', 'respondent_email',
                        'application_type', 'completed_at',
                    ]),
                    'test'        => $application->test,
                    'candidate'   => $application->candidate,
                    'result'      => $application->result,
                    'disclaimer'  => $application->test->disclaimer,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Não encontrado.'], 404);
        }
    }

    // =========================================================================
    // PDF
    // =========================================================================

    /**
     * GET /api/assessments/applications/{id}/pdf
     * Gera e retorna o PDF do resultado de uma aplicação.
     */
    public function downloadPdf(int $id)
    {
        try {
            $application = AssessmentApplication::with([
                'test:id,slug,name,type,version,disclaimer',
                'test.dimensions:id,assessment_test_id,slug,name,weight,order',
                'candidate:id,name,email',
                'result',
                'recruitmentClient:id,name',
            ])->findOrFail($id);

            if (!$this->canAccessApplication($application)) {
                return response()->json(['success' => false, 'message' => 'Não encontrado.'], 404);
            }

            if (!$application->result) {
                return response()->json(['success' => false, 'message' => 'Resultado ainda não calculado.'], 404);
            }

            $dimScores = $application->result->dimension_scores ?? [];
            uasort($dimScores, fn($a, $b) => $b['score'] <=> $a['score']);

            $pdf = Pdf::loadView('pdf.assessment-result', [
                'application'       => $application,
                'test'              => $application->test,
                'result'            => $application->result,
                'ranked_dimensions' => $dimScores,
                'quality_index'     => $application->result->quality_index ?? 100,
                'flags'             => $application->result->flags ?? [],
                'jobTitle'          => $application->metadata['job_title'] ?? null,
            ])->setPaper('a4', 'portrait');

            $name     = $application->respondent_name ?? 'Resultado';
            $filename = 'Mapeamento_' . str_replace(' ', '_', $name) . '_' . now()->format('Y-m-d') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('Assessment PDF error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao gerar PDF.'], 500);
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function canAccessApplication(AssessmentApplication $application): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        if (in_array($user->role, ['admin', 'operational'], true)) {
            return true;
        }

        return $application->recruitment_client_id === $user->recruitment_client_id;
    }
}
