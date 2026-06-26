<?php

namespace App\Console\Commands;

use App\Models\Job;
use App\Models\FinancialTransaction;
use Illuminate\Console\Command;

class BackfillJobBilling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'financial:backfill-jobs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria lançamentos de faturamento (rascunho) para todas as vagas ativas que ainda não possuem faturamento associado';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando o processo de faturamento das vagas ativas...');

        // Busca todas as vagas ativas e aprovadas
        $activeJobs = Job::where('is_active', true)
            ->where(function($q) {
                $q->where('approval_status', 'approved')
                  ->orWhereNull('approval_status');
            })
            ->get();

        $totalJobs = $activeJobs->count();
        $this->info("Encontradas {$totalJobs} vagas ativas no sistema.");

        $createdCount = 0;
        $skippedCount = 0;
        $noClientCount = 0;

        foreach ($activeJobs as $job) {
            // 1. Verifica se a transação financeira já existe para esta vaga
            if (FinancialTransaction::where('job_id', $job->id)->exists()) {
                $skippedCount++;
                continue;
            }

            try {
                // 2. Tenta auto-criar o faturamento para a vaga
                $transaction = FinancialTransaction::autoCreateForJob($job);
                if ($transaction) {
                    $this->line("Faturamento criado para a vaga: ID {$job->id} - {$job->title}");
                    $createdCount++;
                } else {
                    $this->warn("Aviso: Vaga ID {$job->id} ({$job->title}) não pôde ser faturada (nenhum cliente RecruitmentClient correspondente a '{$job->company}' encontrado).");
                    $noClientCount++;
                }
            } catch (\Exception $e) {
                $this->error("Erro ao processar vaga ID {$job->id} ({$job->title}): " . $e->getMessage());
            }
        }

        $this->info("Processamento concluído!");
        $this->info("- Criados: {$createdCount}");
        $this->info("- Ignorados (já possuíam faturamento): {$skippedCount}");
        $this->info("- Ignorados (cliente não encontrado): {$noClientCount}");
    }
}
