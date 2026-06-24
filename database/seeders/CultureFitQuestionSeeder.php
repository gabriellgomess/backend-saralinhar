<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CultureFitQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questions = [
            // Autonomia
            [
                'question_number' => 1,
                'situation' => 'Você está começando um novo projeto no trabalho.',
                'statement' => 'Prefiro receber diretrizes claras e supervisão constante durante o processo.',
                'dimension' => 'autonomy',
                'scoring_direction' => 'negative',
            ],
            [
                'question_number' => 2,
                'situation' => 'Seu gerente oferece a opção de trabalhar de forma independente ou com acompanhamento próximo.',
                'statement' => 'Me sinto mais confortável e produtivo quando tenho liberdade para tomar minhas próprias decisões.',
                'dimension' => 'autonomy',
                'scoring_direction' => 'positive',
            ],
            [
                'question_number' => 3,
                'situation' => 'Uma decisão importante precisa ser tomada sobre o seu trabalho.',
                'statement' => 'Gosto de ter autonomia para decidir como vou realizar minhas tarefas sem precisar de aprovação constante.',
                'dimension' => 'autonomy',
                'scoring_direction' => 'positive',
            ],

            // Inovação
            [
                'question_number' => 4,
                'situation' => 'Sua equipe está discutindo melhorias nos processos de trabalho.',
                'statement' => 'Me entusiasmo com a possibilidade de experimentar novas abordagens e soluções criativas.',
                'dimension' => 'innovation',
                'scoring_direction' => 'positive',
            ],
            [
                'question_number' => 5,
                'situation' => 'Você tem a opção de seguir um método testado ou tentar algo novo.',
                'statement' => 'Prefiro seguir processos já estabelecidos e comprovados ao invés de arriscar com novidades.',
                'dimension' => 'innovation',
                'scoring_direction' => 'negative',
            ],
            [
                'question_number' => 6,
                'situation' => 'A empresa está implementando mudanças significativas na forma de trabalhar.',
                'statement' => 'Vejo mudanças e transformações como oportunidades empolgantes de crescimento e inovação.',
                'dimension' => 'innovation',
                'scoring_direction' => 'positive',
            ],
            [
                'question_number' => 7,
                'situation' => 'Você identifica uma oportunidade de melhorar um processo existente.',
                'statement' => 'Gosto de propor ideias inovadoras mesmo que isso signifique desafiar o status quo.',
                'dimension' => 'innovation',
                'scoring_direction' => 'positive',
            ],

            // Hierarquia/Estrutura
            [
                'question_number' => 8,
                'situation' => 'A estrutura organizacional da empresa está sendo discutida.',
                'statement' => 'Me sinto mais seguro quando há uma hierarquia clara e processos bem definidos.',
                'dimension' => 'hierarchy',
                'scoring_direction' => 'positive',
            ],
            [
                'question_number' => 9,
                'situation' => 'Você precisa resolver um problema no trabalho.',
                'statement' => 'Prefiro ambientes com estrutura flexível onde posso contornar a burocracia quando necessário.',
                'dimension' => 'hierarchy',
                'scoring_direction' => 'negative',
            ],
            [
                'question_number' => 10,
                'situation' => 'Seu time está definindo como as decisões serão tomadas.',
                'statement' => 'Valorizo ter regras e procedimentos claros que orientem as atividades do dia a dia.',
                'dimension' => 'hierarchy',
                'scoring_direction' => 'positive',
            ],

            // Trabalho em Equipe
            [
                'question_number' => 11,
                'situation' => 'Um projeto importante está sendo planejado.',
                'statement' => 'Trabalho melhor quando posso colaborar ativamente com colegas e trocar ideias.',
                'dimension' => 'teamwork',
                'scoring_direction' => 'positive',
            ],
            [
                'question_number' => 12,
                'situation' => 'Você tem a opção de trabalhar sozinho ou em grupo em uma tarefa.',
                'statement' => 'Prefiro trabalhar de forma independente e me responsabilizar pelos meus próprios resultados.',
                'dimension' => 'teamwork',
                'scoring_direction' => 'negative',
            ],
            [
                'question_number' => 13,
                'situation' => 'Sua equipe está enfrentando um desafio complexo.',
                'statement' => 'Acredito que as melhores soluções vêm da colaboração e do trabalho conjunto.',
                'dimension' => 'teamwork',
                'scoring_direction' => 'positive',
            ],
            [
                'question_number' => 14,
                'situation' => 'O ambiente de trabalho está sendo reorganizado.',
                'statement' => 'Me sinto energizado quando trabalho em equipe e posso contribuir para objetivos coletivos.',
                'dimension' => 'teamwork',
                'scoring_direction' => 'positive',
            ],

            // Foco em Resultados
            [
                'question_number' => 15,
                'situation' => 'Você está definindo prioridades para o trimestre.',
                'statement' => 'Sou altamente motivado por metas desafiadoras e pela conquista de resultados tangíveis.',
                'dimension' => 'results',
                'scoring_direction' => 'positive',
            ],
            [
                'question_number' => 16,
                'situation' => 'A empresa está estabelecendo indicadores de performance.',
                'statement' => 'Valorizo mais o processo e a qualidade do trabalho do que necessariamente bater metas numéricas.',
                'dimension' => 'results',
                'scoring_direction' => 'negative',
            ],
            [
                'question_number' => 17,
                'situation' => 'Seu gerente está definindo expectativas para sua função.',
                'statement' => 'Me sinto realizado quando posso ver os resultados concretos do meu esforço e superar objetivos.',
                'dimension' => 'results',
                'scoring_direction' => 'positive',
            ],
            [
                'question_number' => 18,
                'situation' => 'A equipe está discutindo como medir o sucesso do projeto.',
                'statement' => 'Gosto de ter KPIs claros e trabalhar com senso de urgência para alcançar as metas estabelecidas.',
                'dimension' => 'results',
                'scoring_direction' => 'positive',
            ],

            // Flexibilidade
            [
                'question_number' => 19,
                'situation' => 'As prioridades do seu projeto mudaram inesperadamente.',
                'statement' => 'Me adapto facilmente a mudanças e consigo ajustar minhas prioridades quando necessário.',
                'dimension' => 'flexibility',
                'scoring_direction' => 'positive',
            ],
            [
                'question_number' => 20,
                'situation' => 'Você precisa lidar com múltiplas demandas simultâneas.',
                'statement' => 'Prefiro ter uma rotina previsível e me incomodo quando os planos mudam com frequência.',
                'dimension' => 'flexibility',
                'scoring_direction' => 'negative',
            ],
            [
                'question_number' => 21,
                'situation' => 'A empresa está passando por mudanças organizacionais.',
                'statement' => 'Vejo versatilidade e adaptabilidade como pontos fortes importantes no ambiente de trabalho.',
                'dimension' => 'flexibility',
                'scoring_direction' => 'positive',
            ],
            [
                'question_number' => 22,
                'situation' => 'Você recebe uma tarefa fora da sua área de conforto.',
                'statement' => 'Me sinto confortável saindo da minha zona de conforto e aprendendo coisas novas.',
                'dimension' => 'flexibility',
                'scoring_direction' => 'positive',
            ],

            // Questões mistas para enriquecer o teste
            [
                'question_number' => 23,
                'situation' => 'A empresa está definindo a cultura organizacional ideal.',
                'statement' => 'Valorizo um ambiente que equilibra liberdade individual com trabalho colaborativo.',
                'dimension' => 'autonomy',
                'scoring_direction' => 'positive',
            ],
            [
                'question_number' => 24,
                'situation' => 'Você está avaliando oportunidades de carreira.',
                'statement' => 'Me identifico com organizações que valorizam tanto a inovação quanto processos estruturados.',
                'dimension' => 'innovation',
                'scoring_direction' => 'positive',
            ],
            [
                'question_number' => 25,
                'situation' => 'Seu time está definindo como trabalhar nos próximos meses.',
                'statement' => 'Busco ambientes que ofereçam estabilidade mas também abertura para mudanças quando necessário.',
                'dimension' => 'flexibility',
                'scoring_direction' => 'positive',
            ],
        ];

        DB::table('culture_fit_questions')->insert($questions);
    }
}
