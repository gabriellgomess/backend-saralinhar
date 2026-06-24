<?php

namespace Database\Seeders;

use App\Models\AssessmentDimension;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentTest;
use Illuminate\Database\Seeder;

/**
 * Inventário de Resiliência e Gestão do Estresse
 * 5 dimensões × 6 itens = 30 questões. Escala Likert 1-5.
 */
class AssessmentResilienciaSeeder extends Seeder
{
    public function run(): void
    {
        $test = AssessmentTest::updateOrCreate(
            ['slug' => 'resiliencia-gestao-estresse'],
            [
                'name'        => 'Inventário de Resiliência e Gestão do Estresse',
                'description' => 'Avalia a capacidade do respondente de lidar com adversidades, pressão e mudanças no contexto de trabalho. Mede regulação emocional, persistência, otimismo, adaptabilidade e uso de suporte social como recursos de enfrentamento.',
                'type'        => 'likert',
                'version'     => '1.0',
                'is_active'   => true,
                'disclaimer'  => 'Este instrumento avalia recursos psicológicos de enfrentamento no contexto profissional. Os resultados são baseados nas autopercepções do respondente e não constituem avaliação de saúde mental, diagnóstico ou laudo psicológico. Em casos de sofrimento psicológico, recomenda-se encaminhamento a profissional de saúde mental.',
            ]
        );

        $dimensions = [
            ['slug' => 'regulacao-emocional',  'name' => 'Regulação Emocional',            'order' => 1, 'description' => 'Capacidade de reconhecer e gerenciar emoções intensas sem que prejudiquem o funcionamento.'],
            ['slug' => 'persistencia',          'name' => 'Persistência e Tolerância',      'order' => 2, 'description' => 'Capacidade de manter o esforço diante de obstáculos, frustrações e fracassos.'],
            ['slug' => 'otimismo',              'name' => 'Otimismo e Perspectiva',         'order' => 3, 'description' => 'Tendência a encarar situações difíceis com esperança e a encontrar aprendizado nas adversidades.'],
            ['slug' => 'suporte-social',        'name' => 'Uso de Suporte Social',          'order' => 4, 'description' => 'Habilidade de buscar e utilizar redes de apoio como recurso em momentos de dificuldade.'],
            ['slug' => 'adaptabilidade-pressao','name' => 'Adaptabilidade sob Pressão',    'order' => 5, 'description' => 'Capacidade de ajustar comportamentos e estratégias quando o contexto muda rapidamente.'],
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
            // Regulação Emocional
            ['regulacao-emocional', 'Consigo identificar o que estou sentindo e nomear a emoção antes de reagir.', false],
            ['regulacao-emocional', 'Quando sinto raiva ou frustração no trabalho, consigo pausar antes de agir impulsivamente.', false],
            ['regulacao-emocional', 'Mantenho o equilíbrio emocional mesmo em dias de alta demanda ou conflito.', false],
            ['regulacao-emocional', 'Emoções intensas como ansiedade ou irritação afetam meu desempenho com frequência.', true],
            ['regulacao-emocional', 'Tenho estratégias eficazes para me acalmar quando estou sobrecarregado.', false],
            ['regulacao-emocional', 'Consigo separar os problemas pessoais do meu desempenho profissional na maior parte do tempo.', false],

            // Persistência e Tolerância
            ['persistencia', 'Diante de um obstáculo, procuro alternativas em vez de desistir do objetivo.', false],
            ['persistencia', 'Fracassos e erros me motivam a tentar de forma diferente, não a desistir.', false],
            ['persistencia', 'Mantenho o empenho em projetos de longo prazo mesmo quando os resultados demoram a aparecer.', false],
            ['persistencia', 'Quando algo não sai como esperado, fico ruminando sobre o fracasso por muito tempo.', true],
            ['persistencia', 'Encaro críticas e feedbacks negativos como dados úteis para melhorar, não como ataques pessoais.', false],
            ['persistencia', 'Desisto com facilidade quando encontro resistência ou dificuldades inesperadas.', true],

            // Otimismo e Perspectiva
            ['otimismo', 'Acredito que as dificuldades do presente são passageiras e que as coisas podem melhorar.', false],
            ['otimismo', 'Consigo encontrar aprendizado ou oportunidade em situações adversas.', false],
            ['otimismo', 'Mesmo em contextos de incerteza, mantenho uma perspectiva construtiva sobre o futuro.', false],
            ['otimismo', 'Tenho tendência a catastrofizar — imaginar o pior cenário possível — quando enfrento problemas.', true],
            ['otimismo', 'Confio na minha capacidade de superar desafios com base nas experiências passadas.', false],
            ['otimismo', 'Situações de estresse me fazem duvidar da minha competência e do meu valor profissional.', true],

            // Suporte Social
            ['suporte-social', 'Sei a quem recorrer quando preciso de apoio profissional ou emocional.', false],
            ['suporte-social', 'Não tenho dificuldade em pedir ajuda quando a situação exige.', false],
            ['suporte-social', 'Mantenho relações de confiança que me servem de suporte em momentos difíceis.', false],
            ['suporte-social', 'Prefiro lidar com os problemas sozinho a buscar apoio de outras pessoas.', true],
            ['suporte-social', 'Conseguir apoio da minha rede me ajuda a processar melhor as situações de pressão.', false],
            ['suporte-social', 'Tenho dificuldade em aceitar ajuda, mesmo quando reconheço que precisaria dela.', true],

            // Adaptabilidade sob Pressão
            ['adaptabilidade-pressao', 'Quando as regras do jogo mudam, me adapto com agilidade sem perder o foco.', false],
            ['adaptabilidade-pressao', 'Trabalho bem em ambientes de alta incerteza e mudanças frequentes de prioridade.', false],
            ['adaptabilidade-pressao', 'Consigo redirecionar minha energia rapidamente quando um plano não funciona.', false],
            ['adaptabilidade-pressao', 'Mudanças não planejadas me geram angústia e comprometem meu rendimento.', true],
            ['adaptabilidade-pressao', 'Mantenho a produtividade mesmo quando trabalho sob prazos apertados ou pressão de resultados.', false],
            ['adaptabilidade-pressao', 'Tenho dificuldade em lidar com ambiguidade — prefiro contextos bem definidos e estáveis.', true],
        ];

        foreach ($questions as $order => [$slug, $statement, $reverse]) {
            AssessmentQuestion::updateOrCreate(
                ['assessment_test_id' => $test->id, 'code' => 'RE' . str_pad($order + 1, 2, '0', STR_PAD_LEFT)],
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
