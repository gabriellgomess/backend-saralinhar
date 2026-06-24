<?php

namespace Database\Seeders;

use App\Models\AssessmentDimension;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentTest;
use Illuminate\Database\Seeder;

/**
 * Mapeamento de Estilo de Trabalho
 * Spec §11 — 8 dimensões × 4 perguntas = 32 itens, escala Likert 1-5.
 * Versão 1.0 — RASCUNHO: revisar enunciados com a Sara antes de produção.
 */
class AssessmentEstiloTrabalhoSeeder extends Seeder
{
    public function run(): void
    {
        $test = AssessmentTest::updateOrCreate(
            ['slug' => 'estilo-de-trabalho'],
            [
                'name'        => 'Mapeamento de Estilo de Trabalho',
                'description' => 'Identifica preferências práticas de organização, execução e interação no trabalho em oito dimensões observáveis.',
                'type'        => 'likert',
                'version'     => '1.0',
                'is_active'   => true,
                'disclaimer'  => 'Este relatório é uma ferramenta de apoio à gestão de pessoas, desenvolvimento profissional e análise organizacional. Os resultados são baseados nas respostas fornecidas no momento da aplicação e não constituem teste psicológico, laudo psicológico, diagnóstico clínico ou avaliação de saúde mental. As informações devem ser analisadas em conjunto com entrevistas, histórico profissional, requisitos da função, contexto de trabalho e demais evidências disponíveis.',
            ]
        );

        $dimensions = [
            ['slug' => 'planejamento-e-priorizacao',    'name' => 'Planejamento e Priorização',       'order' => 1],
            ['slug' => 'execucao-e-ritmo',              'name' => 'Execução e Ritmo',                 'order' => 2],
            ['slug' => 'atencao-a-detalhes',            'name' => 'Atenção a Detalhes',               'order' => 3],
            ['slug' => 'autonomia',                     'name' => 'Autonomia',                        'order' => 4],
            ['slug' => 'flexibilidade-operacional',     'name' => 'Flexibilidade Operacional',        'order' => 5],
            ['slug' => 'colaboracao-operacional',       'name' => 'Colaboração Operacional',          'order' => 6],
            ['slug' => 'aprendizado-e-melhoria',        'name' => 'Aprendizado e Melhoria',           'order' => 7],
            ['slug' => 'conformidade-com-processos',    'name' => 'Conformidade com Processos',       'order' => 8],
        ];

        $dimModels = [];
        foreach ($dimensions as $dimData) {
            $dim = AssessmentDimension::updateOrCreate(
                ['assessment_test_id' => $test->id, 'slug' => $dimData['slug']],
                ['name' => $dimData['name'], 'weight' => 1.0, 'order' => $dimData['order']]
            );
            $dimModels[$dimData['slug']] = $dim;
        }

        $questions = [
            // Planejamento e Priorização
            ['planejamento-e-priorizacao', 'Antes de iniciar uma tarefa importante, organizo prioridades e etapas.', false],
            ['planejamento-e-priorizacao', 'Ajusto o planejamento quando surgem imprevistos, sem perder o fio das prioridades.', false],
            ['planejamento-e-priorizacao', 'Defino critérios para decidir o que fazer primeiro quando tenho muitas demandas simultâneas.', false],
            ['planejamento-e-priorizacao', 'Começo a executar sem planejar, o que frequentemente gera retrabalho.', true],

            // Execução e Ritmo
            ['execucao-e-ritmo', 'Mantenho produtividade constante ao longo do dia, sem depender de pressão externa.', false],
            ['execucao-e-ritmo', 'Entrego o que é combinado dentro do prazo estabelecido.', false],
            ['execucao-e-ritmo', 'Consigo manter o ritmo de trabalho mesmo em dias com muitas interrupções.', false],
            ['execucao-e-ritmo', 'Preciso de pressão ou urgência para conseguir concluir tarefas importantes.', true],

            // Atenção a Detalhes
            ['atencao-a-detalhes', 'Reviso meu trabalho antes de entregar para reduzir erros.', false],
            ['atencao-a-detalhes', 'Identifico inconsistências em documentos, processos ou informações.', false],
            ['atencao-a-detalhes', 'Cuido da qualidade dos detalhes mesmo quando estou sob pressão de prazo.', false],
            ['atencao-a-detalhes', 'Entrego trabalhos sem revisar, confiando que está correto.', true],

            // Autonomia
            ['autonomia', 'Consigo avançar em atividades mesmo quando não tenho todas as instruções detalhadas.', false],
            ['autonomia', 'Tomo decisões dentro da minha alçada sem precisar consultar alguém a cada passo.', false],
            ['autonomia', 'Quando encontro um obstáculo, tento resolver por conta própria antes de pedir ajuda.', false],
            ['autonomia', 'Sinto insegurança para agir sem validação de alguém com mais autoridade.', true],

            // Flexibilidade Operacional
            ['flexibilidade-operacional', 'Adapto minha rotina quando surgem prioridades novas sem me desorganizar.', false],
            ['flexibilidade-operacional', 'Consigo trabalhar bem em ambientes com processos em mudança.', false],
            ['flexibilidade-operacional', 'Lido com múltiplas tarefas simultâneas sem perder o controle do andamento.', false],
            ['flexibilidade-operacional', 'Prefiro ignorar processos quando acredito que consigo resolver mais rápido de outra forma.', true],

            // Colaboração Operacional
            ['colaboracao-operacional', 'Integro meu trabalho com o de outras áreas ou pessoas de forma fluida.', false],
            ['colaboracao-operacional', 'Comunico ao time quando minha parte pode impactar o andamento do trabalho deles.', false],
            ['colaboracao-operacional', 'Ofereço suporte a colegas quando tenho capacidade e percebo que eles precisam.', false],
            ['colaboracao-operacional', 'Foco apenas na minha parte, sem me preocupar com como ela conecta ao trabalho do time.', true],

            // Aprendizado e Melhoria
            ['aprendizado-e-melhoria', 'Busco entender o porquê dos processos que executo, não apenas como fazê-los.', false],
            ['aprendizado-e-melhoria', 'Identifico melhorias no meu processo de trabalho e proponho ajustes quando cabível.', false],
            ['aprendizado-e-melhoria', 'Aplico na prática o que aprendo em treinamentos ou em novas experiências.', false],
            ['aprendizado-e-melhoria', 'Prefiro continuar fazendo do jeito que já conheço, mesmo quando há formas melhores.', true],

            // Conformidade com Processos
            ['conformidade-com-processos', 'Sigo os fluxos e procedimentos estabelecidos, mesmo quando acho que poderia ser diferente.', false],
            ['conformidade-com-processos', 'Registro as informações nos sistemas ou canais corretos conforme o processo.', false],
            ['conformidade-com-processos', 'Consulto manuais, normas ou orientações antes de agir em situações novas.', false],
            ['conformidade-com-processos', 'Tomo atalhos nos processos quando estou com pressa, sem avaliar o impacto.', true],
        ];

        $order = 1;
        foreach ($questions as [$dimSlug, $statement, $isReverse]) {
            AssessmentQuestion::updateOrCreate(
                [
                    'assessment_test_id'      => $test->id,
                    'assessment_dimension_id' => $dimModels[$dimSlug]->id,
                    'statement'               => $statement,
                ],
                [
                    'question_type'      => 'likert',
                    'scale_min'          => 1,
                    'scale_max'          => 5,
                    'is_reverse'         => $isReverse,
                    'weight'             => 1.0,
                    'is_attention_check' => false,
                    'order'              => $order++,
                ]
            );
        }

        $this->command->info('✓ Estilo de Trabalho: ' . $order - 1 . ' perguntas.');
    }
}
