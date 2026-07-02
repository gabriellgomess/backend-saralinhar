<?php

namespace Database\Seeders;

use App\Models\InterviewArea;
use App\Models\InterviewQuestion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Popula áreas e perguntas iniciais do EntrevistaPro AI.
 *
 * Idempotente (updateOrCreate): pode rodar mais de uma vez sem duplicar.
 * Rodar com: php artisan db:seed --class=InterviewContentSeeder
 */
class InterviewContentSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            ['name' => 'Administrativo', 'icon' => 'file-tray-full-outline'],
            ['name' => 'Comercial / Vendas', 'icon' => 'trending-up-outline'],
            ['name' => 'Recursos Humanos', 'icon' => 'people-outline'],
            ['name' => 'Fiscal / Contábil', 'icon' => 'calculator-outline'],
            ['name' => 'Atendimento', 'icon' => 'headset-outline'],
            ['name' => 'Liderança', 'icon' => 'ribbon-outline'],
            ['name' => 'Operacional', 'icon' => 'construct-outline'],
            ['name' => 'Tecnologia', 'icon' => 'code-slash-outline'],
            ['name' => 'Financeiro / Bancário', 'icon' => 'cash-outline'],
            ['name' => 'Marketing / Comunicação', 'icon' => 'megaphone-outline'],
            ['name' => 'Logística / Suprimentos', 'icon' => 'cube-outline'],
            ['name' => 'Saúde', 'icon' => 'medkit-outline'],
            ['name' => 'Educação', 'icon' => 'school-outline'],
            ['name' => 'Jurídico', 'icon' => 'briefcase-outline'],
            ['name' => 'Engenharia / Produção', 'icon' => 'settings-outline'],
            ['name' => 'Estágio / 1º emprego', 'icon' => 'rocket-outline'],
        ];

        $areaIds = []; // name => id
        foreach ($areas as $index => $data) {
            $area = InterviewArea::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'icon' => $data['icon'],
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
            $areaIds[$data['name']] = $area->id;
        }

        // Perguntas gerais (exibidas em todas as áreas)
        $general = [
            'Fale um pouco sobre você.',
            'Por que você se interessou por essa vaga?',
            'Quais são seus principais pontos fortes?',
            'Conte sobre um desafio profissional que você já enfrentou.',
            'Qual sua pretensão salarial?',
            'Por que saiu do último emprego?',
        ];

        foreach ($general as $index => $text) {
            InterviewQuestion::updateOrCreate(
                ['text' => $text, 'interview_area_id' => null],
                ['is_active' => true, 'sort_order' => $index]
            );
        }

        // Perguntas específicas por área (nome => perguntas)
        $specific = [
            'Administrativo' => [
                'Como você organiza suas tarefas quando há várias demandas ao mesmo tempo?',
                'Quais ferramentas você utiliza no dia a dia administrativo?',
            ],
            'Comercial / Vendas' => [
                'Como você lida com a objeção de um cliente que acha o preço alto?',
                'Conte sobre uma meta desafiadora que você atingiu.',
            ],
            'Recursos Humanos' => [
                'Como você conduziria um processo seletivo do início ao fim?',
                'Como você lidaria com um conflito entre colaboradores?',
            ],
            'Fiscal / Contábil' => [
                'Como você se mantém atualizado sobre mudanças na legislação?',
                'Conte sobre um erro fiscal que você identificou e corrigiu.',
            ],
            'Atendimento' => [
                'Como você lida com um cliente insatisfeito e alterado?',
                'O que é um bom atendimento para você?',
            ],
            'Liderança' => [
                'Como você motiva uma equipe em um momento difícil?',
                'Conte sobre uma decisão impopular que você precisou tomar.',
            ],
            'Operacional' => [
                'Como você garante qualidade e segurança na sua rotina de trabalho?',
                'Conte sobre uma melhoria de processo que você sugeriu.',
            ],
            'Tecnologia' => [
                'Conte sobre um projeto técnico do qual você se orgulha.',
                'Como você se mantém atualizado com novas tecnologias?',
            ],
            'Financeiro / Bancário' => [
                'Como você organiza uma rotina de contas a pagar e receber?',
                'Conte sobre uma análise financeira que ajudou em uma decisão.',
            ],
            'Marketing / Comunicação' => [
                'Conte sobre uma campanha ou ação que gerou bons resultados.',
                'Como você mede o sucesso de uma ação de marketing?',
            ],
            'Logística / Suprimentos' => [
                'Como você agiria diante de um atraso crítico de entrega?',
                'Que indicadores você acompanha na operação logística?',
            ],
            'Saúde' => [
                'Como você lida com situações de pressão e urgência?',
                'Como você garante a humanização no atendimento ao paciente?',
            ],
            'Educação' => [
                'Como você lida com turmas ou alunos com níveis muito diferentes?',
                'Conte sobre uma estratégia pedagógica que funcionou bem.',
            ],
            'Jurídico' => [
                'Como você organiza prazos processuais para não perder nenhum?',
                'Conte sobre um caso ou parecer desafiador em que atuou.',
            ],
            'Engenharia / Produção' => [
                'Conte sobre um projeto em que precisou equilibrar prazo, custo e qualidade.',
                'Como você age ao identificar uma falha de segurança ou de processo?',
            ],
            'Estágio / 1º emprego' => [
                'O que você busca aprender nesta primeira experiência?',
                'Fale sobre um trabalho acadêmico ou projeto pessoal do qual se orgulha.',
            ],
        ];

        foreach ($specific as $areaName => $questions) {
            if (!isset($areaIds[$areaName])) {
                continue;
            }
            foreach ($questions as $index => $text) {
                InterviewQuestion::updateOrCreate(
                    ['text' => $text, 'interview_area_id' => $areaIds[$areaName]],
                    ['is_active' => true, 'sort_order' => $index]
                );
            }
        }
    }
}
