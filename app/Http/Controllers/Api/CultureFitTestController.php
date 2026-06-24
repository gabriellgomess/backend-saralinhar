<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CultureFitQuestion;
use App\Models\CultureFitResult;
use App\Models\TestAuditLog;
use App\Services\OpenAIService;
use App\Traits\ScopesByClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class CultureFitTestController extends Controller
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
            $questions = CultureFitQuestion::active()->ordered()->get();

            return response()->json([
                'success' => true,
                'questions' => $questions,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar questões Culture Fit: ' . $e->getMessage());
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
                'answers.*.question_id' => 'required|exists:culture_fit_questions,id',
                'answers.*.rating' => 'required|integer|min:1|max:5',
                'testee_name' => 'required|string|max:255',
                'testee_email' => 'required|email|max:255',
                'testee_cpf' => 'required|string|max:20',
                'testee_phone' => 'nullable|string|max:20',
                'testee_position' => 'nullable|string|max:255',
            ]);

            $answers = $validated['answers'];
            $scores = $this->calculateScores($answers);

            $testResult = CultureFitResult::create([
                'user_id' => $request->user()->id,
                'testee_name' => $validated['testee_name'],
                'testee_email' => $validated['testee_email'],
                'testee_cpf' => $validated['testee_cpf'],
                'testee_phone' => $validated['testee_phone'] ?? null,
                'testee_position' => $validated['testee_position'] ?? null,
                'answers' => $answers,
                'score_autonomy' => $scores['autonomy'],
                'score_innovation' => $scores['innovation'],
                'score_hierarchy' => $scores['hierarchy'],
                'score_teamwork' => $scores['teamwork'],
                'score_results' => $scores['results'],
                'score_flexibility' => $scores['flexibility'],
                'status' => 'pending',
            ]);

            // Analisa com IA e atualiza status
            $this->analyzeWithAI($testResult);

            // Recarrega o resultado para pegar o status atualizado
            $testResult->refresh();

            TestAuditLog::record(
                TestAuditLog::TYPE_CULTURE_FIT,
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
            Log::error('Erro ao submeter teste Culture Fit: ' . $e->getMessage());
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
                'answers.*.question_id' => 'required|exists:culture_fit_questions,id',
                'answers.*.rating' => 'required|integer|min:1|max:5',
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

            $testResult = CultureFitResult::create([
                'user_id'                   => $tokenModel->user_id,
                'candidate_id'              => $tokenModel->candidate_id,
                'culture_fit_test_token_id' => $tokenModel->id,
                'testee_name'               => $testeeData['testee_name'],
                'testee_email'              => $testeeData['testee_email'],
                'testee_cpf'                => $testeeData['testee_cpf'],
                'testee_phone'              => $testeeData['testee_phone'],
                'testee_position'           => $testeeData['testee_position'],
                'answers'                   => $answers,
                'score_autonomy'            => $scores['autonomy'],
                'score_innovation'          => $scores['innovation'],
                'score_hierarchy'           => $scores['hierarchy'],
                'score_teamwork'            => $scores['teamwork'],
                'score_results'             => $scores['results'],
                'score_flexibility'         => $scores['flexibility'],
                'status'                    => 'pending',
            ]);

            // Marca o token como usado
            $tokenModel->markAsUsed($testResult->id);

            // Analisa com IA e atualiza status
            $this->analyzeWithAI($testResult);

            // Recarrega o resultado para pegar o status atualizado
            $testResult->refresh();

            // Auditoria atribuída ao criador do token (não há auth no fluxo público)
            TestAuditLog::record(
                TestAuditLog::TYPE_CULTURE_FIT,
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
            Log::error('Erro ao submeter teste Culture Fit público: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao submeter teste.',
                'error' => $e->getMessage(),
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

            $result = CultureFitResult::with('user')
                ->where('id', $resultId)
                ->where('culture_fit_test_token_id', $tokenModel->id)
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
            Log::error('Erro ao buscar resultado Culture Fit público: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Resultado não encontrado.',
            ], 404);
        }
    }

    public function getResult($id)
    {
        try {
            $result = CultureFitResult::with('user')->findOrFail($id);

            if (!$this->userCanAccessResult($result->user_id, $result->testee_email, $result)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resultado não encontrado.',
                ], 404);
            }

            TestAuditLog::record(
                TestAuditLog::TYPE_CULTURE_FIT,
                TestAuditLog::ACTION_RESULT_VIEWED,
                'result',
                $result->id
            );

            return response()->json([
                'success' => true,
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar resultado Culture Fit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Resultado não encontrado.',
            ], 404);
        }
    }

    public function getMyResults()
    {
        try {
            $query = CultureFitResult::with('user:id,name')->orderBy('created_at', 'desc');
            $this->applyClientScopeWithLinkedCandidates($query);
            $results = $query->get();

            return response()->json([
                'success' => true,
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar histórico Culture Fit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar histórico.',
            ], 500);
        }
    }

    public function getLatestResult()
    {
        try {
            $query = CultureFitResult::query()->orderBy('created_at', 'desc');
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
            Log::error('Erro ao buscar último resultado Culture Fit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar resultado.',
            ], 500);
        }
    }

    private function calculateScores(array $answers): array
    {
        $scores = [
            'autonomy' => 0,
            'innovation' => 0,
            'hierarchy' => 0,
            'teamwork' => 0,
            'results' => 0,
            'flexibility' => 0,
        ];

        // Busca todas as questões para saber a direção do scoring
        $questionIds = array_column($answers, 'question_id');
        $questions = CultureFitQuestion::whereIn('id', $questionIds)->get()->keyBy('id');

        foreach ($answers as $answer) {
            $question = $questions[$answer['question_id']];
            $rating = $answer['rating'];
            $dimension = $question->dimension;

            // Se scoring_direction é 'positive', rating alto = score alto
            // Se scoring_direction é 'negative', rating alto = score baixo
            if ($question->scoring_direction === 'positive') {
                $scores[$dimension] += $rating;
            } else {
                // Inverte a escala: 5->1, 4->2, 3->3, 2->4, 1->5
                $scores[$dimension] += (6 - $rating);
            }
        }

        return $scores;
    }

    private function analyzeWithAI(CultureFitResult $testResult)
    {
        try {
            $analysis = $this->openAIService->analyzeCultureFitProfile([
                'scores' => [
                    'autonomy' => $testResult->score_autonomy,
                    'innovation' => $testResult->score_innovation,
                    'hierarchy' => $testResult->score_hierarchy,
                    'teamwork' => $testResult->score_teamwork,
                    'results' => $testResult->score_results,
                    'flexibility' => $testResult->score_flexibility,
                ],
                'dominant_dimensions' => $testResult->dominant_dimensions,
                'percentages' => $testResult->dimension_percentages,
            ]);

            if ($analysis) {
                $testResult->update([
                    'ai_analysis' => $analysis['analysis'] ?? null,
                    'cultural_profile' => $analysis['cultural_profile'] ?? null,
                    'strengths' => $analysis['strengths'] ?? null,
                    'challenges' => $analysis['challenges'] ?? null,
                    'ideal_environments' => $analysis['ideal_environments'] ?? null,
                    'recommendations' => $analysis['recommendations'] ?? null,
                    'status' => 'analyzed',
                ]);

                Log::info('Perfil Culture Fit analisado com sucesso pela IA', [
                    'test_result_id' => $testResult->id,
                    'testee_name' => $testResult->testee_name,
                ]);
            } else {
                // Se não conseguiu analisar, marca como completed sem análise
                $testResult->update(['status' => 'completed']);

                Log::warning('Análise Culture Fit retornou vazia', [
                    'test_result_id' => $testResult->id,
                ]);
            }
        } catch (\Exception $e) {
            // Em caso de erro, marca como completed sem análise
            $testResult->update(['status' => 'completed']);

            Log::error('Erro ao analisar perfil Culture Fit com IA', [
                'test_result_id' => $testResult->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function downloadPdf($id)
    {
        try {
            $result = CultureFitResult::with('user')->findOrFail($id);

            if (!$this->userCanAccessResult($result->user_id, $result->testee_email, $result)) {
                return response()->json(['success' => false, 'message' => 'Acesso não autorizado.'], 403);
            }

            $percentages = $result->dimension_percentages;

            $pdf = Pdf::loadView('pdf.culture-fit-result', [
                'result' => $result,
                'percentages' => $percentages,
            ]);

            $filename = 'CultureFit_' . ($result->testee_name ? str_replace(' ', '_', $result->testee_name) : 'Resultado') . '_' . date('Y-m-d') . '.pdf';

            TestAuditLog::record(
                TestAuditLog::TYPE_CULTURE_FIT,
                TestAuditLog::ACTION_PDF_DOWNLOADED,
                'result',
                $result->id
            );

            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('Erro ao gerar PDF Culture Fit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar PDF.',
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $result = CultureFitResult::findOrFail($id);

            if (!$this->userCanAccessByUserId($result->user_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resultado não encontrado.',
                ], 404);
            }

            $resultId = $result->id;
            $result->delete();

            TestAuditLog::record(
                TestAuditLog::TYPE_CULTURE_FIT,
                TestAuditLog::ACTION_RESULT_DELETED,
                'result',
                $resultId
            );

            return response()->json([
                'success' => true,
                'message' => 'Teste Culture Fit excluído com sucesso.',
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao excluir teste Culture Fit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir teste.',
            ], 500);
        }
    }
}
