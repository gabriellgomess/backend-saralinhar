<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Tecnologia da Informação',
                'description' => 'Desenvolvimento de software, infraestrutura, segurança da informação, análise de sistemas e suporte técnico',
            ],
            [
                'name' => 'Administração',
                'description' => 'Gestão administrativa, recursos humanos, contabilidade e finanças',
            ],
            [
                'name' => 'Vendas e Marketing',
                'description' => 'Vendas, marketing digital, publicidade, relações públicas e atendimento ao cliente',
            ],
            [
                'name' => 'Saúde',
                'description' => 'Medicina, enfermagem, fisioterapia, nutrição e outras áreas da saúde',
            ],
            [
                'name' => 'Educação',
                'description' => 'Professores, coordenadores pedagógicos, instrutores e educadores',
            ],
            [
                'name' => 'Engenharia',
                'description' => 'Engenharia civil, mecânica, elétrica, produção e outras especialidades',
            ],
            [
                'name' => 'Arquitetura e Design',
                'description' => 'Arquitetura, design gráfico, design de interiores e design de produtos',
            ],
            [
                'name' => 'Direito',
                'description' => 'Advocacia, consultoria jurídica, compliance e áreas correlatas',
            ],
            [
                'name' => 'Logística e Transporte',
                'description' => 'Gestão de suprimentos, armazenagem, transporte e distribuição',
            ],
            [
                'name' => 'Recursos Humanos',
                'description' => 'Recrutamento e seleção, treinamento, desenvolvimento organizacional e gestão de pessoas',
            ],
            [
                'name' => 'Comunicação',
                'description' => 'Jornalismo, relações públicas, assessoria de imprensa e comunicação corporativa',
            ],
            [
                'name' => 'Indústria e Produção',
                'description' => 'Operação de máquinas, controle de qualidade, supervisão de produção e manutenção',
            ],
            [
                'name' => 'Serviços Gerais',
                'description' => 'Limpeza, portaria, manutenção, segurança e serviços auxiliares',
            ],
            [
                'name' => 'Gastronomia e Hotelaria',
                'description' => 'Cozinha, atendimento, hotelaria, turismo e eventos',
            ],
            [
                'name' => 'Beleza e Estética',
                'description' => 'Cabeleireiro, manicure, esteticista, maquiador e áreas relacionadas',
            ],
            [
                'name' => 'Construção Civil',
                'description' => 'Pedreiro, eletricista, encanador, carpinteiro e outras funções da construção',
            ],
            [
                'name' => 'Agricultura e Pecuária',
                'description' => 'Agronomia, veterinária, produção agrícola e gestão rural',
            ],
            [
                'name' => 'Varejo e Comércio',
                'description' => 'Atendimento em lojas, caixa, repositor, gerência de loja e vendas',
            ],
            [
                'name' => 'Consultoria',
                'description' => 'Consultoria empresarial, estratégica, financeira e de gestão',
            ],
            [
                'name' => 'Artes e Entretenimento',
                'description' => 'Música, teatro, cinema, artes plásticas e produção cultural',
            ],
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'description' => $category['description'],
                'is_active' => true,
            ]);
        }
    }
}
