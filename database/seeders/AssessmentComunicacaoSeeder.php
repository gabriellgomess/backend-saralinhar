<?php

namespace Database\Seeders;

use App\Models\AssessmentDimension;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentTest;
use Illuminate\Database\Seeder;

/**
 * Mapeamento de Estilo de Comunicação
 * Spec §10 — 6 dimensões × 5 perguntas = 30 itens, escala Likert 1-5.
 * Versão 1.0 — RASCUNHO: revisar enunciados com a Sara antes de produção.
 */
class AssessmentComunicacaoSeeder extends Seeder
{
    public function run(): void
    {
        $test = AssessmentTest::updateOrCreate(
            ['slug' => 'estilo-de-comunicacao'],
            [
                'name'        => 'Mapeamento de Estilo de Comunicação',
                'description' => 'Identifica preferências e tendências de comunicação no ambiente profissional em seis dimensões observáveis.',
                'type'        => 'likert',
                'version'     => '1.0',
                'is_active'   => true,
                'disclaimer'  => 'Este relatório é uma ferramenta de apoio à gestão de pessoas, desenvolvimento profissional e análise organizacional. Os resultados são baseados nas respostas fornecidas no momento da aplicação e não constituem teste psicológico, laudo psicológico, diagnóstico clínico ou avaliação de saúde mental. As informações devem ser analisadas em conjunto com entrevistas, histórico profissional, requisitos da função, contexto de trabalho e demais evidências disponíveis.',
            ]
        );

        $dimensions = [
            ['slug' => 'clareza-e-objetividade',        'name' => 'Clareza e Objetividade',                  'order' => 1],
            ['slug' => 'escuta-ativa',                  'name' => 'Escuta Ativa',                            'order' => 2],
            ['slug' => 'assertividade-profissional',    'name' => 'Assertividade Profissional',              'order' => 3],
            ['slug' => 'adaptacao-ao-publico',          'name' => 'Adaptação ao Público',                    'order' => 4],
            ['slug' => 'feedback-e-alinhamento',        'name' => 'Feedback e Alinhamento',                  'order' => 5],
            ['slug' => 'registro-e-organizacao',        'name' => 'Registro e Organização da Informação',    'order' => 6],
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
            // Clareza e Objetividade
            ['clareza-e-objetividade', 'Organizo minhas ideias antes de comunicar informações importantes.', false],
            ['clareza-e-objetividade', 'Transmito informações de forma direta, sem omitir o que é essencial.', false],
            ['clareza-e-objetividade', 'Confirmo se a mensagem foi compreendida da forma esperada.', false],
            ['clareza-e-objetividade', 'Costumo me perder ao explicar ideias mais complexas.', true],
            ['clareza-e-objetividade', 'Uso exemplos ou comparações para facilitar o entendimento do que explico.', false],

            // Escuta Ativa
            ['escuta-ativa', 'Ouço a pessoa até o final antes de responder.', false],
            ['escuta-ativa', 'Demonstro interesse genuíno quando alguém está me explicando algo.', false],
            ['escuta-ativa', 'Faço perguntas para entender melhor o que a pessoa quis dizer.', false],
            ['escuta-ativa', 'Interrompo as pessoas antes que terminem de falar.', true],
            ['escuta-ativa', 'Reformulo o que entendi para confirmar se captei a mensagem corretamente.', false],

            // Assertividade Profissional
            ['assertividade-profissional', 'Consigo me posicionar com clareza quando discordo de algo.', false],
            ['assertividade-profissional', 'Expresso minha opinião de forma respeitosa mesmo em situações de pressão.', false],
            ['assertividade-profissional', 'Defendo meu ponto de vista sem desrespeitar a perspectiva do outro.', false],
            ['assertividade-profissional', 'Evito me posicionar para não criar conflito, mesmo quando discordo.', true],
            ['assertividade-profissional', 'Consigo dizer "não" de forma clara e profissional quando necessário.', false],

            // Adaptação ao Público
            ['adaptacao-ao-publico', 'Ajusto minha linguagem conforme o perfil de quem estou comunicando.', false],
            ['adaptacao-ao-publico', 'Percebo quando minha forma de comunicar não está sendo bem recebida e mudo a abordagem.', false],
            ['adaptacao-ao-publico', 'Comunico informações técnicas de forma acessível para quem não é especialista.', false],
            ['adaptacao-ao-publico', 'Uso a mesma forma de comunicar independentemente de quem está ouvindo.', true],
            ['adaptacao-ao-publico', 'Levo em conta o contexto emocional ou cultural do interlocutor ao me comunicar.', false],

            // Feedback e Alinhamento
            ['feedback-e-alinhamento', 'Peço feedback sobre minha comunicação quando percebo que algo pode ter ficado confuso.', false],
            ['feedback-e-alinhamento', 'Ofereço feedbacks de forma construtiva e respeitosa.', false],
            ['feedback-e-alinhamento', 'Solicito confirmação ao final de um alinhamento importante.', false],
            ['feedback-e-alinhamento', 'Evito dar feedback mesmo quando percebo que ele seria útil para a outra pessoa.', true],
            ['feedback-e-alinhamento', 'Recebo feedbacks sobre minha comunicação sem reagir de forma defensiva.', false],

            // Registro e Organização da Informação
            ['registro-e-organizacao', 'Registro combinados importantes por escrito para evitar mal-entendidos.', false],
            ['registro-e-organizacao', 'Organizo e compartilho as informações de forma que outros possam acessá-las facilmente.', false],
            ['registro-e-organizacao', 'Mantenho histórico de decisões relevantes para consulta futura.', false],
            ['registro-e-organizacao', 'Evito registrar informações porque prefiro resolver tudo verbalmente.', true],
            ['registro-e-organizacao', 'Documento processos ou procedimentos quando identifico que isso pode ajudar a equipe.', false],
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

        $this->command->info('✓ Estilo de Comunicação: ' . $order - 1 . ' perguntas.');
    }
}
