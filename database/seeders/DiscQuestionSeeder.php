<?php

namespace Database\Seeders;

use App\Models\DiscQuestion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DiscQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questions = [
            [
                'question_number' => 1,
                'statement_d' => 'Gosto de desafios e competições',
                'statement_i' => 'Gosto de me relacionar com pessoas e trabalhar em equipe',
                'statement_s' => 'Prefiro ambientes calmos e previsíveis',
                'statement_c' => 'Valorizo precisão e qualidade nos detalhes',
            ],
            [
                'question_number' => 2,
                'statement_d' => 'Tomo decisões rapidamente',
                'statement_i' => 'Sou entusiasta e otimista',
                'statement_s' => 'Sou paciente e bom ouvinte',
                'statement_c' => 'Sou analítico e metódico',
            ],
            [
                'question_number' => 3,
                'statement_d' => 'Gosto de liderar e estar no controle',
                'statement_i' => 'Gosto de persuadir e influenciar outras pessoas',
                'statement_s' => 'Prefiro apoiar e ajudar os outros',
                'statement_c' => 'Prefiro seguir procedimentos e padrões estabelecidos',
            ],
            [
                'question_number' => 4,
                'statement_d' => 'Sou direto e objetivo ao me comunicar',
                'statement_i' => 'Sou expressivo e comunicativo',
                'statement_s' => 'Sou calmo e raramente me altero',
                'statement_c' => 'Sou reservado e preciso nas minhas palavras',
            ],
            [
                'question_number' => 5,
                'statement_d' => 'Gosto de assumir riscos calculados',
                'statement_i' => 'Gosto de inovar e tentar coisas novas',
                'statement_s' => 'Prefiro manter as coisas como estão',
                'statement_c' => 'Gosto de planejar antes de agir',
            ],
            [
                'question_number' => 6,
                'statement_d' => 'Sou assertivo e determinado',
                'statement_i' => 'Sou sociável e amigável',
                'statement_s' => 'Sou leal e confiável',
                'statement_c' => 'Sou cauteloso e cuidadoso',
            ],
            [
                'question_number' => 7,
                'statement_d' => 'Prefiro trabalhar com metas e resultados',
                'statement_i' => 'Prefiro trabalhar com pessoas e ideias',
                'statement_s' => 'Prefiro trabalhar em um ritmo constante',
                'statement_c' => 'Prefiro trabalhar com dados e fatos',
            ],
            [
                'question_number' => 8,
                'statement_d' => 'Sou competitivo e focado em vencer',
                'statement_i' => 'Sou motivador e inspirador',
                'statement_s' => 'Sou colaborativo e evito conflitos',
                'statement_c' => 'Sou sistemático e organizado',
            ],
            [
                'question_number' => 9,
                'statement_d' => 'Prefiro ambientes dinâmicos e com mudanças',
                'statement_i' => 'Prefiro ambientes criativos e estimulantes',
                'statement_s' => 'Prefiro ambientes harmoniosos e estáveis',
                'statement_c' => 'Prefiro ambientes estruturados e organizados',
            ],
            [
                'question_number' => 10,
                'statement_d' => 'Valorizo independência e autonomia',
                'statement_i' => 'Valorizo reconhecimento e popularidade',
                'statement_s' => 'Valorizo lealdade e segurança',
                'statement_c' => 'Valorizo exatidão e qualidade',
            ],
            [
                'question_number' => 11,
                'statement_d' => 'Sou impaciente com ineficiência',
                'statement_i' => 'Sou impaciente com rotinas monótonas',
                'statement_s' => 'Sou impaciente com mudanças bruscas',
                'statement_c' => 'Sou impaciente com erros e imprecisões',
            ],
            [
                'question_number' => 12,
                'statement_d' => 'Enfrento problemas de forma direta',
                'statement_i' => 'Tento resolver problemas através do diálogo',
                'statement_s' => 'Evito confrontos e busco harmonia',
                'statement_c' => 'Analiso problemas cuidadosamente antes de agir',
            ],
            [
                'question_number' => 13,
                'statement_d' => 'Prefiro dar ordens e delegar tarefas',
                'statement_i' => 'Prefiro compartilhar ideias e colaborar',
                'statement_s' => 'Prefiro receber instruções claras',
                'statement_c' => 'Prefiro seguir procedimentos definidos',
            ],
            [
                'question_number' => 14,
                'statement_d' => 'Sou orientado para a ação',
                'statement_i' => 'Sou orientado para as pessoas',
                'statement_s' => 'Sou orientado para o processo',
                'statement_c' => 'Sou orientado para a qualidade',
            ],
            [
                'question_number' => 15,
                'statement_d' => 'Gosto de fazer as coisas do meu jeito',
                'statement_i' => 'Gosto de fazer as coisas de forma criativa',
                'statement_s' => 'Gosto de fazer as coisas da forma tradicional',
                'statement_c' => 'Gosto de fazer as coisas da forma correta',
            ],
            [
                'question_number' => 16,
                'statement_d' => 'Sou motivado por desafios e conquistas',
                'statement_i' => 'Sou motivado por interação social e diversão',
                'statement_s' => 'Sou motivado por estabilidade e segurança',
                'statement_c' => 'Sou motivado por perfeição e excelência',
            ],
            [
                'question_number' => 17,
                'statement_d' => 'Prefiro trabalhar em projetos de curto prazo',
                'statement_i' => 'Prefiro trabalhar com variedade de tarefas',
                'statement_s' => 'Prefiro trabalhar em projetos de longo prazo',
                'statement_c' => 'Prefiro trabalhar com especialização',
            ],
            [
                'question_number' => 18,
                'statement_d' => 'Sou persistente e não desisto facilmente',
                'statement_i' => 'Sou adaptável e flexível',
                'statement_s' => 'Sou consistente e previsível',
                'statement_c' => 'Sou minucioso e detalhista',
            ],
            [
                'question_number' => 19,
                'statement_d' => 'Valorizo eficiência e resultados',
                'statement_i' => 'Valorizo relacionamentos e networking',
                'statement_s' => 'Valorizo cooperação e trabalho em equipe',
                'statement_c' => 'Valorizo precisão e conformidade',
            ],
            [
                'question_number' => 20,
                'statement_d' => 'Tenho dificuldade em aceitar fracassos',
                'statement_i' => 'Tenho dificuldade em lidar com críticas',
                'statement_s' => 'Tenho dificuldade em lidar com conflitos',
                'statement_c' => 'Tenho dificuldade em tomar decisões rápidas',
            ],
        ];

        foreach ($questions as $question) {
            DiscQuestion::create($question);
        }
    }
}
