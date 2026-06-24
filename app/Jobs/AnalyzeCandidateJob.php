<?php

namespace App\Jobs;

use App\Models\Candidate;
use App\Services\OpenAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeCandidateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(
        public readonly int $candidateId
    ) {}

    public function handle(OpenAIService $openAIService): void
    {
        $candidate = Candidate::find($this->candidateId);

        if (!$candidate) {
            Log::warning('AnalyzeCandidateJob: candidato não encontrado', [
                'candidate_id' => $this->candidateId,
            ]);
            return;
        }

        try {
            if (!$candidate->file_path) {
                $candidate->update(['status' => 'error']);
                return;
            }

            $fullPath = storage_path('app/public/' . $candidate->file_path);

            if (!file_exists($fullPath)) {
                $candidate->update(['status' => 'error']);
                return;
            }

            $resumeText = $openAIService->extractTextFromFile($fullPath);

            if (!$resumeText) {
                $candidate->update(['status' => 'error']);
                return;
            }

            $analysis = $openAIService->analyzeCandidateResume($resumeText);

            if (!$analysis) {
                $candidate->update(['status' => 'error']);
                return;
            }

            $candidate->update([
                'city'                   => $analysis['city'] ?? null,
                'professional_area'      => $analysis['professional_area'] ?? 'Não identificada',
                'qualifications_summary' => $analysis['qualifications_summary'] ?? 'Resumo não disponível no momento',
                'status'                 => 'analyzed',
            ]);
        } catch (\Throwable $e) {
            Log::error('AnalyzeCandidateJob falhou', [
                'candidate_id' => $this->candidateId,
                'error'        => $e->getMessage(),
            ]);

            $candidate->update(['status' => 'error']);
            throw $e;
        }
    }
}
