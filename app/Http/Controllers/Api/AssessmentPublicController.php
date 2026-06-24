<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Assessment\SubmitResponsesRequest;
use App\Models\AssessmentApplication;
use App\Models\AssessmentResponse;
use App\Models\User;
use App\Notifications\AssessmentCompletedNotification;
use App\Services\Assessment\AssessmentScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class AssessmentPublicController extends Controller
{
    public function __construct(
        private readonly AssessmentScoringService $scoringService
    ) {}

    /**
     * GET /api/assessment/public/{token}
     * Valida o token e retorna o instrumento com perguntas (sem gabarito SJT).
     */
    public function start(string $token): JsonResponse
    {
        try {
            $application = $this->resolveToken($token);

            if (!$application) {
                return response()->json(['success' => false, 'message' => 'Link inválido ou expirado.'], 404);
            }

            if ($application->isCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este instrumento já foi respondido.',
                    'data'    => ['status' => 'completed', 'result_id' => $application->result?->id],
                ], 422);
            }

            // Marca como iniciado na primeira abertura
            if ($application->status === 'pending') {
                $application->update(['status' => 'started', 'started_at' => now()]);
            }

            $application->load([
                'test' => fn($q) => $q->select(['id', 'slug', 'name', 'description', 'type', 'version', 'disclaimer']),
                'test.dimensions:id,assessment_test_id,slug,name,description,order',
                'test.questions' => fn($q) => $q->with([
                    'options:id,assessment_question_id,label,text,order', // score oculto
                ])->orderBy('order'),
            ]);

            // Embaralha opções de questões SJT para evitar viés de posição
            $application->test->questions->each(function ($question) {
                if (in_array($question->question_type, ['sjt_pair', 'single_choice'])) {
                    $question->setRelation('options', $question->options->shuffle());
                }
            });

            return response()->json([
                'success' => true,
                'data'    => [
                    'application_id'   => $application->id,
                    'respondent_name'  => $application->respondent_name,
                    'respondent_email' => $application->respondent_email,
                    'test'             => $application->test,
                    'expires_at'       => $application->expires_at,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AssessmentPublic::start ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao carregar instrumento.'], 500);
        }
    }

    /**
     * POST /api/assessment/public/{token}/submit
     * Recebe as respostas, calcula o resultado e retorna o id.
     */
    public function submit(SubmitResponsesRequest $request, string $token): JsonResponse
    {
        try {
            $application = $this->resolveToken($token);

            if (!$application) {
                return response()->json(['success' => false, 'message' => 'Link inválido ou expirado.'], 404);
            }

            if ($application->isCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este instrumento já foi respondido.',
                ], 422);
            }

            // Carrega as perguntas do teste para validar que as respostas pertencem a ele
            $application->load('test.questions:id,assessment_test_id,question_type');
            $validQuestionIds = $application->test->questions->pluck('id')->all();

            DB::transaction(function () use ($request, $application, $validQuestionIds) {
                // Atualiza dados do respondente se enviados
                if ($request->filled('respondent_name') || $request->filled('respondent_email')) {
                    $application->update(array_filter([
                        'respondent_name'  => $request->input('respondent_name'),
                        'respondent_email' => $request->input('respondent_email'),
                    ]));
                }

                foreach ($request->input('responses') as $responseData) {
                    $questionId = $responseData['question_id'];

                    // Ignora respostas de perguntas que não pertencem ao teste
                    if (!in_array($questionId, $validQuestionIds, true)) {
                        continue;
                    }

                    AssessmentResponse::updateOrCreate(
                        [
                            'assessment_application_id' => $application->id,
                            'assessment_question_id'    => $questionId,
                        ],
                        [
                            'assessment_option_id'    => $responseData['option_id'] ?? null,
                            'numeric_answer'          => $responseData['numeric_answer'] ?? null,
                            'text_answer'             => $responseData['text_answer'] ?? null,
                            'ranking_json'            => $responseData['ranking_json'] ?? null,
                            'sjt_pair_json'           => $responseData['sjt_pair_json'] ?? null,
                            'response_time_seconds'   => $responseData['response_time_seconds'] ?? null,
                        ]
                    );
                }

                // Calcula e persiste o resultado
                $this->scoringService->score($application);
            });

            // Notifica admins/operacionais — best-effort, não bloqueia a resposta
            try {
                $application->load(['test:id,name', 'result']);
                $recipients = User::whereIn('role', ['admin', 'operational'])->get();
                Notification::send($recipients, new AssessmentCompletedNotification($application));
            } catch (\Exception $notifEx) {
                Log::warning('AssessmentPublic::submit — falha ao enviar notificação: ' . $notifEx->getMessage());
            }

            $application->refresh();

            return response()->json([
                'success'    => true,
                'message'    => 'Respostas enviadas com sucesso.',
                'data'       => [
                    'result_id'     => $application->result->id,
                    'overall_score' => $application->result->overall_score,
                    'classification'=> $application->result->classification,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AssessmentPublic::submit ' . $e->getMessage(), [
                'token' => $token,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'message' => 'Erro ao processar respostas.'], 500);
        }
    }

    /**
     * GET /api/assessment/public/{token}/result
     * Exibe o resultado ao respondente após a submissão.
     */
    public function result(string $token): JsonResponse
    {
        try {
            $application = AssessmentApplication::where('token', $token)
                ->with([
                    'test:id,slug,name,type,version,disclaimer',
                    'test.dimensions:id,assessment_test_id,slug,name,weight,order',
                    'result',
                ])
                ->first();

            if (!$application || !$application->isCompleted()) {
                return response()->json(['success' => false, 'message' => 'Resultado não disponível.'], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => [
                    'respondent_name' => $application->respondent_name,
                    'test'            => $application->test,
                    'result'          => $application->result,
                    'disclaimer'      => $application->test->disclaimer,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AssessmentPublic::result ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao carregar resultado.'], 500);
        }
    }

    // -------------------------------------------------------------------------

    private function resolveToken(string $token): ?AssessmentApplication
    {
        $application = AssessmentApplication::where('token', $token)->first();

        if (!$application) {
            return null;
        }

        if ($application->isExpired()) {
            $application->update(['status' => 'expired']);
            return null;
        }

        return $application;
    }
}
