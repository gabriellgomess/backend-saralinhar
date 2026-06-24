<?php

namespace App\Jobs;

use App\Models\BatchUploadFile;
use App\Models\Category;
use App\Services\OpenAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessResumeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Tentativas antes de mover para failed_jobs
     */
    public int $tries = 2;

    /**
     * Timeout por tentativa (extração de texto + chamada OpenAI)
     */
    public int $timeout = 120;

    public function __construct(
        public readonly int $batchUploadFileId
    ) {}

    public function handle(OpenAIService $openAIService): void
    {
        $file = BatchUploadFile::find($this->batchUploadFileId);

        if (!$file) {
            Log::warning('ProcessResumeJob: BatchUploadFile não encontrado', [
                'id' => $this->batchUploadFileId,
            ]);
            return;
        }

        $file->update(['status' => 'processing']);

        try {
            $fullPath = storage_path('app/public/' . $file->temp_path);

            if (!file_exists($fullPath)) {
                throw new \Exception("Arquivo temporário não encontrado: {$file->temp_path}");
            }

            // Extrai texto do PDF/DOCX
            $text = $openAIService->extractTextFromFile($fullPath);

            if (!$text) {
                throw new \Exception('Não foi possível ler o conteúdo do arquivo.');
            }

            // Extrai dados via IA (nome, email, telefone, categoria)
            $categories = Category::where('is_active', true)->get(['id', 'name'])->toArray();
            $data = $openAIService->extractContactInfo($text, $categories);

            if (!$data) {
                throw new \Exception('Não foi possível extrair dados do currículo via IA.');
            }

            $file->update([
                'name'              => $data['name'] ?? null,
                'email'             => $data['email'] ?? null,
                'phone'             => $data['phone'] ?? null,
                'professional_area' => $data['professional_area'] ?? null,
                'category_id'       => $data['category_id'] ?? null,
                'status'            => 'done',
                'error_message'     => null,
            ]);

        } catch (\Exception $e) {
            Log::error('ProcessResumeJob falhou', [
                'batch_upload_file_id' => $this->batchUploadFileId,
                'attempt'              => $this->attempts(),
                'error'                => $e->getMessage(),
            ]);

            // Mantém em fila para nova tentativa quando ainda houver retries disponíveis.
            if ($this->attempts() < $this->tries) {
                $file->update([
                    'status'        => 'queued',
                    'error_message' => $e->getMessage(),
                ]);
            }

            throw $e;
        } finally {
            // Sempre recalcula os contadores do batch pai
            optional($file->batch)->recalculate();
        }
    }

    /**
     * Chamado quando todas as tentativas se esgotam
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessResumeJob esgotou tentativas', [
            'batch_upload_file_id' => $this->batchUploadFileId,
            'error'                => $exception->getMessage(),
        ]);

        $file = BatchUploadFile::find($this->batchUploadFileId);
        if ($file) {
            $file->update([
                'status'        => 'error',
                'error_message' => 'Falhou após todas as tentativas: ' . $exception->getMessage(),
            ]);
            optional($file->batch)->recalculate();
        }
    }
}
