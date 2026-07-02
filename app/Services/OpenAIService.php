<?php

namespace App\Services;

use App\Models\OpenAIUsageLog;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    protected $apiKey;
    protected $apiUrl;
    protected $modelTranscription;
    protected $modelContactExtract;
    protected $modelResumeAnalysis;
    protected $modelReport;
    protected $modelPlayer;
    protected $modelValidation;
    protected $modelDisc;
    protected $modelOcr;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->apiUrl = 'https://api.openai.com/v1/chat/completions';

        $models = config('services.openai.models', []);
        $this->modelTranscription  = $models['transcription']   ?? 'gpt-4o-transcribe';
        $this->modelContactExtract = $models['contact_extract'] ?? 'gpt-5-nano';
        $this->modelResumeAnalysis = $models['resume_analysis'] ?? 'gpt-5.4-nano';
        $this->modelReport         = $models['report']          ?? 'gpt-5.4-nano';
        $this->modelPlayer         = $models['player']          ?? 'gpt-5.4-nano';
        $this->modelValidation     = $models['validation']      ?? 'gpt-5-nano';
        $this->modelDisc           = $models['disc']            ?? 'gpt-5.4-nano';
        $this->modelOcr            = $models['ocr']             ?? 'gpt-5.4-nano';
    }

    /**
     * Transcreve áudio usando Whisper
     */
    public function transcribeAudio(string $filePath): string
    {
        $t0 = microtime(true);
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->timeout(300)->attach(
                'file',
                file_get_contents($filePath),
                basename($filePath)
            )->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => $this->modelTranscription,
                'language' => 'pt',
            ]);

            $this->recordUsage(OpenAIUsageLog::FEATURE_TRANSCRIPTION, $this->modelTranscription, 'audio.transcriptions', $t0, $response, [
                'metadata' => ['file' => basename($filePath), 'size_bytes' => @filesize($filePath) ?: null],
            ]);

            if ($response->successful()) {
                return $response->json()['text'];
            }

            Log::error('OpenAI Whisper Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            throw new \Exception('Falha na transcrição do áudio');
        } catch (\Exception $e) {
            $this->recordUsage(OpenAIUsageLog::FEATURE_TRANSCRIPTION, $this->modelTranscription, 'audio.transcriptions', $t0, null, [
                'metadata' => ['file' => basename($filePath)],
            ], $e);
            Log::error('OpenAI Whisper Exception', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Estrutura o parecer do candidato a partir do texto
     */
    public function structureCandidateReport(string $text, ?string $resumeText = null, ?string $complementaryPrompt = null): array
    {
        $t0 = microtime(true);
        try {
            $prompt = $this->buildReportStructurePrompt($text, $resumeText, $complementaryPrompt);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($this->apiUrl, [
                'model' => $this->modelReport,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Você é um especialista em recrutamento e seleção da consultoria Sara Linhar DHO RH. Sua tarefa é estruturar anotações de entrevista e currículo no formato formal da consultoria Sara Linhar. Responda estritamente em formato JSON.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

            $this->recordUsage(OpenAIUsageLog::FEATURE_CANDIDATE_REPORT, $this->modelReport, 'chat.completions', $t0, $response);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'];
                return $this->parseReportStructureResponse($content);
            }

            Log::error('OpenAI Report Structure Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $this->getDefaultReportStructure();
        } catch (\Exception $e) {
            $this->recordUsage(OpenAIUsageLog::FEATURE_CANDIDATE_REPORT, $this->modelReport, 'chat.completions', $t0, null, [], $e);
            Log::error('OpenAI Report Structure Exception', ['message' => $e->getMessage()]);
            return $this->getDefaultReportStructure();
        }
    }

    protected function buildReportStructurePrompt(string $text, ?string $resumeText = null, ?string $complementaryPrompt = null): string
    {
        $prompt = "Você está analisando a transcrição de uma entrevista e o currículo do candidato. Sua tarefa é estruturar o parecer de entrevista no formato formal da consultoria Sara Linhar DHO RH.

RELATO DO RECRUTADOR (TRANSCRIÇÃO):
{$text}
";
        
        if ($resumeText) {
            $prompt .= "\nCURRÍCULO DO CANDIDATO (fonte complementar):\n{$resumeText}\n";
        }

        $prompt .= "

Gere um JSON com os seguintes campos. Extraia as informações com precisão. Se alguma informação não estiver disponível, preencha com string vazia (\"\").

{
    \"summary\": \"[Resumo Profissional geral em texto corrido]\",
    \"technical_skills\": [\"Competência 1\", \"Competência 2\"],
    \"behavioral_posture\": \"[Postura Comportamental em texto corrido]\",
    \"strengths\": [\"Ponto Forte 1\", \"Ponto Forte 2\"],
    \"development_points\": [\"Ponto a Desenvolver 1\"],
    \"final_opinion\": \"[Parecer Final do entrevistador]\",
    \"status\": \"recommended | recommended_with_reservations | not_recommended\",
    \"sara_data\": {
        \"personal_info\": {
            \"name\": \"[Nome completo do candidato]\",
            \"age\": \"[Idade do candidato, ex: '28 anos']\",
            \"marital_status\": \"[Estado civil, ex: 'Solteiro']\",
            \"city\": \"[Cidade]\",
            \"neighborhood\": \"[Bairro]\",
            \"children\": \"[Possui filhos e suas idades, ex: 'Sim, 1 filho (3 anos)' ou 'Não possui']\",
            \"family_base\": \"[Base familiar/com quem reside, ex: 'Reside com a esposa']\",
            \"hobbies\": \"[Hobbies / interesses, ex: 'Leitura, futebol']\"
        },
        \"experiences\": [
            {
                \"company\": \"[Nome da empresa]\",
                \"role\": \"[Cargo exercido]\",
                \"period\": \"[Período, ex: '05/2023 - Atual' ou '02/2021 - 04/2023']\",
                \"activities\": \"[Principais atividades desenvolvidas]\",
                \"exit_reason\": \"[Motivo da saída]\",
                \"salary_benefits\": \"[Última Remuneração/Benefícios]\"
            }
        ],
        \"experience_extras\": \"[Informações adicionais de experiências, observações extras sobre o histórico ou outras experiências menores]\",
        \"education_development\": {
            \"education\": \"[Escolaridade, ex: 'Ensino Superior Completo em Administração']\",
            \"courses\": \"[Cursos e qualificações adicionais realizados]\",
            \"certifications\": \"[Certificações profissionais relevantes]\",
            \"tools_systems\": \"[Ferramentas / sistemas dominados, ex: 'Excel intermediário, sistema SAP']\"
        },
        \"professional_personal_evaluation\": {
            \"professional_self_description\": \"[Como o candidato se descreve como profissional]\",
            \"strengths_text\": \"[Pontos fortes destacados, ex: 'Determinação, organização']\",
            \"overcome_challenge\": \"[Desafio superado relatado pelo candidato]\",
            \"development_text\": \"[Pontos a desenvolver indicados pelo candidato ou pelo recrutador]\",
            \"ideal_work_environment\": \"[Ambiente ideal para trabalhar de acordo com o candidato]\"
        },
        \"financial\": {
            \"salary_expectation\": \"[Pretensão salarial]\",
            \"pj_or_clt\": \"[Preferência por PJ ou CLT, ex: 'CLT']\",
            \"benefits_expectation\": \"[Pretensão de benefícios]\"
        },
        \"logistics_availability\": {
            \"commute\": \"[Deslocamento / logística de transporte]\",
            \"schedule_availability\": \"[Disponibilidade de horários]\",
            \"start_availability\": \"[Disponibilidade de Início / Aviso prévio]\"
        },
        \"interviewer_evaluation\": {
            \"interviewer_communication\": \"[Avaliação detalhada da Comunicação/Clareza do candidato]\",
            \"interviewer_posture\": \"[Avaliação detalhada da Postura/Engajamento do candidato]\"
        }
    }
}

REGRAS DE ESTRUTURAÇÃO IMPORTANTES:
1. ORDENAÇÃO DE EXPERIÊNCIAS (BLOCO 2): As experiências na lista 'sara_data.experiences' DEVEM ser ordenadas cronologicamente do mais recente para o mais antigo.
2. ADIÇÃO DE BLOCOS: Adicione um objeto completo (com os campos company, role, period, activities, exit_reason, salary_benefits) para CADA experiência profissional que for identificada no relato e no currículo. Adicione tantos blocos de experiência quantos forem encontrados. Se houver detalhes extras ou outras experiências curtas, use o campo 'experience_extras'.
3. NÃO INVENTE INFORMAÇÕES: Use apenas informações reais baseadas no relato do recrutador e no currículo. Se um dado não estiver disponível, preencha com string vazia (\"\").
4. NUNCA comente lacunas da entrevista dizendo 'não foi perguntado', 'não foi possível avaliar', etc. Estruture apenas o que foi observado.
5. Os campos summary, technical_skills, behavioral_posture, strengths, development_points, final_opinion e status no nível raiz do JSON devem ser preenchidos de forma coerente com o restante dos dados do parecer, para garantir retrocompatibilidade com o sistema legados. Por exemplo:
   - 'summary' deve conter o Resumo Profissional geral.
   - 'technical_skills' deve conter um array de competências tiradas do currículo ou relato.
   - 'behavioral_posture' deve resumir a postura do candidato.
   - 'strengths' deve ser um array dos pontos fortes de 'strengths_text'.
   - 'development_points' deve ser um array dos pontos a desenvolver.
   - 'final_opinion' deve resumir a avaliação global do entrevistador.";

        if ($complementaryPrompt) {
            $prompt .= "\n\nINSTRUÇÕES COMPLEMENTARES DO RECRUTADOR (prioridade máxima — siga estas instruções específicas ao estruturar o parecer):\n{$complementaryPrompt}";
        }

        return $prompt;
    }

    protected function parseReportStructureResponse(string $content): array
    {
        $content = preg_replace('/```json\s*|\s*```/', '', $content);
        $content = trim($content);

        try {
            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                Log::error('JSON Parse Error (Sara)', [
                    'error' => json_last_error_msg(),
                    'content_preview' => substr($content, 0, 200),
                ]);
                return $this->getDefaultReportStructure();
            }

            $data['technical_skills'] = is_array($data['technical_skills'] ?? null) ? $data['technical_skills'] : [];
            $data['strengths'] = is_array($data['strengths'] ?? null) ? $data['strengths'] : [];
            $data['development_points'] = is_array($data['development_points'] ?? null) ? $data['development_points'] : [];
            $data['sara_data'] = is_array($data['sara_data'] ?? null) ? $data['sara_data'] : [];

            // Garante campos default dentro de sara_data
            $data['sara_data'] = array_merge($this->getDefaultSaraDataStructure(), $data['sara_data']);

            return $data;
        } catch (\Exception $e) {
            Log::error('Parse Sara Report Exception', ['message' => $e->getMessage()]);
            return $this->getDefaultReportStructure();
        }
    }

    protected function getDefaultReportStructure(): array
    {
        return [
            'summary' => '',
            'technical_skills' => [],
            'behavioral_posture' => '',
            'strengths' => [],
            'development_points' => [],
            'final_opinion' => '',
            'status' => 'recommended_with_reservations',
            'sara_data' => $this->getDefaultSaraDataStructure(),
        ];
    }

    protected function getDefaultSaraDataStructure(): array
    {
        return [
            'personal_info' => [
                'name' => '',
                'age' => '',
                'marital_status' => '',
                'city' => '',
                'neighborhood' => '',
                'children' => '',
                'family_base' => '',
                'hobbies' => '',
            ],
            'experiences' => [],
            'experience_extras' => '',
            'education_development' => [
                'education' => '',
                'courses' => '',
                'certifications' => '',
                'tools_systems' => '',
            ],
            'professional_personal_evaluation' => [
                'professional_self_description' => '',
                'strengths_text' => '',
                'overcome_challenge' => '',
                'development_text' => '',
                'ideal_work_environment' => '',
            ],
            'financial' => [
                'salary_expectation' => '',
                'pj_or_clt' => '',
                'benefits_expectation' => '',
            ],
            'logistics_availability' => [
                'commute' => '',
                'schedule_availability' => '',
                'start_availability' => '',
            ],
            'interviewer_evaluation' => [
                'interviewer_communication' => '',
                'interviewer_posture' => '',
            ],
        ];
    }
    /**
     * ============================================================
     * AGENTE PLAYER - Reestrutura parecer Sara no formato Player
     * ============================================================
     */

    /**
     * Reestrutura os dados do parecer Sara para o formato Player
     */
    public function structurePlayerReport(array $reportData, ?string $rawTranscription = null, ?string $complementaryPrompt = null): array
    {
        $t0 = microtime(true);
        try {
            $prompt = $this->buildPlayerReportPrompt($reportData, $rawTranscription, $complementaryPrompt);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($this->apiUrl, [
                'model' => $this->modelPlayer,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Você é um especialista em recrutamento e seleção. Sua tarefa é reestruturar um parecer de entrevista existente no formato específico da consultoria Player. Você deve extrair e organizar as informações em campos individuais. Responda estritamente em formato JSON.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

            $this->recordUsage(OpenAIUsageLog::FEATURE_PLAYER_REPORT, $this->modelPlayer, 'chat.completions', $t0, $response);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'];
                return $this->parsePlayerReportResponse($content);
            }

            Log::error('OpenAI Player Report Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $this->getDefaultPlayerStructure();
        } catch (\Exception $e) {
            $this->recordUsage(OpenAIUsageLog::FEATURE_PLAYER_REPORT, $this->modelPlayer, 'chat.completions', $t0, null, [], $e);
            Log::error('OpenAI Player Report Exception', ['message' => $e->getMessage()]);
            return $this->getDefaultPlayerStructure();
        }
    }

    protected function buildPlayerReportPrompt(array $data, ?string $rawTranscription = null, ?string $complementaryPrompt = null): string
    {
        $candidateName = $data['candidate_name'] ?? 'N/A';
        $interviewerName = $data['interviewer_name'] ?? 'N/A';
        $interviewDate = $data['interview_date'] ?? 'N/A';
        $jobTitle = $data['job_title'] ?? 'N/A';
        $clientName = $data['client_name'] ?? 'N/A';
        $summary = $data['summary'] ?? '';
        $behavioralPosture = $data['behavioral_posture'] ?? '';
        $technicalSkills = is_array($data['technical_skills'] ?? null) ? implode(', ', $data['technical_skills']) : ($data['technical_skills'] ?? '');
        $strengths = is_array($data['strengths'] ?? null) ? implode(', ', $data['strengths']) : ($data['strengths'] ?? '');
        $developmentPoints = is_array($data['development_points'] ?? null) ? implode(', ', $data['development_points']) : ($data['development_points'] ?? '');
        $finalOpinion = $data['final_opinion'] ?? '';
        $status = $data['status'] ?? '';

        $prompt = "Tenho um parecer de entrevista já gerado com os seguintes dados. Preciso que você reorganize essas informações no formato da consultoria Player, extraindo dados pessoais e profissionais em campos individuais.

DADOS DO PARECER EXISTENTE:
- Candidato: {$candidateName}
- Cargo/Vaga: {$jobTitle}
- Cliente: {$clientName}
- Entrevistador: {$interviewerName}
- Data: {$interviewDate}
- Resumo Profissional: {$summary}
- Postura Comportamental: {$behavioralPosture}
- Competências Técnicas: {$technicalSkills}
- Pontos Fortes: {$strengths}
- Pontos a Desenvolver: {$developmentPoints}
- Parecer Final: {$finalOpinion}
- Status: {$status}

Gere um JSON com os seguintes campos, EXTRAINDO as informações do parecer acima. Se alguma informação não estiver disponível, preencha com string vazia (\"\").

{
    \"age\": \"[Idade do candidato, ex: '32 anos.' - extrair do resumo se mencionado]\",
    \"children\": \"[Informação sobre filhos, ex: 'sem filhos.' ou '2 filhos.' - extrair do resumo se mencionado]\",
    \"marital_status\": \"[Estado civil, ex: 'Solteira.', 'Casado.' - extrair do resumo se mencionado]\",
    \"address\": \"[Endereço/Bairro/Cidade, ex: 'Porto Alegre, Menino Deus' - extrair do resumo se mencionado]\",
    \"commute\": \"[Deslocamento, ex: 'Transporte público.', 'Carro próprio.' - extrair do resumo se mencionado]\",
    \"education\": \"[Escolaridade, ex: 'Ensino Superior em Direito' - extrair do resumo se mencionado]\",
    \"salary_expectation\": \"[Pretensão salarial, ex: '6.000,00 PJ, está de acordo.' - extrair do resumo se mencionado]\",
    \"professional_experience\": \"[Texto narrativo sobre a experiência profissional atual e trajetória, 2-4 frases]\",
    \"work_history\": [
        {
            \"company\": \"[Nome da empresa]\",
            \"location\": \"[Localização]\",
            \"period\": \"[Período, ex: 'Out/2023 a Set/2024']\",
            \"role\": \"[Cargo exercido]\",
            \"activities\": \"[Atividades desenvolvidas]\",
            \"exit_reason\": \"[Motivo de saída]\"
        }
    ],
    \"strengths_text\": \"[Pontos fortes em uma frase curta, ex: 'Simpática, comunicativa e determinada.']\",
    \"development_text\": \"[Pontos a desenvolver em uma frase curta]\",
    \"professional_challenge\": \"[Desafio profissional identificado]\",
    \"final_summary\": \"[Resumo final detalhado - 2 a 3 parágrafos analisando perfil, aderência à vaga, experiência, formação e comportamento. Profissional e em 3ª pessoa.]\",
    \"fit_percentage\": [número inteiro de 0 a 100],
    \"fit_label\": \"[ex: 'Alta Aderência', 'Boa Aderência', 'Aderência Moderada', 'Baixa Aderência']\",
    \"final_conclusion\": \"[Frase de conclusão em 1ª pessoa, ex: 'Considero a candidata X apta para a continuidade do processo seletivo.']\"
}

REGRAS:
1. 'work_history' deve ser um array de objetos. Se nenhuma experiência for mencionada, retorne [].
2. O 'final_summary' deve ser o texto mais elaborado, com 2-3 parágrafos.
3. Se status='recommended', fit entre 75-95%. Se 'recommended_with_reservations', entre 55-74%. Se 'not_recommended', entre 20-50%.
4. Deixe em branco/string vazia (\"\") nos campos individuais (age, children, marital_status, address, commute, education, salary_expectation, professional_experience, strengths_text, development_text, professional_challenge, final_summary, final_conclusion) quando a informação realmente não existir.
5. 'fit_percentage' deve ser um número inteiro, não string.
6. Em 'final_summary', 'professional_experience', 'professional_challenge', 'strengths_text' e 'development_text': NUNCA comente lacunas da entrevista. É proibido escrever frases como 'o recrutador não validou X', 'não foi constatado Y', 'não foi possível avaliar Z', 'não foi explorado', 'não foi questionado'. Esses textos descrevem o que FOI observado — nunca o que deixou de ser. Se algo não foi mencionado, simplesmente omita o tópico.
7. NÃO INVENTE NADA. Use apenas o que está no parecer fornecido e na transcrição. Não preencha lacunas com suposições.";

        if ($rawTranscription) {
            $prompt .= "\n\nTRANSCRIÇÃO ORIGINAL DO RECRUTADOR (fonte primária — use para preencher dados ausentes ou corrigir inconsistências no parecer acima):\n{$rawTranscription}\n\nINSTRUÇÃO CRÍTICA: A transcrição acima tem prioridade sobre o parecer estruturado. Se encontrar empresas, datas, dados pessoais ou qualquer informação na transcrição que não esteja no parecer, INCLUA no JSON gerado. Especialmente em work_history: liste TODAS as empresas mencionadas na transcrição, mesmo que não estejam no parecer estruturado.";
        }

        if ($complementaryPrompt) {
            $prompt .= "\n\nINSTRUÇÕES COMPLEMENTARES DO RECRUTADOR (prioridade máxima — siga estas instruções específicas ao reorganizar o parecer):\n{$complementaryPrompt}";
        }

        return $prompt;
    }

    protected function parsePlayerReportResponse(string $content): array
    {
        $content = preg_replace('/```json\s*|\s*```/', '', $content);
        $content = trim($content);

        try {
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                Log::error('JSON Parse Error (Player)', [
                    'error' => json_last_error_msg(),
                    'content_preview' => substr($content, 0, 200),
                ]);
                return $this->getDefaultPlayerStructure();
            }

            $data['work_history'] = is_array($data['work_history'] ?? null) ? $data['work_history'] : [];
            $data['fit_percentage'] = intval($data['fit_percentage'] ?? 50);

            return $data;
        } catch (\Exception $e) {
            Log::error('Parse Player Report Exception', ['message' => $e->getMessage()]);
            return $this->getDefaultPlayerStructure();
        }
    }

    protected function getDefaultPlayerStructure(): array
    {
        return [
            'age' => '',
            'children' => '',
            'marital_status' => '',
            'address' => '',
            'commute' => '',
            'education' => '',
            'salary_expectation' => '',
            'professional_experience' => '',
            'work_history' => [],
            'strengths_text' => '',
            'development_text' => '',
            'professional_challenge' => '',
            'final_summary' => '',
            'fit_percentage' => 50,
            'fit_label' => 'Aderência Moderada',
            'final_conclusion' => '',
        ];
    }

    /**
     * Gera o parecer Player direto da transcrição (fluxo independente do parecer Sara).
     *
     * @param string      $transcription       Transcrição da entrevista (fonte primária)
     * @param string|null $resumeText          Texto do currículo (opcional, complementar)
     * @param string|null $complementaryPrompt Instruções adicionais do recrutador
     * @param array       $context             Dados de cabeçalho: candidate_name, interviewer_name, interview_date, job_title, client_name
     */
    public function structurePlayerReportFromTranscription(string $transcription, ?string $resumeText = null, ?string $complementaryPrompt = null, array $context = []): array
    {
        $t0 = microtime(true);
        try {
            $prompt = $this->buildPlayerReportPromptFromTranscription($transcription, $resumeText, $complementaryPrompt, $context);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($this->apiUrl, [
                'model' => $this->modelPlayer,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Você é um especialista em recrutamento e seleção da consultoria Player. Sua tarefa é analisar a transcrição de uma entrevista e estruturar o parecer no formato Player, extraindo e organizando as informações em campos individuais. Responda estritamente em formato JSON.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

            $this->recordUsage(OpenAIUsageLog::FEATURE_PLAYER_REPORT_FROM_TRANSCRIPTION, $this->modelPlayer, 'chat.completions', $t0, $response);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'];
                return $this->parsePlayerReportResponse($content);
            }

            Log::error('OpenAI Player Report (Transcription) Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $this->getDefaultPlayerStructure();
        } catch (\Exception $e) {
            $this->recordUsage(OpenAIUsageLog::FEATURE_PLAYER_REPORT_FROM_TRANSCRIPTION, $this->modelPlayer, 'chat.completions', $t0, null, [], $e);
            Log::error('OpenAI Player Report (Transcription) Exception', ['message' => $e->getMessage()]);
            return $this->getDefaultPlayerStructure();
        }
    }

    protected function buildPlayerReportPromptFromTranscription(string $transcription, ?string $resumeText, ?string $complementaryPrompt, array $context): string
    {
        $candidateName   = $context['candidate_name']   ?? 'N/A';
        $interviewerName = $context['interviewer_name'] ?? 'N/A';
        $interviewDate   = $context['interview_date']   ?? 'N/A';
        $jobTitle        = $context['job_title']        ?? 'N/A';
        $clientName      = $context['client_name']      ?? 'N/A';

        $prompt = "Você está analisando a transcrição de uma entrevista de recrutamento. Sua tarefa é estruturar o parecer no formato da consultoria Player, extraindo dados pessoais, profissionais e comportamentais a partir da transcrição.

DADOS DA ENTREVISTA:
- Candidato: {$candidateName}
- Cargo/Vaga: {$jobTitle}
- Cliente: {$clientName}
- Entrevistador: {$interviewerName}
- Data: {$interviewDate}

TRANSCRIÇÃO DA ENTREVISTA (fonte primária — toda informação extraída deve vir daqui):
{$transcription}";

        if ($resumeText) {
            $prompt .= "\n\nCURRÍCULO DO CANDIDATO (fonte complementar — pode reforçar ou completar dados da transcrição, mas a transcrição tem prioridade em caso de conflito):\n{$resumeText}";
        }

        $prompt .= "

Gere um JSON com os seguintes campos, EXTRAINDO as informações da transcrição (e do currículo, se fornecido). Se alguma informação não estiver disponível, preencha com string vazia (\"\") nos campos pessoais.

{
    \"age\": \"Idade do candidato, ex: '32 anos.'\",
    \"children\": \"Informação sobre filhos, ex: 'sem filhos.' ou '2 filhos.'\",
    \"marital_status\": \"Estado civil, ex: 'Solteira.', 'Casado.'\",
    \"address\": \"Endereço/Bairro/Cidade, ex: 'Porto Alegre, Menino Deus'\",
    \"commute\": \"Deslocamento, ex: 'Transporte público.', 'Carro próprio.'\",
    \"education\": \"Escolaridade, ex: 'Ensino Superior em Direito'\",
    \"salary_expectation\": \"Pretensão salarial, ex: '6.000,00 PJ, está de acordo.'\",
    \"professional_experience\": \"Texto narrativo sobre a experiência profissional atual e trajetória, 2-4 frases\",
    \"work_history\": [
        {
            \"company\": \"Nome da empresa\",
            \"location\": \"Localização\",
            \"period\": \"Período, ex: 'Out/2023 a Set/2024'\",
            \"role\": \"Cargo exercido\",
            \"activities\": \"Atividades desenvolvidas\",
            \"exit_reason\": \"Motivo de saída\"
        }
    ],
    \"strengths_text\": \"Pontos fortes em uma frase curta, ex: 'Simpática, comunicativa e determinada.'\",
    \"development_text\": \"Pontos a desenvolver em uma frase curta\",
    \"professional_challenge\": \"Desafio profissional identificado\",
    \"final_summary\": \"Resumo final detalhado — 2 a 3 parágrafos analisando perfil, aderência à vaga, experiência, formação e comportamento. Profissional e em 3ª pessoa.\",
    \"fit_percentage\": 75,
    \"fit_label\": \"Alta Aderência | Boa Aderência | Aderência Moderada | Baixa Aderência\",
    \"final_conclusion\": \"Frase de conclusão em 1ª pessoa, ex: 'Considero a candidata X apta para a continuidade do processo seletivo.'\"
}

REGRAS:
1. 'work_history' deve ser um array de objetos. Se nenhuma experiência for mencionada, retorne [].
2. O 'final_summary' deve ser o texto mais elaborado, com 2-3 parágrafos em texto corrido (sem listas, sem bullets).
3. Avalie a aderência à vaga e atribua um 'fit_percentage' inteiro (0-100). Faixas sugeridas: alta aderência 75-95%, boa 60-74%, moderada 40-59%, baixa 20-39%.
4. Deixe em branco/string vazia (\"\") nos campos individuais (age, children, marital_status, address, commute, education, salary_expectation) quando a informação realmente não existir na transcrição/currículo.
5. 'fit_percentage' deve ser um número inteiro, não string.
6. Em 'final_summary', 'professional_experience', 'professional_challenge', 'strengths_text' e 'development_text': NUNCA comente lacunas da entrevista. É proibido escrever frases como 'o recrutador não validou X', 'não foi constatado Y', 'não foi possível avaliar Z', 'não foi explorado', 'não foi questionado'. Esses textos descrevem o que FOI observado — nunca o que deixou de ser. Se algo não foi mencionado, simplesmente omita o tópico.
7. NÃO INVENTE NADA. Use apenas o que está na transcrição (e currículo, se fornecido). Não preencha lacunas com suposições.
8. NÃO use colchetes '[' ']' nos valores do JSON — escreva o conteúdo direto.
9. Prefira termos em português; evite anglicismos quando houver equivalente natural em português.";

        if ($complementaryPrompt) {
            $prompt .= "\n\nINSTRUÇÕES COMPLEMENTARES DO RECRUTADOR (prioridade máxima — siga estas instruções específicas ao estruturar o parecer):\n{$complementaryPrompt}";
        }

        return $prompt;
    }

    /**
     * Valida consistência entre a transcrição original e o relatório Player gerado.
     * Retorna lista de inconsistências encontradas (informações na transcrição ausentes no relatório).
     */
    public function validateReportConsistency(string $rawTranscription, array $playerReport): array
    {
        $t0 = microtime(true);
        try {
            $reportJson = json_encode($playerReport, JSON_UNESCAPED_UNICODE);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->apiUrl, [
                'model' => $this->modelValidation,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Você é um auditor de qualidade de relatórios de RH. Sua função é comparar uma transcrição original com um relatório gerado e identificar informações omitidas ou inconsistentes. Responda estritamente em JSON.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Compare a transcrição com o relatório e retorne APENAS inconsistências reais — informações presentes na transcrição mas ausentes ou erradas no relatório.\n\nTRANSCRIÇÃO ORIGINAL:\n{$rawTranscription}\n\nRELATÓRIO GERADO:\n{$reportJson}\n\nRetorne um JSON no formato:\n{\n    \"has_issues\": true|false,\n    \"issues\": [\n        {\n            \"field\": \"campo afetado, ex: work_history\",\n            \"issue\": \"descrição do problema\",\n            \"suggested_value\": \"valor correto baseado na transcrição\"\n        }\n    ]\n}\n\nSe não houver inconsistências, retorne has_issues: false e issues: []."
                    ]
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

            $this->recordUsage(OpenAIUsageLog::FEATURE_REPORT_VALIDATION, $this->modelValidation, 'chat.completions', $t0, $response);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'];
                $data = json_decode($content, true);

                if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                    return ['has_issues' => false, 'issues' => []];
                }

                return [
                    'has_issues' => $data['has_issues'] ?? false,
                    'issues' => $data['issues'] ?? [],
                ];
            }

            return ['has_issues' => false, 'issues' => []];

        } catch (\Exception $e) {
            $this->recordUsage(OpenAIUsageLog::FEATURE_REPORT_VALIDATION, $this->modelValidation, 'chat.completions', $t0, null, [], $e);
            Log::error('Validate Report Consistency Exception', ['message' => $e->getMessage()]);
            return ['has_issues' => false, 'issues' => []];
        }
    }

    /**
     * Analisa o currículo do candidato e extrai informações básicas
     */
    public function analyzeCandidateResume(string $resumeContent): ?array
    {
        $t0 = microtime(true);
        try {
            $prompt = $this->buildCandidateAnalysisPrompt($resumeContent);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->apiUrl, [
                'model' => $this->modelResumeAnalysis,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Você é um especialista em análise de currículos. Extraia informações estruturadas do currículo fornecido.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_completion_tokens' => 800,
            ]);

            $this->recordUsage(OpenAIUsageLog::FEATURE_RESUME_ANALYSIS, $this->modelResumeAnalysis, 'chat.completions', $t0, $response);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'];
                return $this->parseCandidateAnalysisResponse($content);
            }

            Log::error('OpenAI API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            $this->recordUsage(OpenAIUsageLog::FEATURE_RESUME_ANALYSIS, $this->modelResumeAnalysis, 'chat.completions', $t0, null, [], $e);
            Log::error('OpenAI Service Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    /**
     * Analisa o currículo em relação à vaga usando OpenAI
     */
    public function analyzeResumeForJob(string $resumeContent, array $jobData): ?array
    {
        $t0 = microtime(true);
        try {
            $prompt = $this->buildJobMatchAnalysisPrompt($resumeContent, $jobData);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->apiUrl, [
                'model' => $this->modelResumeAnalysis,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Você é um especialista em recrutamento e seleção. Sua função é analisar currículos e avaliar a aderência dos candidatos às vagas.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_completion_tokens' => 1500,
            ]);

            $this->recordUsage(OpenAIUsageLog::FEATURE_JOB_MATCH_ANALYSIS, $this->modelResumeAnalysis, 'chat.completions', $t0, $response, [
                'metadata' => ['job_title' => $jobData['title'] ?? null],
            ]);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'];
                return $this->parseJobMatchAnalysisResponse($content);
            }

            Log::error('OpenAI API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            $this->recordUsage(OpenAIUsageLog::FEATURE_JOB_MATCH_ANALYSIS, $this->modelResumeAnalysis, 'chat.completions', $t0, null, [
                'metadata' => ['job_title' => $jobData['title'] ?? null],
            ], $e);
            Log::error('OpenAI Service Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    /**
     * Constrói o prompt para análise do candidato (sem vaga específica)
     */
    protected function buildCandidateAnalysisPrompt(string $resumeContent): string
    {
        return "Analise o currículo abaixo e extraia informações estruturadas sobre o candidato.

CURRÍCULO:
{$resumeContent}

Por favor, forneça sua análise no seguinte formato JSON:
{
    \"city\": \"[cidade do candidato, se mencionada - apenas o nome da cidade, sem estado]\",
    \"professional_area\": \"[área profissional principal - ex: Tecnologia, Saúde, Educação, Engenharia, Vendas, Administrativo, Recursos Humanos, Financeiro, Marketing, etc]\",
    \"qualifications_summary\": \"[resumo conciso das principais qualificações, experiências e competências do candidato em 2-3 frases]\"
}

Seja objetivo e profissional. Se a cidade não for mencionada, retorne null. Para a área profissional, identifique a principal área de atuação baseado na experiência predominante.";
    }

    /**
     * Constrói o prompt para análise de aderência à vaga
     */
    protected function buildJobMatchAnalysisPrompt(string $resumeContent, array $jobData): string
    {
        return "Analise o currículo abaixo em relação à vaga descrita e forneça uma avaliação de aderência.

VAGA:
Título: {$jobData['title']}
Empresa: {$jobData['company']}
Tipo: {$jobData['type']}
Descrição: {$jobData['description']}
Responsabilidades: {$jobData['responsibilities']}
Requisitos: {$jobData['requirements']}

CURRÍCULO:
{$resumeContent}

Por favor, forneça sua análise no seguinte formato JSON:
{
    \"adherence_score\": [número de 0 a 100],
    \"strengths\": \"[lista de pontos fortes do candidato para esta vaga específica, separados por ponto e vírgula]\",
    \"attention_points\": \"[lista de pontos que requerem atenção ou lacunas identificadas, separados por ponto e vírgula]\",
    \"summary\": \"[resumo executivo da aderência em 2-3 frases]\"
}

Seja objetivo e profissional. Considere experiência, habilidades técnicas, formação acadêmica e alinhamento com os requisitos da vaga.";
    }

    /**
     * Faz parsing da resposta de análise do candidato
     */
    protected function parseCandidateAnalysisResponse(string $content): array
    {
        // Remove markdown code blocks se existirem
        $content = preg_replace('/```json\s*|\s*```/', '', $content);
        $content = trim($content);

        try {
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON Parse Error', ['content' => $content]);
                return $this->getDefaultCandidateAnalysis();
            }

            return [
                'city' => $data['city'] ?? null,
                'professional_area' => $data['professional_area'] ?? 'Não identificada',
                'qualifications_summary' => $data['qualifications_summary'] ?? 'Resumo não disponível',
                'raw_response' => $content,
            ];
        } catch (\Exception $e) {
            Log::error('Parse Candidate Analysis Exception', ['message' => $e->getMessage()]);
            return $this->getDefaultCandidateAnalysis();
        }
    }

    /**
     * Faz parsing da resposta de análise de aderência à vaga
     */
    protected function parseJobMatchAnalysisResponse(string $content): array
    {
        // Remove markdown code blocks se existirem
        $content = preg_replace('/```json\s*|\s*```/', '', $content);
        $content = trim($content);

        try {
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON Parse Error', ['content' => $content]);
                return $this->getDefaultJobMatchAnalysis();
            }

            return [
                'adherence_score' => $data['adherence_score'] ?? 50,
                'strengths' => $data['strengths'] ?? 'Análise em andamento',
                'attention_points' => $data['attention_points'] ?? 'Análise em andamento',
                'summary' => $data['summary'] ?? 'Análise em andamento',
                'raw_response' => $content,
            ];
        } catch (\Exception $e) {
            Log::error('Parse Job Match Analysis Exception', ['message' => $e->getMessage()]);
            return $this->getDefaultJobMatchAnalysis();
        }
    }

    /**
     * Retorna análise padrão do candidato em caso de erro
     */
    protected function getDefaultCandidateAnalysis(): array
    {
        return [
            'city' => null,
            'professional_area' => 'Não identificada',
            'qualifications_summary' => 'Resumo não disponível no momento',
            'raw_response' => null,
        ];
    }

    /**
     * Retorna análise padrão de aderência em caso de erro
     */
    protected function getDefaultJobMatchAnalysis(): array
    {
        return [
            'adherence_score' => 50,
            'strengths' => 'Análise não disponível no momento',
            'attention_points' => 'Análise não disponível no momento',
            'summary' => 'A análise automática não pôde ser concluída. Revisar manualmente.',
            'raw_response' => null,
        ];
    }

    /**
     * Extrai texto de arquivo PDF (com fallback OCR via OpenAI Vision)
     */
    public function extractTextFromPDF(string $filePath): ?string
    {
        try {
            // 1. Tentar extração normal com pdfparser
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();

            // 2. Se o texto extraído for suficiente, retorna
            if ($text && strlen(trim($text)) > 100) {
                Log::info('PDF extracted with pdfparser', ['chars' => strlen($text)]);
                return $this->sanitizeUtf8($text);
            }

            // 3. Fallback: usar OCR com OpenAI Vision
            Log::info('PDF text extraction insufficient, trying OCR with OpenAI Vision', [
                'file' => $filePath,
                'extracted_chars' => strlen($text ?? ''),
            ]);

            return $this->extractTextFromPDFWithOCR($filePath);

        } catch (\Exception $e) {
            Log::error('PDF Extraction Error', [
                'file' => $filePath,
                'message' => $e->getMessage(),
            ]);

            // Tentar OCR mesmo em caso de erro na extração normal
            try {
                return $this->extractTextFromPDFWithOCR($filePath);
            } catch (\Exception $ocrException) {
                Log::error('PDF OCR Fallback Error', [
                    'file' => $filePath,
                    'message' => $ocrException->getMessage(),
                ]);
                return null;
            }
        }
    }

    /**
     * Extrai texto de PDF usando OCR via OpenAI Vision API (Imagick → base64 → gpt-4o-mini)
     */
    protected function extractTextFromPDFWithOCR(string $filePath): ?string
    {
        try {
            if (!extension_loaded('imagick')) {
                Log::warning('Imagick extension not available for PDF OCR');
                return null;
            }

            $extractedText = [];
            $imagick = new \Imagick();
            $imagick->setResolution(200, 200);

            // Lê até 10 páginas do PDF
            $imagick->readImage($filePath . '[0-9]');

            $pageCount = $imagick->getNumberImages();
            Log::info('Processing PDF with OCR', ['pages' => $pageCount]);

            foreach ($imagick as $pageNum => $page) {
                $page->setImageFormat('png');
                $page->setImageCompressionQuality(85);

                $imageData = base64_encode($page->getImageBlob());
                $pageText = $this->extractTextFromImageWithVision($imageData, $pageNum + 1);

                if ($pageText) {
                    $extractedText[] = $pageText;
                }

                // Limita a 5 páginas para evitar timeout/custo
                if ($pageNum >= 4) {
                    Log::info('OCR limited to first 5 pages');
                    break;
                }
            }

            $imagick->clear();
            $imagick->destroy();

            $fullText = implode("\n\n--- Página ---\n\n", $extractedText);

            Log::info('PDF OCR completed', ['total_chars' => strlen($fullText)]);

            return $fullText ? $this->sanitizeUtf8($fullText) : null;

        } catch (\Exception $e) {
            Log::error('PDF OCR Error', [
                'file' => $filePath,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Extrai texto de uma página de imagem usando OpenAI Vision API
     */
    protected function extractTextFromImageWithVision(string $base64Image, int $pageNumber = 1): ?string
    {
        $t0 = microtime(true);
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($this->apiUrl, [
                'model' => $this->modelOcr,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Você é um assistente especializado em extrair texto de imagens de currículos e documentos. Extraia TODO o texto visível na imagem, mantendo a estrutura e formatação original o máximo possível.',
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => "Extraia todo o texto desta página {$pageNumber} de um currículo/documento. Retorne apenas o texto extraído, sem comentários adicionais.",
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url'    => "data:image/png;base64,{$base64Image}",
                                    'detail' => 'high',
                                ],
                            ],
                        ],
                    ],
                ],
                'max_completion_tokens' => 4000,
            ]);

            $this->recordUsage(OpenAIUsageLog::FEATURE_PDF_OCR, $this->modelOcr, 'chat.completions', $t0, $response, [
                'metadata' => ['page' => $pageNumber],
            ]);

            if ($response->successful()) {
                return $response->json()['choices'][0]['message']['content'] ?? null;
            }

            Log::error('OpenAI Vision API Error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return null;

        } catch (\Exception $e) {
            $this->recordUsage(OpenAIUsageLog::FEATURE_PDF_OCR, $this->modelOcr, 'chat.completions', $t0, null, [
                'metadata' => ['page' => $pageNumber],
            ], $e);
            Log::error('OpenAI Vision Exception', [
                'page'    => $pageNumber,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Extrai texto de arquivo DOCX
     */
    public function extractTextFromDOCX(string $filePath): ?string
    {
        try {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) === true) {
                $content = $zip->getFromName('word/document.xml');
                $zip->close();

                if ($content) {
                    $content = str_replace('</w:p>', "\n", $content);
                    $content = strip_tags($content);
                    return $this->sanitizeUtf8(trim($content));
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('DOCX Extraction Error', [
                'file' => $filePath,
                'message' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Extrai texto de arquivo baseado na extensão
     */
    public function extractTextFromFile(string $filePath): ?string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'pdf':
                return $this->extractTextFromPDF($filePath);
            case 'docx':
                return $this->extractTextFromDOCX($filePath);
            case 'txt':
                return file_get_contents($filePath);
            default:
                Log::warning('Unsupported file type', ['extension' => $extension]);
                return null;
        }
    }

    /**
     * Analisa o perfil DISC e gera insights com IA
     */
    public function analyzeDiscProfile(array $profileData): ?array
    {
        $t0 = microtime(true);
        try {
            $prompt = $this->buildDiscAnalysisPrompt($profileData);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($this->apiUrl, [
                'model' => $this->modelDisc,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Você é um especialista em análise comportamental e teste DISC. Forneça análises profundas e insights valiosos sobre perfis DISC para recrutamento e desenvolvimento profissional. Responda estritamente em formato JSON.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_completion_tokens' => 4000,
                'response_format' => ['type' => 'json_object'],
            ]);

            $this->recordUsage(OpenAIUsageLog::FEATURE_DISC_ANALYSIS, $this->modelDisc, 'chat.completions', $t0, $response, [
                'metadata' => ['primary_profile' => $profileData['primary_profile'] ?? null],
            ]);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'];
                return $this->parseDiscAnalysisResponse($content);
            }

            Log::error('OpenAI API Error (DISC Analysis)', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            $this->recordUsage(OpenAIUsageLog::FEATURE_DISC_ANALYSIS, $this->modelDisc, 'chat.completions', $t0, null, [], $e);
            Log::error('OpenAI DISC Analysis Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    /**
     * Constrói o prompt para análise do perfil DISC
     */
    protected function buildDiscAnalysisPrompt(array $profileData): string
    {
        $scores = $profileData['scores'];
        $primary = $profileData['primary_profile'];
        $secondary = $profileData['secondary_profile'] ?? 'Nenhum';
        $percentages = $profileData['percentages'];

        $profileDescriptions = [
            'D' => 'Dominância - Focado em resultados, direto, competitivo, gosta de desafios',
            'I' => 'Influência - Comunicativo, entusiasmado, otimista, persuasivo',
            'S' => 'Estabilidade - Paciente, confiável, colaborativo, prefere ambiente estável',
            'C' => 'Conformidade - Analítico, detalhista, preciso, sistemático',
        ];

        return "Analise o seguinte perfil DISC de um candidato e forneça insights detalhados para recrutamento e desenvolvimento profissional.

PERFIL DISC:
- Perfil Primário: {$primary} ({$profileDescriptions[$primary]})
- Perfil Secundário: {$secondary}" . ($secondary !== 'Nenhum' ? " ({$profileDescriptions[$secondary]})" : '') . "

PONTUAÇÕES:
- Dominância (D): {$scores['D']} pontos ({$percentages['D']}%)
- Influência (I): {$scores['I']} pontos ({$percentages['I']}%)
- Estabilidade (S): {$scores['S']} pontos ({$percentages['S']}%)
- Conformidade (C): {$scores['C']} pontos ({$percentages['C']}%)

Por favor, forneça uma análise completa no seguinte formato JSON:
{
    \"analysis\": \"Análise detalhada do perfil comportamental, explicando como as características se manifestam no ambiente de trabalho - 3-4 parágrafos\",
    \"strengths\": \"Lista de 5-7 pontos fortes principais deste perfil, separados por ponto e vírgula\",
    \"development_areas\": \"Lista de 3-5 áreas que podem ser desenvolvidas ou desafios potenciais, separados por ponto e vírgula\",
    \"ideal_roles\": \"Lista de 5-8 tipos de funções ou cargos ideais para este perfil, separados por ponto e vírgula\",
    \"work_style\": \"Descrição do estilo de trabalho preferido, ambiente ideal, como se comunica, como toma decisões, como trabalha em equipe - 2-3 parágrafos\"
}

REGRAS IMPORTANTES:
1. FORMATAÇÃO: NÃO use colchetes [ ou ] no conteúdo dos campos da resposta. As descrições acima são instruções, não devem ser copiadas literalmente nem encerradas com colchetes.
2. IDIOMA: Escreva em português brasileiro natural. Evite anglicismos quando houver equivalente claro em português — prefira 'desenvolvimento de negócios' em vez de 'business development', 'gerente de contas' em vez de 'key account manager', 'partes interessadas' em vez de 'stakeholders', 'vendas consultivas' em vez de 'consultative sales', 'crescimento' em vez de 'growth'. Termos técnicos consagrados sem tradução natural podem ser mantidos.
3. CONTEÚDO: Seja específico, profissional e forneça insights práticos aplicáveis ao contexto de recrutamento e seleção brasileiro.";
    }

    /**
     * Faz parsing da resposta de análise DISC
     */
    protected function parseDiscAnalysisResponse(string $content): array
    {
        // Remove markdown code blocks se existirem
        $content = preg_replace('/```json\s*|\s*```/', '', $content);
        $content = trim($content);

        try {
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                Log::error('JSON Parse Error (DISC)', [
                    'error' => json_last_error_msg(),
                    'content_preview' => substr($content, 0, 200),
                ]);
                return $this->getDefaultDiscAnalysis();
            }

            // Verifica se tem pelo menos o campo analysis
            if (!isset($data['analysis'])) {
                Log::warning('DISC Analysis sem campo obrigatório', [
                    'keys' => array_keys($data),
                ]);
            }

            return [
                'analysis' => $this->stripBracketWrappers($data['analysis'] ?? 'Análise não disponível'),
                'strengths' => $this->stripBracketWrappers($data['strengths'] ?? 'Pontos fortes não identificados'),
                'development_areas' => $this->stripBracketWrappers($data['development_areas'] ?? 'Áreas de desenvolvimento não identificadas'),
                'ideal_roles' => $this->stripBracketWrappers($data['ideal_roles'] ?? 'Funções ideais não identificadas'),
                'work_style' => $this->stripBracketWrappers($data['work_style'] ?? 'Estilo de trabalho não identificado'),
                'raw_response' => $content,
            ];
        } catch (\Exception $e) {
            Log::error('Parse DISC Analysis Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->getDefaultDiscAnalysis();
        }
    }

    /**
     * Retorna análise DISC padrão em caso de erro
     */
    protected function getDefaultDiscAnalysis(): array
    {
        return [
            'analysis' => 'A análise detalhada do perfil não está disponível no momento.',
            'strengths' => 'Análise em andamento',
            'development_areas' => 'Análise em andamento',
            'ideal_roles' => 'Análise em andamento',
            'work_style' => 'Análise em andamento',
            'raw_response' => null,
        ];
    }

    /**
     * Analisa o perfil de Culture Fit e gera insights com IA
     */
    public function analyzeCultureFitProfile(array $profileData): ?array
    {
        $t0 = microtime(true);
        try {
            $prompt = $this->buildCultureFitAnalysisPrompt($profileData);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($this->apiUrl, [
                'model' => $this->modelDisc,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Você é um especialista em cultura organizacional e adequação cultural. Forneça análises profundas sobre o perfil cultural do candidato e como ele se encaixa em diferentes ambientes organizacionais. Responda estritamente em formato JSON.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_completion_tokens' => 4000,
                'response_format' => ['type' => 'json_object'],
            ]);

            $this->recordUsage(OpenAIUsageLog::FEATURE_CULTURE_FIT_ANALYSIS, $this->modelDisc, 'chat.completions', $t0, $response);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'];
                return $this->parseCultureFitAnalysisResponse($content);
            }

            Log::error('OpenAI API Error (Culture Fit Analysis)', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            $this->recordUsage(OpenAIUsageLog::FEATURE_CULTURE_FIT_ANALYSIS, $this->modelDisc, 'chat.completions', $t0, null, [], $e);
            Log::error('OpenAI Culture Fit Analysis Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return null;
        }
    }

    /**
     * Constrói o prompt para análise do perfil Culture Fit
     */
    protected function buildCultureFitAnalysisPrompt(array $profileData): string
    {
        $scores = $profileData['scores'];
        $dominant = $profileData['dominant_dimensions'];
        $percentages = $profileData['percentages'];

        $dimensionDescriptions = [
            'autonomy' => 'Autonomia - Preferência por independência, autogestão e tomada de decisão própria',
            'innovation' => 'Inovação - Valorização de criatividade, mudança, experimentação e novas ideias',
            'hierarchy' => 'Hierarquia - Preferência por estrutura clara, processos definidos e autoridade estabelecida',
            'teamwork' => 'Trabalho em Equipe - Valorização de colaboração, consenso e trabalho coletivo',
            'results' => 'Foco em Resultados - Orientação para metas, performance e conquista de objetivos',
            'flexibility' => 'Flexibilidade - Adaptabilidade a mudanças, versatilidade e abertura ao novo',
        ];

        return "Analise o seguinte perfil de adequação cultural (Culture Fit) de um candidato e forneça insights detalhados sobre que tipo de ambiente organizacional seria ideal.

PERFIL CULTURE FIT:
- Dimensões Dominantes: " . implode(' e ', array_map(function($dim) use ($dimensionDescriptions) {
            return ucfirst($dim) . " ({$dimensionDescriptions[$dim]})";
        }, $dominant)) . "

PONTUAÇÕES POR DIMENSÃO:
- Autonomia: {$scores['autonomy']} pontos ({$percentages['autonomy']}%)
- Inovação: {$scores['innovation']} pontos ({$percentages['innovation']}%)
- Hierarquia/Estrutura: {$scores['hierarchy']} pontos ({$percentages['hierarchy']}%)
- Trabalho em Equipe: {$scores['teamwork']} pontos ({$percentages['teamwork']}%)
- Foco em Resultados: {$scores['results']} pontos ({$percentages['results']}%)
- Flexibilidade: {$scores['flexibility']} pontos ({$percentages['flexibility']}%)

Por favor, forneça uma análise completa no seguinte formato JSON:
{
    \"analysis\": \"Análise detalhada do perfil cultural, explicando as preferências e valores do candidato no ambiente de trabalho - 3-4 parágrafos\",
    \"cultural_profile\": \"Descrição do perfil cultural dominante do candidato em 2-3 frases, ex: 'Perfil Inovador-Autônomo' ou 'Perfil Estruturado-Colaborativo'\",
    \"strengths\": \"Lista de 5-7 pontos fortes culturais deste perfil, separados por ponto e vírgula\",
    \"challenges\": \"Lista de 3-5 desafios de adaptação ou ambientes que podem ser difíceis para este perfil, separados por ponto e vírgula\",
    \"ideal_environments\": \"Lista de 5-8 características de ambientes organizacionais ideais, separados por ponto e vírgula\",
    \"recommendations\": \"Recomendações práticas para o recrutador sobre como avaliar a adequação cultural deste candidato e em que tipos de vagas ele se encaixaria melhor - 2-3 parágrafos\"
}

REGRAS IMPORTANTES:
1. FORMATAÇÃO: NÃO use colchetes [ ou ] no conteúdo dos campos da resposta. As descrições acima são instruções, não devem ser copiadas literalmente nem encerradas com colchetes.
2. IDIOMA: Escreva em português brasileiro natural. Evite anglicismos quando houver equivalente claro em português — prefira 'adequação cultural' em vez de 'culture fit' ou 'fit cultural', 'empresas iniciantes' ou 'empresas em estágio inicial' em vez de 'startups' (quando couber), 'trabalho remoto' em vez de 'remote work', 'desempenho' em vez de 'performance'. Termos técnicos consagrados sem tradução natural podem ser mantidos.
3. CONTEÚDO: Seja específico, profissional e forneça insights práticos para decisões de recrutamento baseadas em adequação cultural no contexto brasileiro.";
    }

    /**
     * Faz parsing da resposta de análise Culture Fit
     */
    protected function parseCultureFitAnalysisResponse(string $content): array
    {
        // Remove markdown code blocks se existirem
        $content = preg_replace('/```json\s*|\s*```/', '', $content);
        $content = trim($content);

        try {
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                Log::error('JSON Parse Error (Culture Fit)', [
                    'error' => json_last_error_msg(),
                    'content_preview' => substr($content, 0, 200),
                ]);
                return $this->getDefaultCultureFitAnalysis();
            }

            // Verifica se tem pelo menos o campo analysis
            if (!isset($data['analysis'])) {
                Log::warning('Culture Fit Analysis sem campo obrigatório', [
                    'keys' => array_keys($data),
                ]);
            }

            return [
                'analysis' => $this->stripBracketWrappers($data['analysis'] ?? 'Análise não disponível'),
                'cultural_profile' => $this->stripBracketWrappers($data['cultural_profile'] ?? 'Perfil cultural não identificado'),
                'strengths' => $this->stripBracketWrappers($data['strengths'] ?? 'Pontos fortes não identificados'),
                'challenges' => $this->stripBracketWrappers($data['challenges'] ?? 'Desafios não identificados'),
                'ideal_environments' => $this->stripBracketWrappers($data['ideal_environments'] ?? 'Ambientes ideais não identificados'),
                'recommendations' => $this->stripBracketWrappers($data['recommendations'] ?? 'Recomendações não disponíveis'),
                'raw_response' => $content,
            ];
        } catch (\Exception $e) {
            Log::error('Parse Culture Fit Analysis Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->getDefaultCultureFitAnalysis();
        }
    }

    /**
     * Retorna análise Culture Fit padrão em caso de erro
     */
    protected function getDefaultCultureFitAnalysis(): array
    {
        return [
            'analysis' => 'A análise detalhada do perfil cultural não está disponível no momento.',
            'cultural_profile' => 'Análise em andamento',
            'strengths' => 'Análise em andamento',
            'challenges' => 'Análise em andamento',
            'ideal_environments' => 'Análise em andamento',
            'recommendations' => 'Análise em andamento',
            'raw_response' => null,
        ];
    }
    /**
     * Extrai informações de contato (nome, email, telefone) do currículo
     */
    /**
     * Remove colchetes residuais de borda nos textos retornados pela IA.
     * O modelo às vezes preserva os colchetes do template (ex: "[texto...]")
     * em campos de saída — esta função tira só os de borda, sem mexer em
     * colchetes legítimos no meio do texto.
     */
    private function stripBracketWrappers($value)
    {
        if (!is_string($value)) {
            return $value;
        }
        $trimmed = trim($value);
        if ($trimmed === '') {
            return $value;
        }
        if ($trimmed[0] === '[') {
            $trimmed = ltrim(substr($trimmed, 1));
        }
        if ($trimmed !== '' && substr($trimmed, -1) === ']') {
            $trimmed = rtrim(substr($trimmed, 0, -1));
        }
        return $trimmed;
    }

    /**
     * Remove bytes inválidos de UTF-8 para evitar json_encode errors
     */
    private function sanitizeUtf8(string $text): string
    {
        // iconv com //IGNORE descarta qualquer byte que não seja UTF-8 válido
        $clean = iconv('UTF-8', 'UTF-8//IGNORE', $text);
        // Se iconv não estiver disponível ou retornar false, usa mb_convert_encoding como fallback
        if ($clean === false) {
            $clean = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }
        return $clean ?? $text;
    }

    public function extractContactInfo(string $resumeContent, array $categories = []): ?array
    {
        $t0 = microtime(true);
        try {
            $resumeContent = $this->sanitizeUtf8($resumeContent);
            $prompt = $this->buildContactInfoExtractionPrompt($resumeContent, $categories);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($this->apiUrl, [
                'model' => $this->modelContactExtract,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Você é um assistente especializado em extração de dados de currículos. Extraia nome, email, telefone e identifique a categoria profissional.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.1,
                'max_completion_tokens' => 300,
                'response_format' => ['type' => 'json_object'],
            ]);

            $this->recordUsage(OpenAIUsageLog::FEATURE_CONTACT_EXTRACTION, $this->modelContactExtract, 'chat.completions', $t0, $response);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'];
                return $this->parseContactInfoResponse($content);
            }

            Log::error('OpenAI API Error (Contact Extraction)', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            $this->recordUsage(OpenAIUsageLog::FEATURE_CONTACT_EXTRACTION, $this->modelContactExtract, 'chat.completions', $t0, null, [], $e);
            Log::error('OpenAI Contact Extraction Exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Gera narrativa de análise para um mapeamento comportamental genérico.
     * Retorna texto corrido (não JSON). Nunca lança exceção — retorna null em caso de falha.
     */
    public function generateAssessmentNarrative(array $data): ?string
    {
        $t0 = microtime(true);
        try {
            $testName    = $data['test_name']       ?? 'Mapeamento Comportamental';
            $respondent  = $data['respondent_name'] ?? 'o respondente';
            $overall     = number_format($data['overall_score'] ?? 0, 1);
            $quality     = $data['quality_index']   ?? 100;
            $dimensions  = $data['dimension_scores'] ?? [];

            // Monta lista de dimensões ordenadas do maior para o menor score
            uasort($dimensions, fn($a, $b) => $b['score'] <=> $a['score']);
            $dimLines = [];
            foreach ($dimensions as $dim) {
                $dimLines[] = "- {$dim['name']}: {$dim['score']} pts ({$dim['classification']})";
            }
            $dimText = implode("\n", $dimLines);

            $qualityNote = $quality < 70
                ? "\nATENÇÃO: O índice de qualidade do preenchimento foi baixo ({$quality}/100), o que pode comprometer a precisão da análise."
                : '';

            $prompt = "Você é um consultor sênior de Desenvolvimento Humano e Organizacional da consultoria Sara Linhar DHO & R&S.

Elabore a análise narrativa do mapeamento comportamental descrito abaixo.
A análise é destinada ao recrutador ou gestor responsável — nunca ao candidato.
Use linguagem profissional, objetiva e acessível.

ESTRUTURA OBRIGATÓRIA — escreva exatamente 4 parágrafos separados por uma linha em branco, nesta ordem:

PARÁGRAFO 1 — PERFIL GERAL
Apresente o score geral e o perfil percebido do respondente. Situe o resultado no contexto do instrumento aplicado e descreva o estilo comportamental predominante.

PARÁGRAFO 2 — PONTOS DE FORÇA
Aprofunde as 2 dimensões com MAIOR score. Descreva as tendências comportamentais observadas e como elas se traduzem em contribuições práticas no ambiente de trabalho.

PARÁGRAFO 3 — OPORTUNIDADES DE DESENVOLVIMENTO
Aborde as 2 dimensões com MENOR score como pontos de atenção e crescimento. Use tom construtivo — não use linguagem de déficit ou falha.

PARÁGRAFO 4 — RECOMENDAÇÃO PRÁTICA
Encerre com orientação concreta de como usar esses dados no processo seletivo ou na gestão e desenvolvimento da pessoa.

REGRAS OBRIGATÓRIAS:
- Não use as palavras: diagnóstico, apto, inapto, personalidade clínica, laudo psicológico, transtorno.
- Use sempre: evidência comportamental, mapeamento, perfil percebido, tendência, contexto observado.
- Não inclua títulos, marcadores, JSON nem o disclaimer legal — apenas os 4 parágrafos em texto corrido.
- Cada parágrafo deve ter entre 3 e 5 frases.

DADOS DO MAPEAMENTO:
Instrumento: {$testName}
Respondente: {$respondent}
Score geral: {$overall}/100
{$qualityNote}

DIMENSÕES (maior → menor score):
{$dimText}";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(60)->post($this->apiUrl, [
                'model'    => $this->modelDisc,
                'messages' => [
                    [
                        'role'    => 'system',
                        'content' => 'Você é um especialista em mapeamento comportamental da consultoria Sara Linhar DHO & R&S. Redija análises claras, éticas e úteis para profissionais de RH.',
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt,
                    ],
                ],
                'max_completion_tokens' => 800,
            ]);

            $this->recordUsage(
                OpenAIUsageLog::FEATURE_ASSESSMENT_ANALYSIS,
                $this->modelDisc,
                'chat.completions',
                $t0,
                $response,
                ['metadata' => ['test_name' => $testName]]
            );

            if ($response->successful()) {
                return trim($response->json()['choices'][0]['message']['content'] ?? '');
            }

            Log::error('OpenAI Assessment Narrative Error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            $this->recordUsage(
                OpenAIUsageLog::FEATURE_ASSESSMENT_ANALYSIS,
                $this->modelDisc,
                'chat.completions',
                $t0,
                null,
                [],
                $e
            );
            Log::error('OpenAI Assessment Narrative Exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Registra um uso da API OpenAI no banco (best-effort, nunca lança).
     */
    protected function recordUsage(
        string $feature,
        string $model,
        string $endpoint,
        float $startedAt,
        ?Response $response,
        array $context = [],
        ?\Throwable $exception = null
    ): void {
        try {
            $data = [
                'feature' => $feature,
                'model' => $model,
                'endpoint' => $endpoint,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'status' => OpenAIUsageLog::STATUS_ERROR,
                'subject_type' => $context['subject_type'] ?? null,
                'subject_id' => $context['subject_id'] ?? null,
                'metadata' => $context['metadata'] ?? null,
            ];

            if ($exception !== null) {
                $data['error_message'] = mb_substr($exception->getMessage(), 0, 5000);
            } elseif ($response !== null) {
                $data['http_status'] = $response->status();

                if ($response->successful()) {
                    $body = $response->json() ?? [];
                    $usage = $body['usage'] ?? [];

                    $data['status'] = OpenAIUsageLog::STATUS_SUCCESS;
                    $data['input_tokens']  = $usage['prompt_tokens']     ?? $usage['input_tokens']  ?? null;
                    $data['output_tokens'] = $usage['completion_tokens'] ?? $usage['output_tokens'] ?? null;
                    $data['total_tokens']  = $usage['total_tokens'] ?? null;

                    $data['cached_input_tokens'] = $usage['prompt_tokens_details']['cached_tokens']
                        ?? $usage['input_tokens_details']['cached_tokens']
                        ?? null;

                    $data['reasoning_tokens'] = $usage['completion_tokens_details']['reasoning_tokens']
                        ?? $usage['output_tokens_details']['reasoning_tokens']
                        ?? null;

                    $data['response_id'] = $body['id'] ?? null;
                } else {
                    $data['error_message'] = mb_substr((string) $response->body(), 0, 5000);
                }
            }

            OpenAIUsageLog::record($data);
        } catch (\Throwable $e) {
            Log::warning('OpenAIService recordUsage failed', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Constrói o prompt para extração de contato
     */
    protected function buildContactInfoExtractionPrompt(string $resumeContent, array $categories = []): string
    {
        $categoriesText = "";
        if (!empty($categories)) {
            $categoriesText = "CATEGORIAS DISPONÍVEIS:\n";
            foreach ($categories as $cat) {
                $categoriesText .= "- ID {$cat['id']}: {$cat['name']}\n";
            }
            $categoriesText .= "\nEscolha o ID da categoria que melhor se adapta ao currículo. Se nenhuma se encaixar perfeitamente, escolha a mais próxima ou retorne null se for impossível classificar.\n";
        }

        return "Extraia o Nome Completo, Email, Telefone e a Categoria Profissional do currículo abaixo.
        
CURRÍCULO:
{$resumeContent}

{$categoriesText}

Retorne APENAS um JSON no seguinte formato:
{
    \"name\": \"[Nome Completo]\",
    \"email\": \"[Email]\",
    \"phone\": \"[Telefone com DDD]\",
    \"professional_area\": \"[Área Profissional - ex: Vendas, TI, Administrativo, Saúde, etc]\",
    \"category_id\": [ID da categoria escolhida ou null]
}

Se não encontrar algum dado, retorne null ou string vazia para esse campo. Tente formatar o telefone para apenas números ou padrão (XX) XXXXX-XXXX. Se houver múltiplos telefones, pegue o celular/principal. Para a área profissional, infira com base na experiência e objetivo.";
    }

    /**
     * Faz parsing da resposta de extração de contato
     */
    protected function parseContactInfoResponse(string $content): array
    {
        $content = preg_replace('/```json\s*|\s*```/', '', $content);
        $content = trim($content);

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            Log::warning('OpenAI Contact Extraction: falha no parse do JSON', [
                'json_error'      => json_last_error_msg(),
                'content_preview' => mb_substr($content, 0, 300),
            ]);
            return [
                'name' => '',
                'email' => '',
                'phone' => '',
                'professional_area' => '',
                'category_id' => null,
            ];
        }

        return [
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'professional_area' => $data['professional_area'] ?? '',
            'category_id' => $data['category_id'] ?? null,
        ];
    }

    /**
     * Processa uma iteração do agente conversacional de pareceres
     */
    public function processConversationalAgent(string $reportType, array $currentData, array $chatHistory, string $newInputText): array
    {
        $t0 = microtime(true);
        try {
            $schemaPrompt = $reportType === 'player' 
                ? $this->buildPlayerConversationalSchema() 
                : $this->buildSaraConversationalSchema();

            $systemPrompt = "Você é o assistente virtual especializado em DHO (Desenvolvimento Humano e Organizacional) da consultoria " . ($reportType === 'player' ? "Player" : "Sara Linhar") . ".
Sua tarefa é conversar de forma extremamente amigável, acolhedora e empática com o recrutador para coletar informações e preencher um parecer de entrevista profissional.

Você receberá:
1. O rascunho em tempo real dos dados estruturados do parecer.
2. O histórico das últimas mensagens trocadas na conversa.
3. A nova mensagem enviada pelo recrutador (que pode ser um texto digitado ou a transcrição de um áudio).

Esquema de Dados Alvo para o parecer (updated_data deve ter esta exata estrutura):
{$schemaPrompt}

Suas diretrizes críticas:
1. **Extração de Informações**: Analise com atenção a nova mensagem do recrutador. Identifique qualquer dado novo relevante para o parecer (como dados pessoais, histórico de trabalho, pontos fortes, postura, pretensão salarial, etc.) e atualize/mescle no respectivo campo do JSON `updated_data`.
2. **Preservação de Dados**: Mantenha e respeite integralmente os dados já existentes no rascunho. Só os altere ou atualize se o recrutador explicitamente corrigir ou complementar alguma informação já existente.
3. **Conversação Gentil e Humana**: No campo `agent_reply`, formule uma resposta curta, muito amigável e profissional.
   - Demonstre que compreendeu as informações trazidas (ex: 'Que bacana!', 'Perfeito, adicionei a experiência dele...', 'Excelente postura comportamental').
   - Identifique dados interessantes para enriquecer o parecer que ainda não foram preenchidos (ex: pretensão salarial, motivo de saída de empregos passados, deslocamento, composição familiar) e sugira gentilmente um ou dois deles em formato de pergunta.
   - **NÃO force o recrutador**. Deixe claro que nenhum campo é obrigatório e ele pode clicar em 'Finalizar' a qualquer momento para gerar o PDF final com o que já foi coletado.
4. **Prontidão de Finalização (`can_finalize`)**: Se o recrutador indicar explicitamente que deseja encerrar a conversa (ex: 'pode fechar', 'gerar parecer', 'finalizar', 'isso é tudo', 'não tenho mais dados') ou se os dados principais já estiverem completos, defina `can_finalize` como true. Caso contrário, defina false.

Você deve responder estritamente com um objeto JSON válido contendo exatamente três chaves no nível raiz:
{
    \"updated_data\": { ... }, // O JSON completo do parecer contendo os dados atualizados com o mesmo schema do rascunho original
    \"agent_reply\": \"Sua resposta textual e empática para o recrutador\",
    \"can_finalize\": false ou true // true se o recrutador pediu para finalizar ou está tudo completo
}

NÃO use blocos de código markdown ou explicações externas no início ou fim. Responda estritamente com o JSON válido.";

            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
            ];

            // Adiciona histórico de chat
            foreach ($chatHistory as $msg) {
                $messages[] = [
                    'role' => $msg['sender'] === 'user' ? 'user' : 'assistant',
                    'content' => $msg['content']
                ];
            }

            // Adiciona última interação com dados atuais
            $messages[] = [
                'role' => 'user',
                'content' => "DADOS ESTRUTURADOS ATUAIS (RASCUNHO JSON):\n" . json_encode($currentData, JSON_UNESCAPED_UNICODE) . "\n\nNOVA MENSAGEM DO RECRUTADOR:\n{$newInputText}"
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(45)->retry(3, 1000)->post($this->apiUrl, [
                'model' => $this->modelReport,
                'messages' => $messages,
                'response_format' => ['type' => 'json_object'],
            ]);

            $feature = $reportType === 'player' ? OpenAIUsageLog::FEATURE_PLAYER_REPORT : OpenAIUsageLog::FEATURE_CANDIDATE_REPORT;
            $this->recordUsage($feature, $this->modelReport, 'chat.completions', $t0, $response);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'];
                $content = preg_replace('/```json\s*|\s*```/', '', $content);
                $content = trim($content);

                $data = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                    return [
                        'updated_data' => $data['updated_data'] ?? $currentData,
                        'agent_reply' => $data['agent_reply'] ?? 'Desculpe, tive um probleminha para formular a resposta, mas salvei suas notas!',
                        'can_finalize' => (bool) ($data['can_finalize'] ?? false),
                    ];
                }
            }

            Log::error('OpenAI Conversational Agent Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'updated_data' => $currentData,
                'agent_reply' => 'Desculpe, tive uma oscilação na conexão com a IA, mas continuo aqui. O que mais gostaria de acrescentar ao parecer?',
                'can_finalize' => false
            ];

        } catch (\Exception $e) {
            $feature = $reportType === 'player' ? OpenAIUsageLog::FEATURE_PLAYER_REPORT : OpenAIUsageLog::FEATURE_CANDIDATE_REPORT;
            $this->recordUsage($feature, $this->modelReport, 'chat.completions', $t0, null, [], $e);
            Log::error('OpenAI Conversational Agent Exception', ['message' => $e->getMessage()]);

            return [
                'updated_data' => $currentData,
                'agent_reply' => 'Desculpe, tive uma instabilidade momentânea. Pode repetir o que disse?',
                'can_finalize' => false
            ];
        }
    }

    protected function buildSaraConversationalSchema(): string
    {
        return "Estrutura JSON Sara Linhar:\n" . json_encode($this->getDefaultReportStructure(), JSON_UNESCAPED_UNICODE);
    }

    protected function buildPlayerConversationalSchema(): string
    {
        return "Estrutura JSON Player:\n" . json_encode($this->getDefaultPlayerStructure(), JSON_UNESCAPED_UNICODE);
    }

    /**
     * Avalia a resposta de uma pergunta de entrevista usando a OpenAI.
     */
    public function evaluateInterviewAnswer(string $question, string $answer): ?array
    {
        $t0 = microtime(true);
        try {
            $systemPrompt = "Você é um especialista em recrutamento, seleção e treinamento de profissionais. " .
                "Sua tarefa é avaliar a resposta de um candidato a uma determinada pergunta de entrevista.\n" .
                "Você deve retornar a análise em formato JSON estrito, seguindo exatamente esta estrutura:\n" .
                "{\n" .
                "  \"score\": 85, // Uma pontuação inteira de 0 a 100\n" .
                "  \"criteria\": [\n" .
                "    { \"label\": \"Clareza\", \"score\": 90 },\n" .
                "    { \"label\": \"Alinhamento Técnico\", \"score\": 80 },\n" .
                "    { \"label\": \"Estruturação (STAR)\", \"score\": 85 }\n" .
                "  ],\n" .
                "  \"positives\": [\n" .
                "    \"Demonstrou boa capacidade analítica.\",\n" .
                "    \"Mencionou resultados quantitativos claros.\"\n" .
                "  ],\n" .
                "  \"improvements\": [\n" .
                "    \"Poderia ter detalhado melhor as ferramentas utilizadas.\",\n" .
                "    \"Tente estruturar a resposta no formato Situação-Tarefa-Ação-Resultado (STAR).\"\n" .
                "  ],\n" .
                "  \"improvedAnswer\": \"Aqui está uma versão reescrita e otimizada da resposta do candidato, servindo como modelo ideal.\"\n" .
                "}\n" .
                "Responda estritamente com o objeto JSON. Não adicione qualquer markdown, bloco de código, ou texto adicional além do JSON.";

            $userPrompt = "Pergunta da Entrevista:\n{$question}\n\nResposta do Candidato:\n{$answer}";

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(90)->post($this->apiUrl, [
                'model' => $this->modelValidation,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt
                    ],
                    [
                        'role' => 'user',
                        'content' => $userPrompt
                    ]
                ],
                'max_completion_tokens' => 2000,
                'response_format' => ['type' => 'json_object'],
            ]);

            $this->recordUsage(OpenAIUsageLog::FEATURE_INTERVIEW_EVALUATION, $this->modelValidation, 'chat.completions', $t0, $response);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'];
                return json_decode($content, true);
            }

            Log::error('Erro ao avaliar resposta na OpenAI', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;

        } catch (\Exception $e) {
            $this->recordUsage(OpenAIUsageLog::FEATURE_INTERVIEW_EVALUATION, $this->modelValidation, 'chat.completions', $t0, null, [], $e);
            Log::error('Exception ao avaliar resposta na OpenAI: ' . $e->getMessage());
            return null;
        }
    }
}

