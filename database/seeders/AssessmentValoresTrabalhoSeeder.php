<?php

namespace Database\Seeders;

use App\Models\AssessmentDimension;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentTest;
use Illuminate\Database\Seeder;

/**
 * Inventário de Valores de Trabalho
 * 6 dimensões × 5 itens = 30 questões. Escala Likert 1-5.
 * Útil para alinhamento cultural, retenção e planejamento de carreira.
 */
class AssessmentValoresTrabalhoSeeder extends Seeder
{
    public function run(): void
    {
        $test = AssessmentTest::updateOrCreate(
            ['slug' => 'valores-de-trabalho'],
            [
                'name'        => 'Inventário de Valores de Trabalho',
                'description' => 'Identifica o que o profissional mais valoriza no ambiente de trabalho: autonomia, segurança, realização, reconhecimento, propósito e equilíbrio vida-trabalho. Fundamental para decisões de fit cultural, onboarding, retenção e desenvolvimento de carreira.',
                'type'        => 'likert',
                'version'     => '1.0',
                'is_active'   => true,
                'disclaimer'  => 'Este instrumento mapeia preferências e valores profissionais autopercebidos. Os resultados não constituem avaliação de desempenho ou critério absoluto de seleção. Devem ser utilizados como insumo para conversas de carreira, alinhamento cultural e planejamento de desenvolvimento, sempre interpretados em contexto.',
            ]
        );

        $dimensions = [
            ['slug' => 'autonomia',            'name' => 'Autonomia e Liberdade',          'order' => 1, 'description' => 'Valorização da independência, autodireção e liberdade para tomar decisões sobre o próprio trabalho.'],
            ['slug' => 'seguranca',            'name' => 'Segurança e Estabilidade',        'order' => 2, 'description' => 'Necessidade de previsibilidade, estabilidade financeira e ambiente de trabalho seguro e confiável.'],
            ['slug' => 'realizacao',           'name' => 'Realização e Desafio',            'order' => 3, 'description' => 'Valorização de conquistas, crescimento profissional e enfrentamento de desafios estimulantes.'],
            ['slug' => 'reconhecimento',       'name' => 'Reconhecimento e Prestígio',      'order' => 4, 'description' => 'Importância atribuída ao reconhecimento, status, visibilidade e valorização pelo trabalho realizado.'],
            ['slug' => 'proposito',            'name' => 'Propósito e Contribuição',        'order' => 5, 'description' => 'Busca por significado, impacto positivo e alinhamento entre o trabalho e valores pessoais.'],
            ['slug' => 'equilibrio',           'name' => 'Equilíbrio Vida-Trabalho',        'order' => 6, 'description' => 'Valorização da qualidade de vida, tempo para vida pessoal e limites saudáveis entre trabalho e vida.'],
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
            // Autonomia
            ['autonomia', 'Prefiro ambientes onde tenho liberdade para organizar meu próprio trabalho sem microgestão.', false],
            ['autonomia', 'Trabalho melhor quando tenho autonomia para decidir como e quando executar minhas entregas.', false],
            ['autonomia', 'Me sinto sufocado em ambientes com excesso de regras, aprovações e controles.', false],
            ['autonomia', 'Prefiro receber orientações detalhadas sobre cada etapa do que ter que decidir por conta própria.', true],
            ['autonomia', 'A possibilidade de criar, inovar e ter iniciativa é fundamental para minha satisfação no trabalho.', false],

            // Segurança
            ['seguranca', 'Estabilidade financeira e segurança do emprego têm grande peso nas minhas decisões de carreira.', false],
            ['seguranca', 'Prefiro um emprego estável com benefícios a uma oportunidade mais lucrativa, porém arriscada.', false],
            ['seguranca', 'Me sinto mais motivado em organizações com processos claros, políticas definidas e cultura estável.', false],
            ['seguranca', 'Aceito bem a incerteza e não me preocupo muito com o futuro do meu emprego.', true],
            ['seguranca', 'A clareza sobre o meu papel, as expectativas e as regras do jogo é essencial para o meu desempenho.', false],

            // Realização
            ['realizacao', 'Sinto necessidade de trabalhar em projetos que desafiem minha capacidade e me façam crescer.', false],
            ['realizacao', 'O sentimento de progressão e desenvolvimento contínuo é um dos principais motivadores no meu trabalho.', false],
            ['realizacao', 'Prefiro um trabalho que me desafie constantemente a um que seja confortável e repetitivo.', false],
            ['realizacao', 'Me satisfaço facilmente em funções que oferecem pouca oportunidade de crescimento ou aprendizado.', true],
            ['realizacao', 'Celebro internamente as conquistas profissionais como forma de reconhecer meu próprio progresso.', false],

            // Reconhecimento
            ['reconhecimento', 'O reconhecimento público pelo trabalho bem feito é importante para minha motivação.', false],
            ['reconhecimento', 'Valorizo trabalhar em organizações com boa reputação e prestígio no mercado.', false],
            ['reconhecimento', 'Receber feedback positivo e elogios genuínos aumenta significativamente meu engajamento.', false],
            ['reconhecimento', 'Me é indiferente ser reconhecido ou elogiado — o que importa é o resultado, não o crédito.', true],
            ['reconhecimento', 'Me motiva saber que minha contribuição é vista e valorizada pelos líderes da organização.', false],

            // Propósito
            ['proposito', 'Preciso acreditar que o meu trabalho tem impacto positivo — para a empresa, as pessoas ou a sociedade.', false],
            ['proposito', 'O alinhamento entre meus valores pessoais e os da organização é fundamental para meu comprometimento.', false],
            ['proposito', 'Me motiva saber que o que faço contribui para algo maior do que apenas os resultados financeiros.', false],
            ['proposito', 'Consigo me engajar totalmente mesmo em trabalhos que não me parecem ter muito propósito ou significado.', true],
            ['proposito', 'Prefiro trabalhar em organizações cujo propósito e missão me identifico do que em empresas bem-pagas sem esse alinhamento.', false],

            // Equilíbrio
            ['equilibrio', 'O respeito ao meu tempo fora do trabalho é um critério importante na escolha de onde trabalhar.', false],
            ['equilibrio', 'Tenho dificuldade em me desconectar do trabalho fora do horário e isso afeta minha qualidade de vida.', true],
            ['equilibrio', 'Valorizo muito a flexibilidade de horários e o direito de descansar sem culpa.', false],
            ['equilibrio', 'Me sinto bem em dedicar longas horas ao trabalho e não me preocupo muito com equilíbrio entre vida e carreira.', true],
            ['equilibrio', 'Organizo minha agenda de modo a preservar tempo para saúde, família e vida pessoal.', false],
        ];

        foreach ($questions as $order => [$slug, $statement, $reverse]) {
            AssessmentQuestion::updateOrCreate(
                ['assessment_test_id' => $test->id, 'code' => 'VT' . str_pad($order + 1, 2, '0', STR_PAD_LEFT)],
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
