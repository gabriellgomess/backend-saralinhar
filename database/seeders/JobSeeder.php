<?php

namespace Database\Seeders;

use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JobSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        if (!$user) {
            $this->command->error('Nenhum usuário encontrado. Execute UserSeeder primeiro.');
            return;
        }

        // Limpar vagas existentes antes de importar
        Job::truncate();
        $this->command->info('Banco de vagas limpo.');

        // Importar vagas do JSON padronizado
        $this->importJobsFromJson($user->id);
    }

    /**
     * Importa vagas do arquivo JSON
     */
    private function importJobsFromJson($userId): void
    {
        // Importar vagas do arquivo padronizado
        $standardizedJobsPath = base_path('../vagas_padronizadas.json');

        if (file_exists($standardizedJobsPath)) {
            $this->importStandardizedJobs($userId, $standardizedJobsPath);
        } else {
            $this->command->error('Arquivo vagas_padronizadas.json não encontrado. Execute o script de padronização primeiro.');
        }
    }

    /**
     * Importa as vagas padronizadas
     */
    private function importStandardizedJobs($userId, $jsonPath): void
    {
        $jsonContent = file_get_contents($jsonPath);
        $jobs = json_decode($jsonContent, true);

        if (!$jobs) {
            $this->command->error('Erro ao decodificar o arquivo de vagas padronizadas.');
            return;
        }

        $this->command->info('Iniciando importação de ' . count($jobs) . ' vagas padronizadas...');

        $imported = 0;
        $errors = 0;

        foreach ($jobs as $jobData) {
            try {
                // Garantir que o user_id seja o correto
                $jobData['user_id'] = $userId;

                // Remover timestamps se existirem (serão criados automaticamente)
                unset($jobData['created_at'], $jobData['updated_at']);

                Job::create($jobData);
                $imported++;

                if ($imported % 20 == 0) {
                    $this->command->info("Importadas {$imported} vagas...");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->command->error("Erro ao importar vaga '{$jobData['title']}': " . $e->getMessage());
            }
        }

        $this->command->info("Importação concluída!");
        $this->command->info("Vagas importadas: {$imported}");
        if ($errors > 0) {
            $this->command->warn("Erros encontrados: {$errors}");
        }
    }
}
