<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            UserSeeder::class,
            // Motor Genérico de Testes — instrumentos comportamentais
            AssessmentCompetenciasSeeder::class,
            AssessmentComunicacaoSeeder::class,
            AssessmentEstiloTrabalhoSeeder::class,
            AssessmentSjtSeeder::class,
            // Novos instrumentos
            AssessmentBigFiveSeeder::class,
            AssessmentInteligenciaEmocionalSeeder::class,
            AssessmentEstilosLiderancaSeeder::class,
            AssessmentPerfilMotivacaoSeeder::class,
            AssessmentResilienciaSeeder::class,
            AssessmentValoresTrabalhoSeeder::class,
            AssessmentPerfilComportamentalSeeder::class,
        ]);
    }
}
