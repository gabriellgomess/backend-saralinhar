<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->apiUrl = 'https://api.openai.com/v1/chat/completions';
    }

    /**
     * Transcreve áudio usando Whisper
     */
    public function transcribeAudio(string $filePath): string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->timeout(300)->attach(
                'file',
                file_get_contents($filePath),
                basename($filePath)
            )->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => 'whisper-1',
                'language' => 'pt',
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
            Log::error('OpenAI Whisper Exception', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Estrutura o parecer do candidato a partir do texto
     */
    public function structureCandidateReport(string $text, ?string $resumeText = null): array
    {
        try {
            $prompt = $this->buildReportStructurePrompt($text, $resumeText);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($this->apiUrl, [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Você é um especialista em recrutamento e seleção. Sua tarefa é estruturar anotações de entrevista em um parecer profissional formal.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.5,
                'response_format' => ['type' => 'json_object'],
            ]);

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
            Log::error('OpenAI Report Structure Exception', ['message' => $e->getMessage()]);
            return $this->getDefaultReportStructure();
        }
    }

    protected function buildReportStructurePrompt(string $text, ?string $resumeText = null): string
    {
        $prompt = "O texto a seguir é a transcrição de um áudio gravado pelo RECRUTADOR, relatando como foi a entrevista e suas impressões sobre o candidato. Sua tarefa é estruturar esse relato em um parecer profissional formal.";
        
        if ($resumeText) {
            $prompt .= " Além disso, utilize as informações do Currículo do candidato (fornecido abaixo) para complementar dados técnicos e históricos. Se houver divergência ou informações mais recentes no relato do recrutador (áudio), priorize o relato do recrutador.";
        }

        $prompt .= "\n\nRELATO DO RECRUTADOR (TRANSCRIÇÃO):\n{$text}\n";

        if ($resumeText) {
            $prompt .= "\nCURRÍCULO DO CANDIDATO:\n{$resumeText}\n";
        }

        $prompt .= "\n\nGere um JSON com os seguintes campos:
{
    \"summary\": \"[Resumo Profissional Detalhado: Sintetize a trajetória e inclua quando citado, os motivos de saída dos últimos empregos (conforme relatado), composição familiar e detalhes de deslocamento/logística citados]\",
    \"technical_skills\": [\"Competência 1\", \"Competência 2\", \"Competência 3\"],
    \"behavioral_posture\": \"[Postura Comportamental: Descreva sua percepção sobre o candidato (ex: 'Percebi o candidato nervoso...', 'Notei segurança...'). Escreva COMO SE FOSSE O RECRUTADOR (1ª pessoa). ]\",
    \"strengths\": [\"Ponto Forte 1\", \"Ponto Forte 2\", \"Ponto Forte 3\"],
    \"development_points\": [\"Ponto a Desenvolver 1\", \"Ponto a Desenvolver 2\"],
    \"final_opinion\": \"[Parecer Final - Sua conclusão e recomendação em 1ª pessoa (ex: 'Recomendo o candidato...', 'Minha avaliação é...') ]\",
    \"status\": \"[Situação recomendada: 'recommended', 'recommended_with_reservations', ou 'not_recommended']\"
}

IMPORTANTE: 
1. Campos 'technical_skills', 'strengths' e 'development_points' DEVEM ser arrays de strings (listas).
2. ATENÇÃO AOS DETALHES: Não ignore informações sobre Motivos de saída de empresas anteriores, Composição familiar, Deslocamento/Logística, e Estado Emocional (nervosismo/segurança). Essas informações são vitais para o parecer.
Se alguma informação não estiver explícita no texto, infira com base no contexto ou deixe genérico, mas profissional. O status deve ser baseado no tom do parecer.";

        return $prompt;
    }

    protected function parseReportStructureResponse(string $content): array
    {
        $content = preg_replace('/```json\s*|\s*```/', '', $content);
        $content = trim($content);

        try {
            $data = json_decode($content, true);
            // Validating arrays
             $data['technical_skills'] = is_array($data['technical_skills'] ?? null) ? $data['technical_skills'] : [];
             $data['strengths'] = is_array($data['strengths'] ?? null) ? $data['strengths'] : [];
             $data['development_points'] = is_array($data['development_points'] ?? null) ? $data['development_points'] : [];
            return $data;
        } catch (\Exception $e) {
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
            'status' => 'recommended_with_reservations'
        ];
    }

    /**
     * Analisa o currículo do candidato e extrai informações básicas
     */
    public function analyzeCandidateResume(string $resumeContent): ?array
    {
        try {
            $prompt = $this->buildCandidateAnalysisPrompt($resumeContent);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->apiUrl, [
                'model' => 'gpt-4o-mini',
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
                'temperature' => 0.3,
                'max_tokens' => 800,
            ]);

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
        try {
            $prompt = $this->buildJobMatchAnalysisPrompt($resumeContent, $jobData);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post($this->apiUrl, [
                'model' => 'gpt-4o-mini',
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
                'temperature' => 0.7,
                'max_tokens' => 1500,
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

            // 2. Se o texto extraído for muito curto, provavelmente é um PDF escaneado
            if ($text && strlen(trim($text)) > 100) {
                Log::info('PDF extracted with pdfparser', ['chars' => strlen($text)]);
                return $text;
            }

            // 3. Fallback: usar OCR com OpenAI Vision
            Log::info('PDF text extraction insufficient, trying OCR with OpenAI Vision', [
                'file' => $filePath,
                'extracted_chars' => strlen($text ?? '')
            ]);

            return $this->extractTextFromPDFWithOCR($filePath);

        } catch (\Exception $e) {
            Log::error('PDF Extraction Error', [
                'file' => $filePath,
                'message' => $e->getMessage()
            ]);

            // Tentar OCR mesmo em caso de erro na extração normal
            try {
                return $this->extractTextFromPDFWithOCR($filePath);
            } catch (\Exception $ocrException) {
                Log::error('PDF OCR Fallback Error', [
                    'file' => $filePath,
                    'message' => $ocrException->getMessage()
                ]);
                return null;
            }
        }
    }

    /**
     * Extrai texto de PDF usando OCR via OpenAI Vision API
     */
    protected function extractTextFromPDFWithOCR(string $filePath): ?string
    {
        try {
            // Verificar se Imagick está disponível
            if (!extension_loaded('imagick')) {
                Log::warning('Imagick extension not available for PDF OCR');
                return null;
            }

            $extractedText = [];
            $imagick = new \Imagick();
            
            // Configurar resolução para melhor qualidade de OCR
            $imagick->setResolution(200, 200);
            
            // Ler PDF (limitar a 10 páginas para evitar timeout)
            $imagick->readImage($filePath . '[0-9]'); // Páginas 0 a 9
            
            $pageCount = $imagick->getNumberImages();
            Log::info('Processing PDF with OCR', ['pages' => $pageCount]);

            foreach ($imagick as $pageNum => $page) {
                // Converter para formato de imagem
                $page->setImageFormat('png');
                $page->setImageCompressionQuality(85);
                
                // Converter para base64
                $imageData = base64_encode($page->getImageBlob());
                
                // Enviar para OpenAI Vision
                $pageText = $this->extractTextFromImageWithVision($imageData, $pageNum + 1);
                
                if ($pageText) {
                    $extractedText[] = $pageText;
                }
                
                // Limitar para evitar custos excessivos (máximo 5 páginas por vez)
                if ($pageNum >= 4) {
                    Log::info('OCR limited to first 5 pages');
                    break;
                }
            }

            $imagick->clear();
            $imagick->destroy();

            $fullText = implode("\n\n--- Página ---\n\n", $extractedText);
            
            Log::info('PDF OCR completed', ['total_chars' => strlen($fullText)]);
            
            return $fullText ?: null;

        } catch (\Exception $e) {
            Log::error('PDF OCR Error', [
                'file' => $filePath,
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Extrai texto de imagem usando OpenAI Vision API
     */
    protected function extractTextFromImageWithVision(string $base64Image, int $pageNumber = 1): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($this->apiUrl, [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Você é um assistente especializado em extrair texto de imagens de currículos e documentos. Extraia TODO o texto visível na imagem, mantendo a estrutura e formatação original o máximo possível. Se houver informações de contato, experiências profissionais, formação acadêmica ou habilidades, certifique-se de capturá-las completamente.'
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => "Extraia todo o texto desta página {$pageNumber} de um currículo/documento. Retorne apenas o texto extraído, sem comentários adicionais."
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => "data:image/png;base64,{$base64Image}",
                                    'detail' => 'high'
                                ]
                            ]
                        ]
                    ]
                ],
                'max_tokens' => 4000,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return $result['choices'][0]['message']['content'] ?? null;
            }

            Log::error('OpenAI Vision API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('OpenAI Vision Exception', [
                'page' => $pageNumber,
                'message' => $e->getMessage()
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
                    // Remove XML tags
                    $content = str_replace('</w:p>', "\n", $content);
                    $content = strip_tags($content);
                    return trim($content);
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
        try {
            $prompt = $this->buildDiscAnalysisPrompt($profileData);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($this->apiUrl, [
                'model' => 'gpt-4o-mini',
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
                'temperature' => 0.7,
                'max_tokens' => 4000,
                'response_format' => ['type' => 'json_object'],
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
    \"analysis\": \"[Análise detalhada do perfil comportamental, explicando como as características se manifestam no ambiente de trabalho - 3-4 parágrafos]\",
    \"strengths\": \"[Lista de 5-7 pontos fortes principais deste perfil, separados por ponto e vírgula]\",
    \"development_areas\": \"[Lista de 3-5 áreas que podem ser desenvolvidas ou desafios potenciais, separados por ponto e vírgula]\",
    \"ideal_roles\": \"[Lista de 5-8 tipos de funções ou cargos ideais para este perfil, separados por ponto e vírgula]\",
    \"work_style\": \"[Descrição do estilo de trabalho preferido, ambiente ideal, como se comunica, como toma decisões, como trabalha em equipe - 2-3 parágrafos]\"
}

Seja específico, profissional e forneça insights práticos e aplicáveis ao contexto de recrutamento e seleção.";
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
                'analysis' => $data['analysis'] ?? 'Análise não disponível',
                'strengths' => $data['strengths'] ?? 'Pontos fortes não identificados',
                'development_areas' => $data['development_areas'] ?? 'Áreas de desenvolvimento não identificadas',
                'ideal_roles' => $data['ideal_roles'] ?? 'Funções ideais não identificadas',
                'work_style' => $data['work_style'] ?? 'Estilo de trabalho não identificado',
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
        try {
            $prompt = $this->buildCultureFitAnalysisPrompt($profileData);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($this->apiUrl, [
                'model' => 'gpt-4o-mini',
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
                'temperature' => 0.7,
                'max_tokens' => 4000,
                'response_format' => ['type' => 'json_object'],
            ]);

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
    \"analysis\": \"[Análise detalhada do perfil cultural, explicando as preferências e valores do candidato no ambiente de trabalho - 3-4 parágrafos]\",
    \"cultural_profile\": \"[Descrição do perfil cultural dominante do candidato em 2-3 frases, ex: 'Perfil Inovador-Autônomo' ou 'Perfil Estruturado-Colaborativo']\",
    \"strengths\": \"[Lista de 5-7 pontos fortes culturais deste perfil, separados por ponto e vírgula]\",
    \"challenges\": \"[Lista de 3-5 desafios de adaptação ou ambientes que podem ser difíceis para este perfil, separados por ponto e vírgula]\",
    \"ideal_environments\": \"[Lista de 5-8 características de ambientes organizacionais ideais (ex: startups, empresas tradicionais, equipes remotas, etc), separados por ponto e vírgula]\",
    \"recommendations\": \"[Recomendações práticas para o recrutador sobre como avaliar o fit deste candidato e em que tipos de vagas ele se encaixaria melhor - 2-3 parágrafos]\"
}

Seja específico, profissional e forneça insights práticos para decisões de recrutamento baseadas em fit cultural.";
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
                'analysis' => $data['analysis'] ?? 'Análise não disponível',
                'cultural_profile' => $data['cultural_profile'] ?? 'Perfil cultural não identificado',
                'strengths' => $data['strengths'] ?? 'Pontos fortes não identificados',
                'challenges' => $data['challenges'] ?? 'Desafios não identificados',
                'ideal_environments' => $data['ideal_environments'] ?? 'Ambientes ideais não identificados',
                'recommendations' => $data['recommendations'] ?? 'Recomendações não disponíveis',
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
    public function extractContactInfo(string $resumeContent, array $categories = []): ?array
    {
        try {
            $prompt = $this->buildContactInfoExtractionPrompt($resumeContent, $categories);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($this->apiUrl, [
                'model' => 'gpt-4o-mini',
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
                'temperature' => 0.1, // Baixa temperatura para extração factual
                'max_tokens' => 300,
            ]);

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
            Log::error('OpenAI Contact Extraction Exception', ['message' => $e->getMessage()]);
            return null;
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
        
        try {
            $data = json_decode($content, true);
            
            return [
                'name' => $data['name'] ?? '',
                'email' => $data['email'] ?? '',
                'phone' => $data['phone'] ?? '',
                'professional_area' => $data['professional_area'] ?? '',
                'category_id' => $data['category_id'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'name' => '',
                'email' => '',
                'phone' => '',
                'professional_area' => '',
                'category_id' => null,
            ];
        }
    }
}

