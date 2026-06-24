<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiscQuestion;
use App\Models\DiscTestResult;
use App\Models\DiscTestToken;
use App\Models\TestAuditLog;
use App\Services\OpenAIService;
use App\Traits\ScopesByClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class DiscTestController extends Controller
{
    use ScopesByClient;

    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    public function getQuestions()
    {
        try {
            $questions = DiscQuestion::active()->ordered()->get();

            return response()->json([
                'success' => true,
                'questions' => $questions,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar questões DISC: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar questões do teste.',
            ], 500);
        }
    }

    public function submitTest(Request $request)
    {
        try {
            $validated = $request->validate([
                'answers' => 'required|array',
                'answers.*.question_id' => 'required|exists:disc_questions,id',
                'answers.*.most' => 'required|in:D,I,S,C',
                'answers.*.least' => 'required|in:D,I,S,C',
                'testee_name' => 'required|string|max:255',
                'testee_email' => 'required|email|max:255',
                'testee_cpf' => 'required|string|max:20',
                'testee_phone' => 'nullable|string|max:20',
                'testee_position' => 'nullable|string|max:255',
            ]);

            $answers = $validated['answers'];
            $scores = $this->calculateScores($answers);

            $testResult = DiscTestResult::create([
                'user_id' => $request->user()->id,
                'testee_name' => $validated['testee_name'],
                'testee_email' => $validated['testee_email'],
                'testee_cpf' => $validated['testee_cpf'],
                'testee_phone' => $validated['testee_phone'] ?? null,
                'testee_position' => $validated['testee_position'] ?? null,
                'answers' => $answers,
                'score_d' => $scores['D'],
                'score_i' => $scores['I'],
                'score_s' => $scores['S'],
                'score_c' => $scores['C'],
                'primary_profile' => $scores['primary'],
                'secondary_profile' => $scores['secondary'],
                'status' => 'pending',
            ]);

            // Analisa com IA e atualiza status
            $this->analyzeWithAI($testResult);

            // Recarrega o resultado para pegar o status atualizado
            $testResult->refresh();

            TestAuditLog::record(
                TestAuditLog::TYPE_DISC,
                TestAuditLog::ACTION_TEST_SUBMITTED,
                'result',
                $testResult->id,
                [
                    'testee_name'  => $testResult->testee_name,
                    'testee_email' => $testResult->testee_email,
                    'source'       => 'authenticated',
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Teste submetido com sucesso!',
                'result_id' => $testResult->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao submeter teste DISC: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao submeter teste.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Submete teste público via token
     */
    public function submitPublicTest(Request $request, $token)
    {
        try {
            // Token já validado pelo middleware
            $tokenModel = $request->get('validated_token');

            $validated = $request->validate([
                'answers' => 'required|array',
                'answers.*.question_id' => 'required|exists:disc_questions,id',
                'answers.*.most' => 'required|in:D,I,S,C',
                'answers.*.least' => 'required|in:D,I,S,C',
                'testee_name' => 'required|string|max:255',
                'testee_email' => 'required|email|max:255',
                'testee_cpf' => 'required|string|max:20',
                'testee_phone' => 'nullable|string|max:20',
                'testee_position' => 'nullable|string|max:255',
            ]);

            $answers = $validated['answers'];
            $scores = $this->calculateScores($answers);

            // Usa dados do token se não fornecidos na requisição
            $testeeData = [
                'testee_name' => $validated['testee_name'] ?? $tokenModel->testee_name ?? 'Testado',
                'testee_email' => $validated['testee_email'] ?? $tokenModel->testee_email ?? null,
                'testee_cpf' => $validated['testee_cpf'],
                'testee_phone' => $validated['testee_phone'] ?? $tokenModel->testee_phone ?? null,
                'testee_position' => $validated['testee_position'] ?? $tokenModel->testee_position ?? null,
            ];

            $testResult = DiscTestResult::create([
                'user_id'            => $tokenModel->user_id,
                'candidate_id'       => $tokenModel->candidate_id,
                'disc_test_token_id' => $tokenModel->id,
                'testee_name'        => $testeeData['testee_name'],
                'testee_email'       => $testeeData['testee_email'],
                'testee_cpf'         => $testeeData['testee_cpf'],
                'testee_phone'       => $testeeData['testee_phone'],
                'testee_position'    => $testeeData['testee_position'],
                'answers'            => $answers,
                'score_d'            => $scores['D'],
                'score_i'            => $scores['I'],
                'score_s'            => $scores['S'],
                'score_c'            => $scores['C'],
                'primary_profile'    => $scores['primary'],
                'secondary_profile'  => $scores['secondary'],
                'status'             => 'pending',
            ]);

            // Marca o token como usado
            $tokenModel->markAsUsed($testResult->id);

            // Analisa com IA e atualiza status
            $this->analyzeWithAI($testResult);

            // Recarrega o resultado para pegar o status atualizado
            $testResult->refresh();

            // Auditoria atribuída ao criador do token (não há auth no fluxo público)
            TestAuditLog::record(
                TestAuditLog::TYPE_DISC,
                TestAuditLog::ACTION_TEST_SUBMITTED,
                'result',
                $testResult->id,
                [
                    'testee_name'  => $testResult->testee_name,
                    'testee_email' => $testResult->testee_email,
                    'token_id'     => $tokenModel->id,
                    'source'       => 'public_token',
                ],
                $tokenModel->user_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Teste submetido com sucesso!',
                'result_id' => $testResult->id,
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao submeter teste DISC público: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao submeter teste.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getResult($id)
    {
        try {
            $result = DiscTestResult::with('user')->findOrFail($id);

            if (!$this->userCanAccessResult($result->user_id, $result->testee_email, $result)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resultado não encontrado.',
                ], 404);
            }

            TestAuditLog::record(
                TestAuditLog::TYPE_DISC,
                TestAuditLog::ACTION_RESULT_VIEWED,
                'result',
                $result->id
            );

            return response()->json([
                'success' => true,
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar resultado DISC: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Resultado não encontrado.',
            ], 404);
        }
    }

    public function getMyResults()
    {
        try {
            $query = DiscTestResult::with('user:id,name')->orderBy('created_at', 'desc');
            $this->applyClientScopeWithLinkedCandidates($query);
            $results = $query->get();

            return response()->json([
                'success' => true,
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar histórico DISC: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar histórico.',
            ], 500);
        }
    }

    public function getLatestResult()
    {
        try {
            $query = DiscTestResult::query()->orderBy('created_at', 'desc');
            $this->applyClientScopeWithLinkedCandidates($query);
            $result = $query->first();

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhum teste encontrado.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar último resultado DISC: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar resultado.',
            ], 500);
        }
    }

    /**
     * Busca resultado público via token
     */
    public function getPublicResult($token, $resultId)
    {
        try {
            // Token já validado pelo middleware
            $tokenModel = request()->get('validated_token');

            $result = DiscTestResult::with('user')
                ->where('id', $resultId)
                ->where('disc_test_token_id', $tokenModel->id)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'result' => $result,
                'token_info' => [
                    'job_title' => $tokenModel->job_title,
                    'description' => $tokenModel->description,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar resultado DISC público: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Resultado não encontrado.',
            ], 404);
        }
    }

    private function calculateScores(array $answers): array
    {
        $scores = [
            'D' => 0,
            'I' => 0,
            'S' => 0,
            'C' => 0,
        ];

        foreach ($answers as $answer) {
            $scores[$answer['most']] += 2;
            $scores[$answer['least']] -= 1;
        }

        foreach ($scores as $key => $value) {
            $scores[$key] = max(0, $value);
        }

        arsort($scores);
        $profileKeys = array_keys($scores);

        return [
            'D' => $scores['D'],
            'I' => $scores['I'],
            'S' => $scores['S'],
            'C' => $scores['C'],
            'primary' => $profileKeys[0],
            'secondary' => $profileKeys[1] ?? null,
        ];
    }

    private function analyzeWithAI(DiscTestResult $testResult)
    {
        try {
            $analysis = $this->openAIService->analyzeDiscProfile([
                'scores' => [
                    'D' => $testResult->score_d,
                    'I' => $testResult->score_i,
                    'S' => $testResult->score_s,
                    'C' => $testResult->score_c,
                ],
                'primary_profile' => $testResult->primary_profile,
                'secondary_profile' => $testResult->secondary_profile,
                'percentages' => $testResult->profile_percentages,
            ]);

            if ($analysis) {
                $testResult->update([
                    'ai_analysis' => $analysis['analysis'] ?? null,
                    'strengths' => $analysis['strengths'] ?? null,
                    'development_areas' => $analysis['development_areas'] ?? null,
                    'ideal_roles' => $analysis['ideal_roles'] ?? null,
                    'work_style' => $analysis['work_style'] ?? null,
                    'status' => 'analyzed',
                ]);

                Log::info('Perfil DISC analisado com sucesso pela IA', [
                    'test_result_id' => $testResult->id,
                    'testee_name' => $testResult->testee_name,
                ]);
            } else {
                // Se não conseguiu analisar, marca como completed sem análise
                $testResult->update(['status' => 'completed']);

                Log::warning('Análise DISC retornou vazia', [
                    'test_result_id' => $testResult->id,
                ]);
            }
        } catch (\Exception $e) {
            // Em caso de erro, marca como completed sem análise
            $testResult->update(['status' => 'completed']);

            Log::error('Erro ao analisar perfil DISC com IA', [
                'test_result_id' => $testResult->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function downloadPdf($id)
    {
        try {
            $result = DiscTestResult::with('user')->findOrFail($id);

            if (!$this->userCanAccessResult($result->user_id, $result->testee_email, $result)) {
                return response()->json(['success' => false, 'message' => 'Acesso não autorizado.'], 403);
            }

            $pdf = Pdf::loadView('pdf.disc-result', [
                'result'      => $result,
                'percentages' => $result->profile_percentages,
            ]);

            $filename = 'DISC_' . ($result->testee_name ? str_replace(' ', '_', $result->testee_name) : 'Resultado') . '_' . date('Y-m-d') . '.pdf';

            TestAuditLog::record(
                TestAuditLog::TYPE_DISC,
                TestAuditLog::ACTION_PDF_DOWNLOADED,
                'result',
                $result->id
            );

            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('Erro ao gerar PDF DISC: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar PDF.',
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $result = DiscTestResult::findOrFail($id);

            if (!$this->userCanAccessByUserId($result->user_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resultado não encontrado.',
                ], 404);
            }

            $resultId = $result->id;
            $result->delete();

            TestAuditLog::record(
                TestAuditLog::TYPE_DISC,
                TestAuditLog::ACTION_RESULT_DELETED,
                'result',
                $resultId
            );

            return response()->json([
                'success' => true,
                'message' => 'Teste DISC excluído com sucesso.',
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao excluir teste DISC: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir teste.',
            ], 500);
        }
    }
}
