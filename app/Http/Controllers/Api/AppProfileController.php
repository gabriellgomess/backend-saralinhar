<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Perfil profissional do candidato no app EntrevistaPro AI.
 * O perfil é persistido no registro Candidate (mesmo banco de candidatos do site).
 */
class AppProfileController extends Controller
{
    public function show(Request $request)
    {
        $candidate = $this->resolveCandidate($request->user());

        return response()->json([
            'message' => 'Perfil recuperado com sucesso',
            'data' => $candidate,
        ], 200);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'professional_area' => 'nullable|string|max:255',
            'desired_role' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'work_mode' => 'nullable|in:presencial,hibrido,remoto',
            'qualifications_summary' => 'nullable|string|max:5000',
            'education' => 'nullable|string|max:2000',
            'skills' => 'nullable|string|max:2000',
            'salary_expectation' => 'nullable|string|max:100',
            'summary' => 'nullable|string|max:5000',
            'phone' => 'nullable|string|max:30',
        ]);

        $candidate = $this->resolveCandidate($request->user());
        $candidate->update($validated);

        return response()->json([
            'message' => 'Perfil atualizado com sucesso',
            'data' => $candidate->fresh(),
        ], 200);
    }

    /**
     * Analisa o currículo enviado pelo app e preenche os dados do perfil.
     */
    public function analyzeResume(Request $request)
    {
        $request->validate([
            'resume' => 'required|file|max:5120|mimes:pdf,docx,txt', // máx 5MB
        ]);

        try {
            $file = $request->file('resume');
            $path = $file->store('temp_resumes');
            $fullPath = \Illuminate\Support\Facades\Storage::path($path);

            $openAIService = app(\App\Services\OpenAIService::class);
            $text = $openAIService->extractTextFromFile($fullPath);

            \Illuminate\Support\Facades\Storage::delete($path);

            if (!$text || empty(trim($text))) {
                return response()->json([
                    'message' => 'Não foi possível extrair o texto do arquivo. Certifique-se de que é um documento legível.',
                ], 422);
            }

            $analysis = $openAIService->analyzeProfileResume($text);

            if (!$analysis) {
                return response()->json([
                    'message' => 'Não foi possível processar a análise do currículo. Tente novamente mais tarde.',
                ], 502);
            }

            $candidate = $this->resolveCandidate($request->user());
            $candidate->update([
                'professional_area' => $analysis['professional_area'] ?? $candidate->professional_area,
                'desired_role' => $analysis['desired_role'] ?? $candidate->desired_role,
                'city' => $analysis['city'] ?? $candidate->city,
                'qualifications_summary' => $analysis['qualifications_summary'] ?? $candidate->qualifications_summary,
                'education' => $analysis['education'] ?? $candidate->education,
                'skills' => $analysis['skills'] ?? $candidate->skills,
                'summary' => $analysis['summary'] ?? $candidate->summary,
            ]);

            return response()->json([
                'message' => 'Currículo analisado com sucesso!',
                'data' => $candidate->fresh(),
            ], 200);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Erro ao analisar currículo no app: ' . $e->getMessage());
            return response()->json([
                'message' => 'Falha ao processar o arquivo. Tente novamente.',
            ], 500);
        }
    }

    /**
     * Encontra (ou cria) o Candidate do usuário logado.
     * Vincula por user_id; se não houver, adota o registro existente com o
     * mesmo e-mail (evita duplicar candidatos já presentes no banco do site).
     */
    private function resolveCandidate(User $user): Candidate
    {
        $candidate = Candidate::where('user_id', $user->id)->first();

        if ($candidate) {
            return $candidate;
        }

        $candidate = Candidate::where('email', $user->email)
            ->whereNull('user_id')
            ->orderBy('id')
            ->first();

        if ($candidate) {
            $candidate->update(['user_id' => $user->id]);
            return $candidate;
        }

        return Candidate::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'pending',
        ]);
    }
}
