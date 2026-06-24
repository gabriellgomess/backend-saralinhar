<?php

namespace Database\Seeders;

use App\Models\AssessmentDimension;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentTest;
use Illuminate\Database\Seeder;

/**
 * Inventário de Inteligência Emocional
 * Baseado no modelo de Goleman (5 domínios).
 * 5 dimensões × 8 itens = 40 questões. Escala Likert 1-5.
 */
class AssessmentInteligenciaEmocionalSeeder extends Seeder
{
    public function run(): void
    {
        $test = AssessmentTest::updateOrCreate(
            ['slug' => 'inteligencia-emocional'],
            [
                'name'        => 'Inventário de Inteligência Emocional',
                'description' => 'Avalia cinco domínios da inteligência emocional: autoconsciência, autorregulação, motivação intrínseca, empatia e habilidades sociais. Essencial para funções que envolvem liderança, atendimento, negociação e trabalho em equipe.',
                'type'        => 'likert',
                'version'     => '1.0',
                'is_active'   => true,
                'disclaimer'  => 'Este instrumento é uma ferramenta de apoio para compreensão de competências emocionais no contexto profissional. Os resultados são baseados nas respostas fornecidas e não constituem diagnóstico psicológico ou laudo clínico. Devem ser interpretados por profissional habilitado e analisados junto com outras fontes de informação do processo.',
            ]
        );

        $dimensions = [
            ['slug' => 'autoconsciencia',    'name' => 'Autoconsciência',       'order' => 1, 'description' => 'Capacidade de reconhecer e compreender as próprias emoções, forças, limitações e o impacto do seu comportamento nos outros.'],
            ['slug' => 'autoregulacao',      'name' => 'Autorregulação',        'order' => 2, 'description' => 'Capacidade de gerenciar impulsos, adaptar comportamentos e manter o controle emocional em situações desafiadoras.'],
            ['slug' => 'motivacao-intrinseca','name' => 'Motivação Intrínseca', 'order' => 3, 'description' => 'Impulso interno para buscar objetivos com energia e persistência, além de recompensas externas.'],
            ['slug' => 'empatia',            'name' => 'Empatia',               'order' => 4, 'description' => 'Habilidade de perceber, compreender e considerar as emoções e perspectivas alheias.'],
            ['slug' => 'habilidades-sociais','name' => 'Habilidades Sociais',   'order' => 5, 'description' => 'Competência para construir relacionamentos, influenciar pessoas e trabalhar colaborativamente.'],
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
            // Autoconsciência
            ['autoconsciencia', 'Consigo identificar com precisão o que estou sentindo no momento em que a emoção ocorre.', false],
            ['autoconsciencia', 'Reconheço como minhas emoções influenciam minhas decisões e comportamentos.', false],
            ['autoconsciencia', 'Tenho clareza sobre meus pontos fortes e limitações no ambiente de trabalho.', false],
            ['autoconsciencia', 'Sei qual tipo de situação costuma me deixar irritado, ansioso ou inseguro.', false],
            ['autoconsciencia', 'Percebo quando estou agindo de forma que contraria meus próprios valores.', false],
            ['autoconsciencia', 'Peço feedback regularmente para entender como sou percebido pelos outros.', false],
            ['autoconsciencia', 'Tenho dificuldade em nomear o que estou sentindo quando estou emocionalmente ativado.', true],
            ['autoconsciencia', 'Tenho consciência do impacto que meu humor e comportamento exercem sobre as pessoas ao redor.', false],

            // Autorregulação
            ['autoregulacao', 'Consigo manter a calma e pensar com clareza mesmo sob pressão intensa.', false],
            ['autoregulacao', 'Quando algo me irrita, consigo pausar e escolher como reagir antes de agir.', false],
            ['autoregulacao', 'Adapto minha forma de agir quando percebo que minha abordagem não está funcionando.', false],
            ['autoregulacao', 'Consigo trabalhar com qualidade mesmo em situações de incerteza ou ambiguidade.', false],
            ['autoregulacao', 'Quando cometo um erro, consigo reconhecer sem me punir excessivamente.', false],
            ['autoregulacao', 'Mantenho meu nível de desempenho mesmo quando estou passando por dificuldades pessoais.', false],
            ['autoregulacao', 'Reajo de forma impulsiva quando contrariado ou criticado.', true],
            ['autoregulacao', 'Deixo que emoções como medo ou frustração me impeçam de agir quando necessário.', true],

            // Motivação Intrínseca
            ['motivacao-intrinseca', 'Me dedico com afinco mesmo em tarefas que não oferecem recompensa imediata.', false],
            ['motivacao-intrinseca', 'Estabeleço metas desafiadoras para mim mesmo e me empenho em alcançá-las.', false],
            ['motivacao-intrinseca', 'Mantenho o entusiasmo pelo meu trabalho mesmo diante de obstáculos repetidos.', false],
            ['motivacao-intrinseca', 'Sinto orgulho genuíno quando entrego algo com qualidade, independentemente do reconhecimento externo.', false],
            ['motivacao-intrinseca', 'Busco continuamente maneiras de melhorar meu desempenho e competências.', false],
            ['motivacao-intrinseca', 'O sentido e o propósito do meu trabalho são mais motivadores do que o salário.', false],
            ['motivacao-intrinseca', 'Preciso de estímulos externos constantes para manter meu engajamento.', true],
            ['motivacao-intrinseca', 'Me recupero rapidamente após fracassos e retorno com mais determinação.', false],

            // Empatia
            ['empatia', 'Percebo o estado emocional das pessoas ao meu redor mesmo quando elas não verbalizam.', false],
            ['empatia', 'Consigo me colocar na perspectiva de alguém com visão muito diferente da minha.', false],
            ['empatia', 'Presto atenção genuína ao que as pessoas dizem, sem pensar em como responderei.', false],
            ['empatia', 'Noto quando alguém está desconfortável e ajusto minha abordagem para criar mais segurança.', false],
            ['empatia', 'Considero o impacto emocional das minhas decisões sobre as pessoas envolvidas.', false],
            ['empatia', 'Tenho sensibilidade para perceber dinâmicas não ditas em grupos ou equipes.', false],
            ['empatia', 'Tenho dificuldade em compreender por que as pessoas reagem emocionalmente a certas situações.', true],
            ['empatia', 'Me interesso genuinamente pelo que motiva e preocupa as pessoas com quem trabalho.', false],

            // Habilidades Sociais
            ['habilidades-sociais', 'Construo relacionamentos de confiança com facilidade, tanto com pares quanto com líderes.', false],
            ['habilidades-sociais', 'Consigo influenciar pessoas sem precisar de autoridade formal para isso.', false],
            ['habilidades-sociais', 'Gerencio conflitos de forma construtiva, buscando soluções que atendam às partes envolvidas.', false],
            ['habilidades-sociais', 'Comunico ideias complexas de maneira clara e adaptada ao meu interlocutor.', false],
            ['habilidades-sociais', 'Sei quando e como dar um feedback difícil de forma que seja bem recebido.', false],
            ['habilidades-sociais', 'Cataliso o engajamento de grupos e consigo mobilizar pessoas em torno de objetivos comuns.', false],
            ['habilidades-sociais', 'Tenho dificuldade em manter relacionamentos profissionais de longo prazo.', true],
            ['habilidades-sociais', 'Adapto meu estilo de comunicação conforme a pessoa e o contexto da interação.', false],
        ];

        foreach ($questions as $order => [$slug, $statement, $reverse]) {
            AssessmentQuestion::updateOrCreate(
                ['assessment_test_id' => $test->id, 'code' => 'IE' . str_pad($order + 1, 2, '0', STR_PAD_LEFT)],
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
