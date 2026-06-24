<?php

namespace App\Console\Commands;

use App\Models\DiscTestToken;
use Illuminate\Console\Command;

class CleanExpiredDiscTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'disc:clean-expired-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marca tokens DISC expirados como expirados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredTokens = DiscTestToken::where('status', 'active')
            ->where('expires_at', '<', now())
            ->get();

        $count = $expiredTokens->count();

        if ($count === 0) {
            $this->info('Nenhum token expirado encontrado.');
            return;
        }

        foreach ($expiredTokens as $token) {
            $token->update(['status' => 'expired']);
        }

        $this->info("Marcados {$count} tokens como expirados.");
    }
}
