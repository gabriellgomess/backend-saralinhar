<?php

namespace Database\Seeders;

use App\Models\AssessmentDimension;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentTest;
use Illuminate\Database\Seeder;

/**
 * Perfil Comportamental — 5 Eixos
 *
 * Instrumento de autoria própria inspirado nos cinco eixos comportamentais
 * fundamentais: Risco, Extroversão, Paciência, Normas e Autocontrole.
 * 5 dimensões × 8 itens = 40 questões. Escala Likert 1-5.
 *
 * Cada dimensão conta com itens diretos e reversos para reduzir
 * viés de aquiescência e aumentar a confiabilidade do instrumento.
 */
class AssessmentPerfilComportamentalSeeder extends Seeder
{
    public function run(): void
    {
        $test = AssessmentTest::updateOrCreate(
            ['slug' => 'perfil-comportamental-5-eixos'],
            [
                'name'        => 'Perfil Comportamental — 5 Eixos',
                'description' => 'Mapeia cinco eixos comportamentais fundamentais — Risco, Extroversão, Paciência, Normas e Autocontrole — identificando o estilo natural de atuação do respondente em contextos profissionais. Apoia decisões de recrutamento, formação de equipes e desenvolvimento de lideranças.',
                'type'        => 'likert',
                'version'     => '1.0',
                'is_active'   => true,
                'disclaimer'  => 'Este instrumento não tem caráter eliminatório. Não há respostas certas ou erradas — responda com base no seu comportamento habitual, não no que acredita ser esperado pelo avaliador. Seus dados são tratados de forma confidencial e utilizados exclusivamente para fins de desenvolvimento profissional e processos seletivos.',
            ]
        );

        $dimensions = [
            [
                'slug'        => 'risco',
                'name'        => 'Risco',
                'order'       => 1,
                'weight'      => 1.0,
                'description' => 'Tendência a assumir desafios, tomar decisões sob incerteza, liderar iniciativas e manter posições diante de resistência. Pessoas com alto Risco são assertivas, competitivas e movidas por metas ambiciosas.',
                'questions'   => [
                    ['Tomo decisões com agilidade, mesmo diante de incertezas.',                           false],
                    ['Busco assumir desafios que outros costumam evitar.',                                   false],
                    ['Prefiro caminhos seguros a arriscar algo novo.',                                       true],
                    ['Quando enfrento obstáculos, persisto até encontrar uma solução.',                      false],
                    ['Fico paralisado diante de situações de alta pressão.',                                 true],
                    ['Tenho facilidade em liderar iniciativas mesmo sem ter todas as informações.',          false],
                    ['Prefiro que outros tomem a frente em situações de conflito.',                          true],
                    ['Defendo meu ponto de vista com firmeza mesmo quando há resistência.',                  false],
                ],
            ],
            [
                'slug'        => 'extroversao',
                'name'        => 'Extroversão',
                'order'       => 2,
                'weight'      => 1.0,
                'description' => 'Grau de sociabilidade, comunicação espontânea e necessidade de interação social. Pessoas com alta Extroversão se energizam com pessoas, comunicam-se com facilidade e influenciam o ambiente ao redor.',
                'questions'   => [
                    ['Sinto energia ao interagir com pessoas que acabei de conhecer.',                       false],
                    ['Prefiro trabalhar sozinho a colaborar em equipe.',                                     true],
                    ['Tenho facilidade para iniciar conversas em ambientes que não conheço.',                false],
                    ['Me sinto à vontade ao me apresentar ou falar para grupos.',                            false],
                    ['Evito situações em que precise convencer ou persuadir outras pessoas.',                true],
                    ['Construir e manter uma rede de relacionamentos é algo natural para mim.',              false],
                    ['Em reuniões, costumo tomar a palavra e compartilhar minhas ideias ativamente.',        false],
                    ['Fico esgotado após longas interações sociais e preciso de tempo sozinho para recarregar.', true],
                ],
            ],
            [
                'slug'        => 'paciencia',
                'name'        => 'Paciência',
                'order'       => 3,
                'weight'      => 1.0,
                'description' => 'Ritmo de trabalho, constância, tolerância a mudanças e capacidade de ouvir. Pessoas com alta Paciência são consistentes, empáticas e preferem ambientes estáveis e previsíveis.',
                'questions'   => [
                    ['Prefiro trabalhar em um ritmo constante e previsível a lidar com urgências frequentes.',  false],
                    ['Mantenho a calma mesmo quando situações fogem do planejado.',                             false],
                    ['Fico impaciente quando processos demoram mais do que o esperado.',                        true],
                    ['Tenho facilidade para ouvir as pessoas até o fim sem interrompê-las.',                   false],
                    ['Costumo concluir o que começo antes de iniciar novas tarefas.',                           false],
                    ['Mudanças frequentes de rotina e prioridades me deixam desconfortável.',                   false],
                    ['Demonstro empatia com facilidade ao lidar com colegas em dificuldade.',                   false],
                    ['Quando há pressa, perco a qualidade e a atenção aos detalhes do meu trabalho.',           true],
                ],
            ],
            [
                'slug'        => 'normas',
                'name'        => 'Normas',
                'order'       => 4,
                'weight'      => 1.0,
                'description' => 'Adesão a processos, atenção a detalhes e orientação para qualidade e precisão. Pessoas com alto índice em Normas são metódicas, analíticas e valorizam clareza de papéis e procedimentos.',
                'questions'   => [
                    ['Sigo procedimentos estabelecidos mesmo quando acredito que poderia fazer de outra forma.',  false],
                    ['Dedico atenção especial aos detalhes antes de entregar qualquer trabalho.',                  false],
                    ['Ajo por impulso sem me preocupar muito com regras ou protocolos.',                           true],
                    ['Prefiro ter diretrizes claras antes de iniciar uma tarefa nova.',                            false],
                    ['Sinto desconforto quando sou solicitado a trabalhar sem processos bem definidos.',           false],
                    ['Verifico meu trabalho mais de uma vez antes de considerá-lo concluído.',                    false],
                    ['Adapto as regras conforme a situação, sem me prender a elas.',                              true],
                    ['Qualidade e precisão são prioridades que não abro mão no meu trabalho.',                    false],
                ],
            ],
            [
                'slug'        => 'autocontrole',
                'name'        => 'Autocontrole',
                'order'       => 5,
                'weight'      => 1.0,
                'description' => 'Equilíbrio emocional, autodisciplina e capacidade de adaptar o comportamento ao contexto. Pessoas com alto Autocontrole gerenciam bem o estresse, recebem feedback construtivamente e mantêm a compostura em situações adversas.',
                'questions'   => [
                    ['Mantenho a compostura e o equilíbrio em situações de alta pressão.',                        false],
                    ['Consigo identificar e gerenciar minhas emoções antes de agir.',                             false],
                    ['Quando estressado, costumo reagir de forma impulsiva.',                                     true],
                    ['Adapto meu comportamento conforme o ambiente e as pessoas à minha volta.',                  false],
                    ['Em momentos de conflito, consigo manter o tom de voz e a postura adequados.',               false],
                    ['Tenho dificuldade para separar problemas pessoais do ambiente de trabalho.',                true],
                    ['Reconheço quando estou no limite e busco formas saudáveis de recarregar minhas energias.',  false],
                    ['Reajo de forma exagerada a críticas ou feedbacks negativos.',                               true],
                ],
            ],
        ];

        foreach ($dimensions as $dimData) {
            $questions = $dimData['questions'];
            unset($dimData['questions']);

            $dim = AssessmentDimension::updateOrCreate(
                [
                    'assessment_test_id' => $test->id,
                    'slug'               => $dimData['slug'],
                ],
                array_merge($dimData, ['assessment_test_id' => $test->id])
            );

            // Remove questões antigas para re-seed limpo
            AssessmentQuestion::where('assessment_dimension_id', $dim->id)->delete();

            $prefix = strtoupper(substr($dimData['slug'], 0, 3));

            foreach ($questions as $order => [$statement, $isReverse]) {
                AssessmentQuestion::create([
                    'assessment_test_id'      => $test->id,
                    'assessment_dimension_id' => $dim->id,
                    'statement'               => $statement,
                    'code'                    => $prefix . str_pad($order + 1, 2, '0', STR_PAD_LEFT),
                    'question_type'           => 'likert',
                    'scale_min'               => 1,
                    'scale_max'               => 5,
                    'is_reverse'              => $isReverse,
                    'is_attention_check'      => false,
                    'weight'                  => 1.0,
                    'order'                   => $order + 1,
                ]);
            }
        }

        $this->command->info('✓ Perfil Comportamental — 5 Eixos: 5 dimensões, 40 questões.');
    }
}
