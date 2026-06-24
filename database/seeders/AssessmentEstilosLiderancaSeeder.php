<?php

namespace Database\Seeders;

use App\Models\AssessmentDimension;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentTest;
use Illuminate\Database\Seeder;

/**
 * Inventário de Estilos de Liderança
 * Baseado nos 6 estilos de Goleman (2000).
 * 6 dimensões × 5 itens = 30 questões. Escala Likert 1-5.
 * Indicado para profissionais em cargos de liderança ou em trilha de desenvolvimento gerencial.
 */
class AssessmentEstilosLiderancaSeeder extends Seeder
{
    public function run(): void
    {
        $test = AssessmentTest::updateOrCreate(
            ['slug' => 'estilos-de-lideranca'],
            [
                'name'        => 'Inventário de Estilos de Liderança',
                'description' => 'Identifica o perfil de liderança com base nos seis estilos de Goleman: Visionário, Coaching, Afiliativo, Democrático, Regulador e Diretivo. Revela quais estilos são dominantes e quais podem ser desenvolvidos para ampliar a eficácia de liderança.',
                'type'        => 'likert',
                'version'     => '1.0',
                'is_active'   => true,
                'disclaimer'  => 'Este instrumento é uma ferramenta de desenvolvimento gerencial e não substitui avaliações formais de desempenho ou processos de coaching estruturado. Os resultados refletem as autopercepções do respondente no momento da aplicação e devem ser interpretados por profissional habilitado em contexto de desenvolvimento.',
            ]
        );

        $dimensions = [
            ['slug' => 'visionario',   'name' => 'Visionário (Transformacional)', 'order' => 1, 'description' => 'Mobiliza as pessoas em direção a uma visão compartilhada, inspirando propósito e senso de missão.'],
            ['slug' => 'coaching',     'name' => 'Coaching',                      'order' => 2, 'description' => 'Conecta objetivos individuais aos da organização, desenvolve talentos com feedbacks e planos de crescimento.'],
            ['slug' => 'afiliativo',   'name' => 'Afiliativo',                    'order' => 3, 'description' => 'Prioriza harmonia, vínculos emocionais e o bem-estar da equipe para construir coesão.'],
            ['slug' => 'democratico',  'name' => 'Democrático',                   'order' => 4, 'description' => 'Cria consenso por meio da participação, valorizando a contribuição de todos nas decisões.'],
            ['slug' => 'regulador',    'name' => 'Regulador (Pacesetter)',         'order' => 5, 'description' => 'Define altos padrões de desempenho e os modela pessoalmente, esperando excelência da equipe.'],
            ['slug' => 'diretivo',     'name' => 'Diretivo (Comando e Controle)', 'order' => 6, 'description' => 'Exige conformidade imediata, é eficaz em crises ou com colaboradores de baixo desempenho.'],
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
            // Visionário
            ['visionario', 'Articulo uma visão de futuro clara e inspiradora que engaja a equipe em torno de um propósito comum.', false],
            ['visionario', 'Quando apresento mudanças, explico o porquê e o impacto de longo prazo para criar adesão genuína.', false],
            ['visionario', 'Conecto o trabalho do dia a dia da equipe aos objetivos estratégicos mais amplos da organização.', false],
            ['visionario', 'Inspiro as pessoas a superarem suas próprias expectativas ao mostrar o que é possível alcançar.', false],
            ['visionario', 'Tenho dificuldade em comunicar a visão de forma que motive verdadeiramente minha equipe.', true],

            // Coaching
            ['coaching', 'Reservo tempo regularmente para conversas de desenvolvimento individual com cada membro da minha equipe.', false],
            ['coaching', 'Ajudo as pessoas a identificar seus pontos fortes e a traçar planos de crescimento profissional.', false],
            ['coaching', 'Ofereço feedbacks específicos e construtivos focados no desenvolvimento, não apenas na correção.', false],
            ['coaching', 'Aceito erros como oportunidades de aprendizado, criando um ambiente seguro para o crescimento.', false],
            ['coaching', 'Prefiro dar respostas prontas a fazer perguntas que levem as pessoas a descobrirem soluções por si mesmas.', true],

            // Afiliativo
            ['afiliativo', 'Invisto no fortalecimento dos laços emocionais dentro da equipe, promovendo um clima de confiança.', false],
            ['afiliativo', 'Em momentos de conflito, priorizo a preservação do relacionamento antes de buscar a solução técnica.', false],
            ['afiliativo', 'Reconheço e celebro conquistas da equipe, não apenas resultados individuais.', false],
            ['afiliativo', 'Demonstro empatia e cuidado genuíno com o bem-estar das pessoas que lidero.', false],
            ['afiliativo', 'Evito conversas difíceis sobre desempenho para não prejudicar o clima da equipe.', true],

            // Democrático
            ['democratico', 'Incluo a equipe nas decisões importantes que afetam o trabalho e os processos.', false],
            ['democratico', 'Busco ativamente o ponto de vista de todos os membros antes de tomar uma decisão.', false],
            ['democratico', 'Crio espaços seguros para que as pessoas expressem discordâncias e sugestões.', false],
            ['democratico', 'Acredito que decisões tomadas com a participação do grupo têm maior qualidade e adesão.', false],
            ['democratico', 'Tomo a maioria das decisões de forma unilateral sem consultar os envolvidos.', true],

            // Regulador
            ['regulador', 'Defino padrões de qualidade elevados para a equipe e os modelo pessoalmente no meu próprio trabalho.', false],
            ['regulador', 'Identifico rapidamente quando o desempenho de alguém está aquém do esperado e ajo prontamente.', false],
            ['regulador', 'Espero que as pessoas da minha equipe tenham iniciativa e operem com alto nível de autonomia.', false],
            ['regulador', 'Me incomoda quando processos ou entregas não atingem o nível de excelência que considero necessário.', false],
            ['regulador', 'Tenho dificuldade em delegar porque me preocupo que as coisas não sejam feitas no padrão correto.', true],

            // Diretivo
            ['diretivo', 'Em situações de crise, assumo o controle e dou direcionamentos claros e imediatos.', false],
            ['diretivo', 'Quando há urgência, prefiro tomar decisões rapidamente a esperar pelo consenso da equipe.', false],
            ['diretivo', 'Monitoro de perto o cumprimento de metas e ajo rapidamente quando os resultados não aparecem.', false],
            ['diretivo', 'Sou direto ao comunicar expectativas e consequências quando padrões não são atendidos.', false],
            ['diretivo', 'Prefiro que a equipe aprenda por si mesma a dar instruções claras sobre como as tarefas devem ser feitas.', true],
        ];

        foreach ($questions as $order => [$slug, $statement, $reverse]) {
            AssessmentQuestion::updateOrCreate(
                ['assessment_test_id' => $test->id, 'code' => 'EL' . str_pad($order + 1, 2, '0', STR_PAD_LEFT)],
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
