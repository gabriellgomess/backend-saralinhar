<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppJobApplication;
use App\Models\Candidate;
use App\Models\InterviewAttempt;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AppInterviewController extends Controller
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Avalia a resposta em texto enviada pelo app.
     */
    public function evaluateText(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:1000',
            'answer' => 'required|string|max:10000',
        ]);

        $evaluation = $this->openAIService->evaluateInterviewAnswer(
            $request->question,
            $request->answer
        );

        if (!$evaluation) {
            return response()->json([
                'message' => 'Não foi possível obter a avaliação da IA no momento. Tente novamente.',
            ], 502);
        }

        // Cria a tentativa no banco de dados
        $attempt = InterviewAttempt::create([
            'user_id' => $request->user()->id,
            'question' => $request->question,
            'answer' => $request->answer,
            'score' => $evaluation['score'] ?? 0,
            'feedback' => $evaluation,
            'source' => 'text',
        ]);

        return response()->json([
            'message' => 'Avaliação concluída com sucesso',
            'data' => [
                'attempt' => $attempt,
                'evaluation' => $evaluation
            ]
        ], 200);
    }

    /**
     * Avalia a resposta em áudio enviada pelo app.
     */
    public function evaluateAudio(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:1000',
            'audio' => 'required|file|max:15360', // máx 15MB
        ]);

        try {
            // Salva áudio temporariamente
            $path = $request->file('audio')->store('temp_audios');
            $fullPath = Storage::path($path);

            // Transcreve áudio
            $transcription = $this->openAIService->transcribeAudio($fullPath);

            // Deleta arquivo temporário
            Storage::delete($path);

            if (empty(trim($transcription))) {
                return response()->json([
                    'message' => 'Não foi possível compreender o áudio enviado. Certifique-se de que falou claramente.',
                ], 422);
            }

            // Avalia resposta transcrita
            $evaluation = $this->openAIService->evaluateInterviewAnswer(
                $request->question,
                $transcription
            );

            if (!$evaluation) {
                return response()->json([
                    'message' => 'Não foi possível obter a avaliação da IA no momento. Tente novamente.',
                ], 502);
            }

            // Cria a tentativa no banco de dados
            $attempt = InterviewAttempt::create([
                'user_id' => $request->user()->id,
                'question' => $request->question,
                'answer' => $transcription,
                'score' => $evaluation['score'] ?? 0,
                'feedback' => $evaluation,
                'source' => 'audio',
            ]);

            return response()->json([
                'message' => 'Avaliação concluída com sucesso',
                'data' => [
                    'attempt' => $attempt,
                    'evaluation' => $evaluation,
                    'transcription' => $transcription
                ]
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Erro no processamento do áudio do app: ' . $e->getMessage());
            return response()->json([
                'message' => 'Falha ao processar o arquivo de áudio. Tente novamente.',
            ], 500);
        }
    }

    /**
     * Retorna estatísticas de uso para o dashboard do candidato.
     */
    public function dashboard(Request $request)
    {
        $userId = $request->user()->id;

        $attemptsCount = InterviewAttempt::where('user_id', $userId)->count();
        $avgScore = InterviewAttempt::where('user_id', $userId)->avg('score');
        $applicationsCount = AppJobApplication::where('user_id', $userId)->count();

        // Calcula a porcentagem de conclusão do perfil do candidato
        $candidate = Candidate::where('user_id', $userId)->first();
        $profileCompletion = 0;

        if ($candidate) {
            $fields = [
                'professional_area', 'desired_role', 'city', 'work_mode',
                'qualifications_summary', 'education', 'skills', 'salary_expectation', 'summary'
            ];
            $filled = 0;
            foreach ($fields as $field) {
                if (!empty($candidate->$field)) {
                    $filled++;
                }
            }
            $profileCompletion = (int) round(($filled / count($fields)) * 100);
        }

        return response()->json([
            'message' => 'Métricas do dashboard recuperadas',
            'data' => [
                'profile_completion' => $profileCompletion,
                'attempts_count' => $attemptsCount,
                'avg_score' => $avgScore ? (int) round($avgScore) : 0,
                'applications_count' => $applicationsCount,
            ]
        ], 200);
    }

    /**
     * Retorna o histórico de tentativas do candidato.
     */
    public function history(Request $request)
    {
        $attempts = InterviewAttempt::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'message' => 'Histórico de tentativas recuperado com sucesso',
            'data' => $attempts
        ], 200);
    }
}
