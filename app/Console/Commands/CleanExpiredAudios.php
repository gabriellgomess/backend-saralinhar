<?php

namespace App\Console\Commands;

use App\Models\CandidateReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanExpiredAudios extends Command
{
    protected $signature = 'reports:clean-expired-audios';

    protected $description = 'Remove áudios de pareceres que passaram do prazo de expiração (1 semana)';

    public function handle()
    {
        $expired = CandidateReport::whereNotNull('audio_path')
            ->whereNotNull('audio_expires_at')
            ->where('audio_expires_at', '<', now())
            ->get();

        $count = $expired->count();

        if ($count === 0) {
            $this->info('Nenhum áudio expirado encontrado.');
            return;
        }

        foreach ($expired as $report) {
            if (Storage::disk('local')->exists($report->audio_path)) {
                Storage::disk('local')->delete($report->audio_path);
            }
            $report->update([
                'audio_path' => null,
                'audio_expires_at' => null,
            ]);
        }

        $this->info("Removidos {$count} áudios expirados.");
    }
}
