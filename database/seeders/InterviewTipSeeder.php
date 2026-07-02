<?php

namespace Database\Seeders;

use App\Models\InterviewTip;
use Illuminate\Database\Seeder;

class InterviewTipSeeder extends Seeder
{
    public function run(): void
    {
        $tips = [
            ['category' => 'curriculo', 'text' => 'Mantenha seu currículo simples, atualizado e fácil de ler.'],
            ['category' => 'curriculo', 'text' => 'Destaque resultados com números sempre que possível, não apenas atividades.'],
            ['category' => 'curriculo', 'text' => 'Adapte o currículo para cada vaga, priorizando o que a empresa procura.'],
            ['category' => 'entrevista', 'text' => 'Evite respostas muito longas. Seja claro, objetivo e profissional.'],
            ['category' => 'entrevista', 'text' => 'Antes da entrevista, pesquise sobre a empresa.'],
            ['category' => 'entrevista', 'text' => 'Tenha exemplos reais para falar sobre suas experiências.'],
            ['category' => 'entrevista', 'text' => 'Ao falar de pontos fracos, mostre também o que está fazendo para melhorar.'],
            ['category' => 'linkedin', 'text' => 'Use uma foto profissional e um título claro com sua área de atuação.'],
            ['category' => 'linkedin', 'text' => 'Interaja com conteúdos da sua área para ganhar visibilidade com recrutadores.'],
            ['category' => 'whatsapp', 'text' => 'Responda recrutadores com agilidade, cordialidade e sem gírias.'],
            ['category' => 'whatsapp', 'text' => 'Evite áudios longos: prefira mensagens de texto curtas e objetivas.'],
            ['category' => 'comportamento', 'text' => 'Chegue com antecedência e cuide da postura desde a recepção.'],
            ['category' => 'comportamento', 'text' => 'Demonstre interesse fazendo perguntas sobre a vaga e a empresa.'],
        ];

        foreach ($tips as $index => $tip) {
            InterviewTip::updateOrCreate(
                ['text' => $tip['text']],
                [
                    'category' => $tip['category'],
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }
    }
}
