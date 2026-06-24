<?php

namespace Database\Seeders;

use App\Models\AssessmentDimension;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentTest;
use Illuminate\Database\Seeder;

/**
 * Inventário de Perfil de Motivação — Teoria de McClelland
 * 3 dimensões × 10 itens = 30 questões. Escala Likert 1-5.
 * Mede orientação para Realização, Afiliação e Poder/Influência.
 */
class AssessmentPerfilMotivacaoSeeder extends Seeder
{
    public function run(): void
    {
        $test = AssessmentTest::updateOrCreate(
            ['slug' => 'perfil-de-motivacao'],
            [
                'name'        => 'Inventário de Perfil de Motivação',
                'description' => 'Identifica os principais motivadores profissionais com base na Teoria das Necessidades de McClelland: orientação para Realização, Afiliação e Poder/Influência. Auxilia no alinhamento de funções, planos de carreira e estratégias de retenção.',
                'type'        => 'likert',
                'version'     => '1.0',
                'is_active'   => true,
                'disclaimer'  => 'Este instrumento mapeia tendências motivacionais do respondente no contexto profissional. Os resultados são baseados nas autopercepções no momento da aplicação e não devem ser utilizados como único critério de seleção ou avaliação. Devem ser analisados em conjunto com entrevistas e demais informações do processo.',
            ]
        );

        $dimensions = [
            ['slug' => 'realizacao',  'name' => 'Motivação para Realização',        'order' => 1, 'description' => 'Impulso para atingir metas desafiadoras, buscar excelência e superar padrões de desempenho.'],
            ['slug' => 'afiliacao',   'name' => 'Motivação para Afiliação',          'order' => 2, 'description' => 'Necessidade de criar e manter relacionamentos positivos, harmonia e sentido de pertencimento.'],
            ['slug' => 'poder',       'name' => 'Motivação para Poder e Influência', 'order' => 3, 'description' => 'Desejo de impactar, influenciar e liderar pessoas e decisões na organização.'],
        ];

        $dimModels = [];
        foreach ($dimensions as $d) {
            $dim = AssessmentDimension::updateOrCreate(
                ['assessment_test_id' => $test->id, 'slug' => $d['slug']],
                ['name' => $d['name'], 'description' => $d['description'], 'weight' => 1.0, 'order' => $d['order']]
            );
            $dimModels[$d['slug']] = $dim;
        }

        // [dimensão, enunciado, is_reverse]
        $questions = [
            // Realização
            ['realizacao', 'Estabeleço metas ambiciosas para mim mesmo e trabalho com persistência para alcançá-las.', false],
            ['realizacao', 'Sinto satisfação profunda quando supero um desafio difícil por mérito próprio.', false],
            ['realizacao', 'Prefiro tarefas que me exijam esforço e aprendizado a atividades rotineiras e previsíveis.', false],
            ['realizacao', 'Monitoro constantemente meu desempenho em busca de oportunidades de melhoria.', false],
            ['realizacao', 'O reconhecimento externo é mais importante para mim do que a sensação interna de ter feito um bom trabalho.', true],
            ['realizacao', 'Fico energizado quando assumo projetos complexos que poucos se dispõem a fazer.', false],
            ['realizacao', 'Me incomoda profundamente quando entrego algo abaixo do padrão que considero adequado.', false],
            ['realizacao', 'Busco dominar novas habilidades e expandir minha competência profissional continuamente.', false],
            ['realizacao', 'Prefiro resultados mediocres com menor esforço a resultados excelentes que demandem mais de mim.', true],
            ['realizacao', 'Sinto que meu maior motivador é o crescimento e a evolução constante no trabalho.', false],

            // Afiliação
            ['afiliacao', 'O ambiente de trabalho e as relações com colegas têm grande peso na minha satisfação profissional.', false],
            ['afiliacao', 'Prefiro trabalhar em equipe a trabalhar sozinho, pois encontro significado nas conexões com as pessoas.', false],
            ['afiliacao', 'Me preocupo genuinamente com o bem-estar e o humor das pessoas ao meu redor no trabalho.', false],
            ['afiliacao', 'Fico desconfortável quando há conflito ou tensão no grupo, mesmo que não me envolva diretamente.', false],
            ['afiliacao', 'Investir no fortalecimento das relações dentro da equipe é tão importante quanto atingir resultados.', false],
            ['afiliacao', 'Sinto que minhas melhores contribuições emergem quando estou em um ambiente de confiança e colaboração.', false],
            ['afiliacao', 'O isolamento social no trabalho — como home office sem contato — me afeta negativamente.', false],
            ['afiliacao', 'Me satisfaz saber que sou querido e respeitado pelas pessoas com quem trabalho.', false],
            ['afiliacao', 'Prefiro tomar decisões impopulares sem hesitar a preservar relações que possam me prejudicar profissionalmente.', true],
            ['afiliacao', 'Tenho dificuldade em priorizar resultados quando sei que isso pode impactar negativamente alguém da equipe.', false],

            // Poder e Influência
            ['poder', 'Sinto satisfação quando consigo influenciar decisões importantes da organização.', false],
            ['poder', 'Me sinto motivado quando tenho a oportunidade de liderar pessoas ou projetos relevantes.', false],
            ['poder', 'Gosto de ter autoridade e responsabilidade sobre resultados de alto impacto.', false],
            ['poder', 'Busco posições que me permitam exercer influência sobre estratégias e rumos da empresa.', false],
            ['poder', 'Me realizo quando consigo desenvolver pessoas e vê-las crescendo profissionalmente sob minha orientação.', false],
            ['poder', 'Prefiro papéis de execução a papéis de liderança ou coordenação.', true],
            ['poder', 'Fico frustrado quando decisões importantes são tomadas sem a minha participação.', false],
            ['poder', 'Meu objetivo de carreira inclui ocupar posições de maior liderança e impacto organizacional.', false],
            ['poder', 'Me sinto realizado quando minha opinião é levada em conta e muda o curso das coisas.', false],
            ['poder', 'Não me interessa ter poder formal — prefiro contribuir de forma técnica sem responsabilidades de gestão.', true],
        ];

        foreach ($questions as $order => [$slug, $statement, $reverse]) {
            AssessmentQuestion::updateOrCreate(
                ['assessment_test_id' => $test->id, 'code' => 'PM' . str_pad($order + 1, 2, '0', STR_PAD_LEFT)],
                [
                    'assessment_dimension_id' => $dimModels[$slug]->id,
                    'statement'               => $statement,
                    'question_type'           => 'likert',
                    'scale_min'               => 1,
                    'scale_max'               => 5,
                    'is_reverse'              => $reverse,
                    'weight'                  => 1.0,
                    'order'                   => $order + 1,
                ]
            );
        }
    }
}
