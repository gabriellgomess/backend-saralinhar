<?php

namespace Database\Seeders;

use App\Models\AssessmentDimension;
use App\Models\AssessmentOption;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentTest;
use Illuminate\Database\Seeder;

/**
 * Teste Situacional Profissional — SJT
 * Spec §9 — formato sjt_pair (melhor + pior resposta).
 * 4 dimensões × 5 cenários = 20 cenários, 5 alternativas cada.
 *
 * Versão 1.0 — RASCUNHO: cenários e scores devem ser revisados e validados
 * com a Sara e/ou lideranças das empresas clientes antes de ir a produção.
 *
 * Regra de score (spec §9.5):
 *   100 = excelente / mais adequada
 *    75 = boa / adequada
 *    50 = parcial / aceitável mas incompleta
 *    25 = fraca / pouco adequada
 *     0 = inadequada / contraproducente
 */
class AssessmentSjtSeeder extends Seeder
{
    public function run(): void
    {
        $test = AssessmentTest::updateOrCreate(
            ['slug' => 'teste-situacional-profissional-sjt'],
            [
                'name'        => 'Teste Situacional Profissional — SJT',
                'description' => 'Avalia o julgamento profissional em situações práticas de trabalho. Cada cenário apresenta quatro ou cinco alternativas de conduta; o respondente indica a melhor e a pior resposta.',
                'type'        => 'sjt',
                'version'     => '1.0',
                'is_active'   => true,
                'disclaimer'  => 'Este relatório é uma ferramenta de apoio à gestão de pessoas, desenvolvimento profissional e análise organizacional. Os resultados são baseados nas respostas fornecidas no momento da aplicação e não constituem teste psicológico, laudo psicológico, diagnóstico clínico ou avaliação de saúde mental. As informações devem ser analisadas em conjunto com entrevistas, histórico profissional, requisitos da função, contexto de trabalho e demais evidências disponíveis.',
            ]
        );

        $dimensions = [
            ['slug' => 'julgamento-profissional',            'name' => 'Julgamento Profissional',                    'order' => 1],
            ['slug' => 'resolucao-de-problemas-sjt',         'name' => 'Resolução de Problemas',                     'order' => 2],
            ['slug' => 'comunicacao-situacoes-criticas',     'name' => 'Comunicação em Situações Críticas',           'order' => 3],
            ['slug' => 'responsabilidade-e-etica',           'name' => 'Responsabilidade e Ética Profissional',       'order' => 4],
        ];

        $dimModels = [];
        foreach ($dimensions as $dimData) {
            $dim = AssessmentDimension::updateOrCreate(
                ['assessment_test_id' => $test->id, 'slug' => $dimData['slug']],
                ['name' => $dimData['name'], 'weight' => 1.0, 'order' => $dimData['order']]
            );
            $dimModels[$dimData['slug']] = $dim;
        }

        // Estrutura: [dimensão, enunciado_do_cenário, [alternativas: [texto, score]]]
        $scenarios = [

            // ─── Julgamento Profissional ───────────────────────────────────────
            [
                'dim'       => 'julgamento-profissional',
                'statement' => 'Você recebe uma solicitação urgente de um cliente, mas percebe que o que ele pede vai contra uma política interna da empresa. O que você faz?',
                'options'   => [
                    ['Explica a política ao cliente, entende a necessidade real e busca uma alternativa dentro dos limites estabelecidos.', 100],
                    ['Consulta seu gestor antes de responder e aguarda orientação para agir.', 75],
                    ['Atende parcialmente o cliente, fazendo o que é possível dentro das regras, sem explicar a restrição.', 50],
                    ['Atende o cliente como ele pediu para não perder o relacionamento, sem comunicar à empresa.', 25],
                    ['Recusa a solicitação sem explicar o motivo, apenas informando que não é possível.', 0],
                ],
            ],
            [
                'dim'       => 'julgamento-profissional',
                'statement' => 'Você está em uma reunião e percebe que uma decisão está sendo tomada com base em uma informação incorreta que só você sabe que está errada. O que você faz?',
                'options'   => [
                    ['Intervém educadamente, apresenta a informação correta e explica o impacto para a decisão.', 100],
                    ['Aguarda o fim da reunião e comunica a informação ao responsável em particular.', 75],
                    ['Sinaliza que há algo a verificar, sem afirmar que está errado, para não constranger.', 50],
                    ['Não se manifesta durante a reunião, mas registra a discordância por e-mail depois.', 25],
                    ['Deixa passar para não criar conflito, mesmo sabendo que a decisão pode estar errada.', 0],
                ],
            ],
            [
                'dim'       => 'julgamento-profissional',
                'statement' => 'Você recebe duas demandas urgentes ao mesmo tempo, ambas de gestores diferentes, e não há tempo para atender as duas. O que você faz?',
                'options'   => [
                    ['Comunica a situação aos dois gestores, apresenta as prioridades e solicita que definam qual atender primeiro.', 100],
                    ['Avalia o impacto de cada demanda e inicia pela de maior prioridade, comunicando o outro gestor sobre o atraso.', 75],
                    ['Começa pelas duas ao mesmo tempo, mesmo sabendo que nenhuma ficará completa dentro do prazo.', 50],
                    ['Escolhe por conta própria qual atender sem comunicar o outro gestor.', 25],
                    ['Ignora as demandas até receber uma orientação clara de prioridade.', 0],
                ],
            ],
            [
                'dim'       => 'julgamento-profissional',
                'statement' => 'Você descobre que um colega está utilizando um recurso da empresa de forma irregular, mas sem causar prejuízo direto visível. O que você faz?',
                'options'   => [
                    ['Conversa com o colega diretamente, de forma respeitosa, sobre o uso correto do recurso.', 100],
                    ['Reporta a situação ao gestor de forma discreta, sem expor desnecessariamente o colega.', 75],
                    ['Observa por mais tempo antes de agir para entender melhor a situação.', 50],
                    ['Ignora, pois o impacto parece pequeno e não é responsabilidade sua fiscalizar.', 25],
                    ['Comenta com outros colegas antes de decidir o que fazer.', 0],
                ],
            ],
            [
                'dim'       => 'julgamento-profissional',
                'statement' => 'Você percebe que um processo que executa há meses tem uma etapa desnecessária que só gera retrabalho. O que você faz?',
                'options'   => [
                    ['Documenta a análise, propõe a melhoria ao gestor e sugere como implementar a mudança.', 100],
                    ['Comenta informalmente com o gestor para ver se ele tem interesse em revisar o processo.', 75],
                    ['Deixa de executar a etapa por conta própria, sem comunicar a equipe.', 25],
                    ['Continua executando como está para não gerar conflito ou resistência.', 50],
                    ['Reclama com colegas sobre o processo sem propor solução.', 0],
                ],
            ],

            // ─── Resolução de Problemas ───────────────────────────────────────
            [
                'dim'       => 'resolucao-de-problemas-sjt',
                'statement' => 'No meio de uma entrega importante, o sistema que você usa apresenta uma falha inesperada. O que você faz?',
                'options'   => [
                    ['Identifica se há uma solução de contorno, comunica o problema ao responsável técnico e continua o que for possível enquanto aguarda.', 100],
                    ['Para tudo, aguarda o suporte técnico resolver e retoma a entrega depois.', 75],
                    ['Tenta resolver o problema técnico por conta própria, mesmo sem ter certeza de como.', 50],
                    ['Comunica ao gestor e solicita extensão de prazo sem tentar nenhuma alternativa.', 25],
                    ['Ignora o problema e tenta entregar mesmo com a falha, sem comunicar ninguém.', 0],
                ],
            ],
            [
                'dim'       => 'resolucao-de-problemas-sjt',
                'statement' => 'Você identifica que um erro ocorrido em uma tarefa anterior pode impactar negativamente um cliente. O que você faz?',
                'options'   => [
                    ['Avalia o impacto, corrige o que for possível imediatamente e comunica o gestor e o cliente com transparência.', 100],
                    ['Corrige o erro antes que o cliente perceba, sem comunicar caso o impacto seja baixo.', 75],
                    ['Comunica o gestor e aguarda instrução antes de qualquer ação.', 50],
                    ['Aguarda que o cliente reclame antes de agir para evitar criar alarde desnecessário.', 25],
                    ['Não menciona o erro para ninguém, esperando que passe despercebido.', 0],
                ],
            ],
            [
                'dim'       => 'resolucao-de-problemas-sjt',
                'statement' => 'Você precisa resolver um problema que está fora do seu escopo técnico. O que você faz?',
                'options'   => [
                    ['Identifica quem pode resolver, articula o encaminhamento e acompanha até a conclusão.', 100],
                    ['Encaminha para a área responsável e informa o cliente ou gestor sobre o direcionamento.', 75],
                    ['Tenta resolver sozinho para não depender de outras áreas.', 50],
                    ['Informa que não é sua responsabilidade e encerra sua participação no problema.', 25],
                    ['Aguarda que alguém perceba e tome a iniciativa.', 0],
                ],
            ],
            [
                'dim'       => 'resolucao-de-problemas-sjt',
                'statement' => 'Uma tarefa rotineira começa a apresentar resultados fora do padrão esperado, mas sem uma causa óbvia. O que você faz?',
                'options'   => [
                    ['Analisa as possíveis causas, registra os padrões observados e comunica ao gestor com as hipóteses levantadas.', 100],
                    ['Testa as hipóteses mais prováveis por conta própria antes de escalar.', 75],
                    ['Espera mais alguns ciclos para ver se o desvio se repete antes de agir.', 50],
                    ['Reporta ao gestor sem investigar, pedindo que ele decida o que fazer.', 25],
                    ['Continua a tarefa normalmente, sem registrar ou comunicar o desvio.', 0],
                ],
            ],
            [
                'dim'       => 'resolucao-de-problemas-sjt',
                'statement' => 'Você precisa tomar uma decisão importante, mas não tem todas as informações necessárias. O que você faz?',
                'options'   => [
                    ['Levanta as informações disponíveis, identifica o que falta, busca o que for possível e decide com base no que tem, sinalizando as incertezas.', 100],
                    ['Consulta pessoas que possam ter as informações que faltam antes de decidir.', 75],
                    ['Decide com base no que tem, sem sinalizar as lacunas de informação.', 50],
                    ['Adia a decisão até ter todas as informações, mesmo que isso cause atraso.', 25],
                    ['Transfere a decisão para outra pessoa para evitar o risco de errar.', 0],
                ],
            ],

            // ─── Comunicação em Situações Críticas ────────────────────────────
            [
                'dim'       => 'comunicacao-situacoes-criticas',
                'statement' => 'Um cliente informa que recebeu uma orientação diferente de outro colaborador e está insatisfeito. Você não tem certeza sobre qual orientação está correta. O que você faz?',
                'options'   => [
                    ['Ouve o cliente, verifica a informação correta e retorna com clareza e cordialidade.', 100],
                    ['Pede um momento, consulta o responsável e alinha a resposta antes de orientar.', 75],
                    ['Dá uma resposta com base no que acredita estar correto, sem verificar.', 50],
                    ['Informa que o erro foi de outro colaborador e encerra o atendimento.', 25],
                    ['Ignora a reclamação, pois não foi você quem passou a orientação inicial.', 0],
                ],
            ],
            [
                'dim'       => 'comunicacao-situacoes-criticas',
                'statement' => 'Você precisa comunicar ao gestor que vai atrasar uma entrega importante. O que você faz?',
                'options'   => [
                    ['Comunica com antecedência, explica a causa, informa o novo prazo estimado e propõe um plano para minimizar o impacto.', 100],
                    ['Avisa assim que percebe o risco, mesmo sem ter ainda um novo prazo definido.', 75],
                    ['Aguarda o prazo original passar para ter mais certeza antes de comunicar.', 50],
                    ['Entrega o que tem no prazo original, mesmo que incompleto, sem comunicar antes.', 25],
                    ['Não comunica e espera que o gestor perceba sozinho.', 0],
                ],
            ],
            [
                'dim'       => 'comunicacao-situacoes-criticas',
                'statement' => 'Você discorda fortemente de uma decisão tomada pelo seu gestor. O que você faz?',
                'options'   => [
                    ['Solicita um momento para conversar, apresenta sua perspectiva com argumentos e respeita a decisão final do gestor.', 100],
                    ['Comunica sua discordância por escrito de forma respeitosa, registrando sua posição.', 75],
                    ['Aceita a decisão sem se manifestar, mas executa com baixo engajamento.', 50],
                    ['Comenta com colegas sobre a decisão antes de falar diretamente com o gestor.', 25],
                    ['Recusa-se a executar a decisão até que ela seja revista.', 0],
                ],
            ],
            [
                'dim'       => 'comunicacao-situacoes-criticas',
                'statement' => 'Dois colegas estão em conflito aberto e isso está impactando o trabalho da equipe. Você não é gestor. O que você faz?',
                'options'   => [
                    ['Conversa separadamente com cada um, ouve suas perspectivas e, se o conflito persistir, comunica o gestor.', 100],
                    ['Comunica o gestor diretamente para que ele tome a iniciativa de mediar.', 75],
                    ['Ignora o conflito pois não é sua responsabilidade intervir.', 50],
                    ['Toma o lado de quem você considera mais correto e defende essa posição abertamente.', 25],
                    ['Comenta o conflito com outros colegas para entender o que o time acha.', 0],
                ],
            ],
            [
                'dim'       => 'comunicacao-situacoes-criticas',
                'statement' => 'Você recebe um feedback negativo que considera injusto. O que você faz?',
                'options'   => [
                    ['Ouve o feedback completo, agradece, e solicita uma conversa para entender melhor os pontos levantados e apresentar sua perspectiva.', 100],
                    ['Aceita o feedback sem questionar, mas internamente continua acreditando que estava certo.', 75],
                    ['Responde na hora explicando que discorda e apresentando os motivos.', 50],
                    ['Demonstra visivelmente que está insatisfeito com o feedback recebido.', 25],
                    ['Ignora o feedback e continua agindo da mesma forma.', 0],
                ],
            ],

            // ─── Responsabilidade e Ética ──────────────────────────────────────
            [
                'dim'       => 'responsabilidade-e-etica',
                'statement' => 'Você cometeu um erro que ainda não foi percebido por ninguém, mas pode impactar uma entrega futura. O que você faz?',
                'options'   => [
                    ['Assume o erro imediatamente, comunica ao gestor e propõe como corrigir.', 100],
                    ['Corrige o erro por conta própria e comunica após a correção.', 75],
                    ['Aguarda para ver se o erro será percebido antes de se manifestar.', 50],
                    ['Corrige silenciosamente sem comunicar ninguém para não se expor.', 25],
                    ['Não faz nada, esperando que o erro passe despercebido.', 0],
                ],
            ],
            [
                'dim'       => 'responsabilidade-e-etica',
                'statement' => 'Você recebe uma solicitação para realizar uma tarefa que considera antiética, mas seu gestor insiste que é necessário. O que você faz?',
                'options'   => [
                    ['Recusa a tarefa de forma respeitosa, explica sua posição ética e propõe alternativas que estejam dentro dos seus limites.', 100],
                    ['Comunica formalmente sua objeção por escrito antes de qualquer ação.', 75],
                    ['Executa a tarefa com ressalvas, mas registra sua discordância.', 50],
                    ['Executa sem questionar por não se sentir em posição de discordar.', 25],
                    ['Executa e não comunica nada para não criar conflito.', 0],
                ],
            ],
            [
                'dim'       => 'responsabilidade-e-etica',
                'statement' => 'Você percebe que um prazo combinado com o cliente não poderá ser cumprido. O que você faz?',
                'options'   => [
                    ['Comunica o cliente o quanto antes, apresenta o motivo, o novo prazo estimado e as medidas para minimizar o impacto.', 100],
                    ['Informa o gestor primeiro e aguarda orientação sobre como comunicar ao cliente.', 75],
                    ['Tenta acelerar ao máximo para cumprir o prazo sem comunicar o risco.', 50],
                    ['Aguarda o prazo passar para então comunicar, apresentando uma justificativa.', 25],
                    ['Não comunica e entrega o que for possível no prazo, sem alertar sobre o que falta.', 0],
                ],
            ],
            [
                'dim'       => 'responsabilidade-e-etica',
                'statement' => 'Você tem acesso a uma informação confidencial da empresa que poderia beneficiar um amigo fora da organização. O que você faz?',
                'options'   => [
                    ['Não compartilha a informação sob nenhuma circunstância, por tratar-se de dado confidencial.', 100],
                    ['Orienta o amigo a buscar a informação pelos canais oficiais disponíveis.', 75],
                    ['Compartilha apenas parte da informação, considerando que o impacto seria pequeno.', 25],
                    ['Compartilha informalmente, pois confia que o amigo não vai usar de forma inadequada.', 0],
                    ['Consulta alguém de confiança dentro da empresa antes de decidir o que fazer.', 50],
                ],
            ],
            [
                'dim'       => 'responsabilidade-e-etica',
                'statement' => 'Você descobre que seu resultado em uma avaliação foi registrado incorretamente e o erro o beneficiou. O que você faz?',
                'options'   => [
                    ['Comunica a área responsável sobre o erro, mesmo que isso resulte em um resultado menor para você.', 100],
                    ['Aguarda para ver se alguém vai perceber o erro antes de se manifestar.', 50],
                    ['Não faz nada, pois o erro não foi causado por você.', 25],
                    ['Comenta com alguém de confiança para pedir opinião antes de decidir.', 75],
                    ['Mantém o resultado sem comunicar, por considerar que não causou o erro.', 0],
                ],
            ],
        ];

        $order = 1;
        foreach ($scenarios as $scenario) {
            $question = AssessmentQuestion::updateOrCreate(
                [
                    'assessment_test_id'      => $test->id,
                    'assessment_dimension_id' => $dimModels[$scenario['dim']]->id,
                    'statement'               => $scenario['statement'],
                ],
                [
                    'question_type'      => 'sjt_pair',
                    'scale_min'          => 0,
                    'scale_max'          => 100,
                    'is_reverse'         => false,
                    'weight'             => 1.0,
                    'is_attention_check' => false,
                    'order'              => $order++,
                ]
            );

            $optOrder = 1;
            foreach ($scenario['options'] as [$text, $score]) {
                AssessmentOption::updateOrCreate(
                    ['assessment_question_id' => $question->id, 'text' => $text],
                    ['score' => $score, 'label' => null, 'order' => $optOrder++]
                );
            }
        }

        $this->command->info('✓ SJT: ' . ($order - 1) . ' cenários em 4 dimensões.');
    }
}
