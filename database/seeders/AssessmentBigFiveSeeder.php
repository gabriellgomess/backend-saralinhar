<?php

namespace Database\Seeders;

use App\Models\AssessmentDimension;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentTest;
use Illuminate\Database\Seeder;

/**
 * Big Five — Inventário de Personalidade (OCEAN)
 * 5 dimensões × 10 itens = 50 questões. Escala Likert 1-5.
 * Itens reversos marcados com true na terceira coluna.
 */
class AssessmentBigFiveSeeder extends Seeder
{
    public function run(): void
    {
        $test = AssessmentTest::updateOrCreate(
            ['slug' => 'big-five-ocean'],
            [
                'name'        => 'Inventário de Personalidade Big Five (OCEAN)',
                'description' => 'Avalia cinco grandes dimensões da personalidade: Abertura à Experiência, Conscienciosidade, Extroversão, Amabilidade e Estabilidade Emocional. Amplamente utilizado em processos de seleção, desenvolvimento e planejamento de carreira.',
                'type'        => 'likert',
                'version'     => '1.0',
                'is_active'   => true,
                'disclaimer'  => 'Este instrumento é uma ferramenta de apoio para compreensão de tendências de personalidade no contexto profissional. Os resultados são baseados nas respostas fornecidas e não constituem diagnóstico psicológico, laudo clínico ou avaliação de saúde mental. Devem ser analisados em conjunto com entrevistas, histórico profissional e demais evidências do processo.',
            ]
        );

        $dimensions = [
            ['slug' => 'abertura',            'name' => 'Abertura à Experiência',  'order' => 1,  'description' => 'Curiosidade intelectual, criatividade, apreciação por novas ideias e experiências.'],
            ['slug' => 'conscienciosidade',   'name' => 'Conscienciosidade',        'order' => 2,  'description' => 'Organização, autodisciplina, orientação para resultados e confiabilidade.'],
            ['slug' => 'extroversao',         'name' => 'Extroversão',              'order' => 3,  'description' => 'Sociabilidade, assertividade, energia e tendência a buscar estimulação social.'],
            ['slug' => 'amabilidade',         'name' => 'Amabilidade',              'order' => 4,  'description' => 'Cooperação, confiança, empatia e orientação para harmonia interpessoal.'],
            ['slug' => 'estabilidade-emocional', 'name' => 'Estabilidade Emocional', 'order' => 5, 'description' => 'Capacidade de manter equilíbrio emocional diante de pressões e adversidades.'],
        ];

        $dimModels = [];
        foreach ($dimensions as $d) {
            $dim = AssessmentDimension::updateOrCreate(
                ['assessment_test_id' => $test->id, 'slug' => $d['slug']],
                ['name' => $d['name'], 'description' => $d['description'] ?? null, 'weight' => 1.0, 'order' => $d['order']]
            );
            $dimModels[$d['slug']] = $dim;
        }

        // [dimensão, enunciado, is_reverse]
        $questions = [
            // Abertura à Experiência
            ['abertura', 'Gosto de explorar novas ideias e maneiras diferentes de fazer as coisas.', false],
            ['abertura', 'Tenho imaginação ativa e muitas vezes deixo minha mente vagar por cenários criativos.', false],
            ['abertura', 'Me interesso por temas fora da minha área de atuação — arte, ciência, filosofia ou cultura.', false],
            ['abertura', 'Prefiro trabalhar com métodos já conhecidos e comprovados a tentar abordagens novas.', true],
            ['abertura', 'Sinto prazer em atividades que envolvam criação, design ou expressão.', false],
            ['abertura', 'Gosto de debater ideias abstratas e conceitos complexos.', false],
            ['abertura', 'Me adapto bem a ambientes em constante mudança, encarando as transformações como oportunidades.', false],
            ['abertura', 'Prefiro situações previsíveis a experiências novas e incertas.', true],
            ['abertura', 'Aprecio diferentes pontos de vista, mesmo quando divergem muito do meu.', false],
            ['abertura', 'Tenho pouco interesse em temas filosóficos ou artísticos.', true],

            // Conscienciosidade
            ['conscienciosidade', 'Planejo minhas atividades com antecedência e sigo o plano estabelecido.', false],
            ['conscienciosidade', 'Cumpro meus compromissos mesmo quando surgem dificuldades no meio do caminho.', false],
            ['conscienciosidade', 'Organizo meu espaço e minhas informações de forma que posso acessá-las quando precisar.', false],
            ['conscienciosidade', 'Costumo deixar tarefas para a última hora ou procrastinar responsabilidades.', true],
            ['conscienciosidade', 'Trabalho de forma metódica, verificando detalhes antes de considerar algo concluído.', false],
            ['conscienciosidade', 'Estabeleço metas claras para mim mesmo e acompanho meu progresso regularmente.', false],
            ['conscienciosidade', 'Sou visto como uma pessoa confiável — as pessoas sabem que podem contar comigo.', false],
            ['conscienciosidade', 'Começo muitos projetos, mas tenho dificuldade em terminá-los.', true],
            ['conscienciosidade', 'Priorizo o cumprimento de prazos e raramente me atraso com entregas.', false],
            ['conscienciosidade', 'Às vezes ajo por impulso sem pensar nas consequências de longo prazo.', true],

            // Extroversão
            ['extroversao', 'Me sinto energizado após interações sociais com grupos de pessoas.', false],
            ['extroversao', 'Tomo a iniciativa em conversas e me sinto confortável conhecendo pessoas novas.', false],
            ['extroversao', 'Prefiro trabalhar em equipe a trabalhar sozinho na maior parte do tempo.', false],
            ['extroversao', 'Prefiro ambientes tranquilos e com menos estímulos sociais.', true],
            ['extroversao', 'Me expresso com facilidade e tenho fluência para falar em público.', false],
            ['extroversao', 'Busco situações onde possa liderar ou influenciar outras pessoas.', false],
            ['extroversao', 'Sinto-me animado e com energia alta na maior parte do tempo.', false],
            ['extroversao', 'Prefiro passar o tempo livre em atividades solitárias a socializar.', true],
            ['extroversao', 'Me sinto à vontade em festas, reuniões sociais e ambientes movimentados.', false],
            ['extroversao', 'Evito chamar atenção para mim mesmo em grupos.', true],

            // Amabilidade
            ['amabilidade', 'Sinto genuíno interesse pelo bem-estar das pessoas ao meu redor.', false],
            ['amabilidade', 'Prefiro ceder em um conflito a insistir na minha posição, quando o relacionamento importa mais.', false],
            ['amabilidade', 'Procuro entender o ponto de vista do outro antes de defender o meu.', false],
            ['amabilidade', 'Tenho dificuldade em confiar nas intenções das pessoas.', true],
            ['amabilidade', 'Me ofereço para ajudar os outros mesmo quando não é esperado de mim.', false],
            ['amabilidade', 'Evito conflitos e busco manter a harmonia nas relações.', false],
            ['amabilidade', 'Sou descrito pelas pessoas como alguém gentil, prestativo e cooperativo.', false],
            ['amabilidade', 'Às vezes posso ser frio ou indiferente às necessidades dos outros.', true],
            ['amabilidade', 'Me importo com questões sociais e com o impacto das minhas ações nas outras pessoas.', false],
            ['amabilidade', 'Costumo ser crítico ou julgador em relação às escolhas alheias.', true],

            // Estabilidade Emocional
            ['estabilidade-emocional', 'Mantenho a calma e o raciocínio claro mesmo em situações de alta pressão.', false],
            ['estabilidade-emocional', 'Recupero-me rapidamente de situações frustrantes ou decepcionantes.', false],
            ['estabilidade-emocional', 'Não costumo me preocupar excessivamente com problemas que ainda não ocorreram.', false],
            ['estabilidade-emocional', 'Sinto ansiedade ou nervosismo com frequência, mesmo em situações rotineiras.', true],
            ['estabilidade-emocional', 'Consigo separar os problemas pessoais do desempenho profissional.', false],
            ['estabilidade-emocional', 'Reajo de forma equilibrada a críticas ou feedbacks negativos.', false],
            ['estabilidade-emocional', 'Meu humor se mantém relativamente estável ao longo do dia.', false],
            ['estabilidade-emocional', 'Fico perturbado ou irritado com facilidade quando as coisas não saem como planejado.', true],
            ['estabilidade-emocional', 'Consigo tomar decisões racionais mesmo quando estou sob pressão emocional.', false],
            ['estabilidade-emocional', 'Às vezes sinto que perco o controle das minhas emoções em situações de conflito.', true],
        ];

        foreach ($questions as $order => [$slug, $statement, $reverse]) {
            AssessmentQuestion::updateOrCreate(
                ['assessment_test_id' => $test->id, 'code' => 'BF' . str_pad($order + 1, 2, '0', STR_PAD_LEFT)],
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
