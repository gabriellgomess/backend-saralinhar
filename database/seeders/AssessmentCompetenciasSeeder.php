<?php

namespace Database\Seeders;

use App\Models\AssessmentDimension;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentTest;
use Illuminate\Database\Seeder;

/**
 * Mapeamento de Competências Comportamentais Observáveis
 * Spec §8 — 10 dimensões × 4 perguntas = 40 itens, escala de frequência 1-5.
 * Versão 1.0 — RASCUNHO: revisar enunciados com a Sara antes de produção.
 */
class AssessmentCompetenciasSeeder extends Seeder
{
    public function run(): void
    {
        $test = AssessmentTest::updateOrCreate(
            ['slug' => 'competencias-comportamentais-observaveis'],
            [
                'name'        => 'Mapeamento de Competências Comportamentais Observáveis',
                'description' => 'Identifica tendências de comportamento profissional em 10 dimensões observáveis, com base nas respostas do próprio respondente.',
                'type'        => 'likert',
                'version'     => '1.0',
                'is_active'   => true,
                'disclaimer'  => 'Este relatório é uma ferramenta de apoio à gestão de pessoas, desenvolvimento profissional e análise organizacional. Os resultados são baseados nas respostas fornecidas no momento da aplicação e não constituem teste psicológico, laudo psicológico, diagnóstico clínico ou avaliação de saúde mental. As informações devem ser analisadas em conjunto com entrevistas, histórico profissional, requisitos da função, contexto de trabalho e demais evidências disponíveis.',
            ]
        );

        $dimensions = [
            ['slug' => 'comunicacao-profissional',    'name' => 'Comunicação Profissional',       'order' => 1],
            ['slug' => 'colaboracao',                 'name' => 'Colaboração',                    'order' => 2],
            ['slug' => 'responsabilidade',            'name' => 'Responsabilidade',               'order' => 3],
            ['slug' => 'organizacao',                 'name' => 'Organização',                    'order' => 4],
            ['slug' => 'proatividade',                'name' => 'Proatividade',                   'order' => 5],
            ['slug' => 'adaptabilidade',              'name' => 'Adaptabilidade',                 'order' => 6],
            ['slug' => 'resolucao-de-problemas',      'name' => 'Resolução de Problemas',         'order' => 7],
            ['slug' => 'foco-no-cliente',             'name' => 'Foco no Cliente / Usuário',      'order' => 8],
            ['slug' => 'aprendizado-continuo',        'name' => 'Aprendizado Contínuo',           'order' => 9],
            ['slug' => 'relacionamento-profissional', 'name' => 'Relacionamento Profissional',    'order' => 10],
        ];

        $dimModels = [];
        foreach ($dimensions as $dimData) {
            $dim = AssessmentDimension::updateOrCreate(
                ['assessment_test_id' => $test->id, 'slug' => $dimData['slug']],
                ['name' => $dimData['name'], 'weight' => 1.0, 'order' => $dimData['order']]
            );
            $dimModels[$dimData['slug']] = $dim;
        }

        // Perguntas: [dimensão, enunciado, is_reverse]
        $questions = [
            // Comunicação Profissional
            ['comunicacao-profissional', 'Explico informações importantes de forma clara para evitar retrabalho.', false],
            ['comunicacao-profissional', 'Confirmo se a outra pessoa compreendeu o que foi combinado.', false],
            ['comunicacao-profissional', 'Registro informações relevantes quando necessário.', false],
            ['comunicacao-profissional', 'Tenho dificuldade em adaptar minha comunicação ao público com quem estou falando.', true],

            // Colaboração
            ['colaboracao', 'Ofereço apoio quando percebo que alguém da equipe precisa de ajuda.', false],
            ['colaboracao', 'Compartilho informações que podem facilitar o trabalho do time.', false],
            ['colaboracao', 'Procuro construir soluções junto com outras pessoas.', false],
            ['colaboracao', 'Prefiro resolver tudo sozinho, mesmo quando a cooperação seria mais adequada.', true],

            // Responsabilidade
            ['responsabilidade', 'Cumpro os prazos e combinados assumidos.', false],
            ['responsabilidade', 'Aviso com antecedência quando identifico risco de atraso ou falha.', false],
            ['responsabilidade', 'Assumo responsabilidade por corrigir erros quando eles acontecem.', false],
            ['responsabilidade', 'Evito comunicar problemas para não me envolver.', true],

            // Organização
            ['organizacao', 'Organizo minhas tarefas de acordo com prioridade e prazo.', false],
            ['organizacao', 'Mantenho os arquivos e informações da minha área organizados e atualizados.', false],
            ['organizacao', 'Planejo as etapas de um trabalho antes de começar a executá-lo.', false],
            ['organizacao', 'Tenho dificuldade em manter a organização quando preciso lidar com várias demandas ao mesmo tempo.', true],

            // Proatividade
            ['proatividade', 'Identifico problemas antes que eles se tornem maiores e tomo a iniciativa de comunicar.', false],
            ['proatividade', 'Busco melhorias no meu processo de trabalho sem precisar ser solicitado.', false],
            ['proatividade', 'Antecipo necessidades do time ou do cliente antes que eles as verbalizem.', false],
            ['proatividade', 'Aguardo que alguém me indique o que fazer antes de tomar qualquer iniciativa.', true],

            // Adaptabilidade
            ['adaptabilidade', 'Adapto minha rotina quando surgem mudanças de prioridade.', false],
            ['adaptabilidade', 'Lido bem com processos novos ou ferramentas que ainda não conheço bem.', false],
            ['adaptabilidade', 'Mantenho o desempenho mesmo em situações de maior pressão ou imprevisibilidade.', false],
            ['adaptabilidade', 'Sinto dificuldade em ajustar minha forma de trabalhar quando as regras mudam.', true],

            // Resolução de Problemas
            ['resolucao-de-problemas', 'Quando encontro um obstáculo, busco alternativas para avançar.', false],
            ['resolucao-de-problemas', 'Analiso as causas de um problema antes de propor solução.', false],
            ['resolucao-de-problemas', 'Envolvo as pessoas certas quando o problema está fora da minha alçada.', false],
            ['resolucao-de-problemas', 'Quando enfrento dificuldades, prefiro aguardar que alguém resolva por mim.', true],

            // Foco no Cliente / Usuário
            ['foco-no-cliente', 'Priorizo a qualidade da entrega pensando em quem vai receber o resultado.', false],
            ['foco-no-cliente', 'Pergunto ou verifico o que o cliente ou usuário realmente precisa antes de agir.', false],
            ['foco-no-cliente', 'Tomo iniciativa para resolver situações que impactam negativamente a experiência do cliente.', false],
            ['foco-no-cliente', 'Termino minha parte sem verificar se o resultado atende quem irá utilizá-lo.', true],

            // Aprendizado Contínuo
            ['aprendizado-continuo', 'Busco aprender com feedbacks recebidos.', false],
            ['aprendizado-continuo', 'Procuro desenvolver habilidades necessárias para o meu trabalho atual ou futuro.', false],
            ['aprendizado-continuo', 'Aplico na prática os conhecimentos que adquiro em treinamentos ou experiências.', false],
            ['aprendizado-continuo', 'Tenho resistência a mudar a forma como realizo as tarefas, mesmo quando recebo orientação.', true],

            // Relacionamento Profissional
            ['relacionamento-profissional', 'Mantenho uma postura respeitosa mesmo em situações de pressão ou discordância.', false],
            ['relacionamento-profissional', 'Consigo colaborar de forma produtiva com pessoas de perfis ou opiniões diferentes.', false],
            ['relacionamento-profissional', 'Trato colegas, clientes e fornecedores com profissionalismo em todas as situações.', false],
            ['relacionamento-profissional', 'Evito interações com pessoas com quem tenho divergências, mesmo quando seria necessário.', true],
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

        $this->command->info('✓ Competências Comportamentais: ' . $order - 1 . ' perguntas.');
    }
}
