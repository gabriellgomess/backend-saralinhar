<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeCandidateJob;
use App\Jobs\ProcessResumeJob;
use App\Models\BatchUpload;
use App\Models\BatchUploadFile;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\CandidateJobApplication;
use App\Models\Job;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class CandidateController extends Controller
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Submete currículo (com ou sem vaga específica)
     */
    public function submit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'resume_file' => 'nullable|file|mimes:pdf,doc,docx,txt|max:5120', // 5MB
            'job_id' => 'nullable|exists:job_listings,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Upload do arquivo se enviado
            $filePath = null;
            $originalName = null;

            if ($request->hasFile('resume_file')) {
                $file = $request->file('resume_file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('resumes', $fileName, 'public');
                $originalName = $file->getClientOriginalName();
            }

            // Verifica se candidato já existe
            $candidate = Candidate::where('email', $request->email)->first();

            if ($candidate) {
                // Atualiza dados do candidato existente
                $candidateData = [
                    'name' => $request->name,
                    'phone' => $request->phone,
                ];

                if ($filePath) {
                    $candidateData['file_path'] = $filePath;
                    $candidateData['file_original_name'] = $originalName;
                    $candidateData['status'] = 'pending';
                }

                $candidate->update($candidateData);
            } else {
                // Cria novo candidato
                $candidate = Candidate::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'file_path' => $filePath,
                    'file_original_name' => $originalName,
                    'status' => $filePath ? 'pending' : 'analyzed',
                ]);
            }

            // Análise do currículo em background se houver arquivo
            if ($candidate->file_path && $filePath) {
                $this->analyzeCandidateInBackground($candidate);
            }

            // Se foi especificada uma vaga, cria a candidatura
            if ($request->job_id) {
                $job = Job::findOrFail($request->job_id);

                // Verifica se já não se candidatou a esta vaga
                $existingApplication = CandidateJobApplication::where('candidate_id', $candidate->id)
                    ->where('job_id', $job->id)
                    ->first();

                if (!$existingApplication) {
                    $application = CandidateJobApplication::create([
                        'candidate_id' => $candidate->id,
                        'job_id' => $job->id,
                        'status' => 'pending',
                    ]);

                    // Analisa aderência à vaga em background
                    $this->analyzeJobMatchInBackground($candidate, $job, $application);
                }
            }

            return response()->json([
                'message' => 'Currículo enviado com sucesso!',
                'candidate' => $candidate
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erro ao submeter currículo: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro ao submeter currículo'
            ], 500);
        }
    }

    /**
     * Analisa o currículo do candidato em background
     */
    protected function analyzeCandidateInBackground(Candidate $candidate)
    {
        try {
            $fullPath = storage_path('app/public/' . $candidate->file_path);

            // Extrai texto do arquivo
            $resumeText = $this->openAIService->extractTextFromFile($fullPath);

            if (!$resumeText) {
                $candidate->update(['status' => 'error']);
                return;
            }

            // Analisa o currículo
            $analysis = $this->openAIService->analyzeCandidateResume($resumeText);

            if ($analysis) {
                $candidate->update([
                    'city' => $analysis['city'],
                    'professional_area' => $analysis['professional_area'],
                    'qualifications_summary' => $analysis['qualifications_summary'],
                    'status' => 'analyzed',
                ]);
            } else {
                $candidate->update(['status' => 'error']);
            }

        } catch (\Exception $e) {
            Log::error('Erro na análise do candidato', [
                'candidate_id' => $candidate->id,
                'error' => $e->getMessage()
            ]);
            $candidate->update(['status' => 'error']);
        }
    }

    /**
     * Analisa aderência do candidato à vaga em background
     */
    protected function analyzeJobMatchInBackground(Candidate $candidate, Job $job, CandidateJobApplication $application)
    {
        try {
            $fullPath = storage_path('app/public/' . $candidate->file_path);

            // Extrai texto do arquivo
            $resumeText = $this->openAIService->extractTextFromFile($fullPath);

            if (!$resumeText) {
                $application->update(['status' => 'error']);
                return;
            }

            // Prepara dados da vaga
            $jobData = [
                'title' => $job->title,
                'company' => $job->company,
                'type' => $job->type,
                'description' => $job->description,
                'responsibilities' => $job->responsibilities,
                'requirements' => $job->requirements,
            ];

            // Analisa aderência
            $analysis = $this->openAIService->analyzeResumeForJob($resumeText, $jobData);

            if ($analysis) {
                $application->update([
                    'adherence_score' => $analysis['adherence_score'],
                    'strengths' => $analysis['strengths'],
                    'attention_points' => $analysis['attention_points'],
                    'ai_analysis' => json_encode($analysis),
                    'status' => 'analyzed',
                ]);
            } else {
                $application->update(['status' => 'error']);
            }

        } catch (\Exception $e) {
            Log::error('Erro na análise de aderência', [
                'application_id' => $application->id,
                'error' => $e->getMessage()
            ]);
            $application->update(['status' => 'error']);
        }
    }

    /**
     * Lista todos os candidatos (Banco de Currículos)
     */
    public function index(Request $request)
    {
        $query = Candidate::query();

        // Filtros
        if ($request->has('name') && $request->name) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->has('email') && $request->email) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }

        if ($request->has('location') && $request->location) {
            $query->where('city', 'like', '%' . $request->location . '%');
        } else if ($request->has('city') && $request->city) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }

        if ($request->has('professional_area') && $request->professional_area) {
            $query->where('professional_area', $request->professional_area);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $candidates = $query->orderBy('created_at', 'desc')->get();

        return response()->json($candidates, 200);
    }

    /**
     * Retorna áreas profissionais únicas
     */
    public function professionalAreas()
    {
        $areas = Candidate::whereNotNull('professional_area')
            ->where('professional_area', '!=', 'Não identificada')
            ->distinct()
            ->pluck('professional_area')
            ->sort()
            ->values();

        return response()->json($areas, 200);
    }

    /**
     * Retorna a lista de candidatos com suas respectivas coordenadas geográficas.
     */
    public function mapStats()
    {
        try {
            // Encontra cidades únicas nos candidatos ordenadas por número de candidatos (descendente)
            $uniqueCities = Candidate::select('city', \DB::raw('count(*) as count'))
                ->whereNotNull('city')
                ->where('city', '!=', '')
                ->groupBy('city')
                ->orderBy('count', 'desc')
                ->pluck('city')
                ->toArray();

            // Identifica quais precisam de geocodificação
            $cachedCities = \App\Models\CityGeocode::pluck('city')->toArray();
            $missingCities = array_diff(array_map('trim', $uniqueCities), $cachedCities);

            // Geocodifica até 10 novas cidades por requisição para evitar timeouts
            $geocodedCount = 0;
            foreach ($missingCities as $city) {
                if ($geocodedCount >= 10) break;
                $this->geocodeCity($city);
                $geocodedCount++;
            }

            // Busca candidatos com suas coordenadas correspondentes
            $candidatesWithCoords = Candidate::select(
                    'candidates.id',
                    'candidates.name',
                    'candidates.email',
                    'candidates.phone',
                    'candidates.city',
                    'candidates.professional_area',
                    'city_geocodes.latitude',
                    'city_geocodes.longitude'
                )
                ->join('city_geocodes', 'candidates.city', '=', 'city_geocodes.city')
                ->whereNotNull('city_geocodes.latitude')
                ->whereNotNull('city_geocodes.longitude')
                ->get();

            return response()->json($candidatesWithCoords, 200);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar map-stats dos candidatos', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Erro ao carregar mapa de candidatos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auxiliar para geocodificar uma cidade via OpenStreetMap Nominatim
     */
    private function geocodeCity($city)
    {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get('https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'q' => $city . ', Brazil', // Força busca no Brasil
                    'format' => 'json',
                    'limit' => 1
                ],
                'headers' => [
                    'User-Agent' => 'SaraLinharMap/1.0 (contato@saralinhar.com.br)'
                ],
                'timeout' => 5
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            if (!empty($data) && isset($data[0])) {
                $lat = (float) $data[0]['lat'];
                $lon = (float) $data[0]['lon'];

                return \App\Models\CityGeocode::create([
                    'city' => $city,
                    'latitude' => $lat,
                    'longitude' => $lon
                ]);
            }

            // Cache negativo: registra nulo para cidades não encontradas para não repetir requisições
            return \App\Models\CityGeocode::create([
                'city' => $city,
                'latitude' => null,
                'longitude' => null
            ]);
        } catch (\Exception $e) {
            Log::error("Falha na geocodificação da cidade: {$city}. Erro: " . $e->getMessage());
            return null; // Não salva cache negativo em caso de erro de rede para tentar novamente
        }
    }

    /**
     * Retorna candidaturas de uma vaga específica
     */
    public function getApplicationsByJob($jobId)
    {
        try {
            $applications = CandidateJobApplication::where('job_id', $jobId)
                ->with('candidate')
                ->withCount('comments')
                ->orderBy('created_at', 'desc')
                ->get();

            $applications->each(function ($application) use ($jobId) {
                $candidate = $application->candidate;
                if (!$candidate) return;

                $disc = \App\Models\DiscTestResult::where('candidate_id', $candidate->id)
                    ->whereIn('status', ['analyzed', 'completed'])
                    ->latest()
                    ->first();

                // Fallback por email para resultados antigos sem candidate_id
                if (!$disc && $candidate->email) {
                    $disc = \App\Models\DiscTestResult::where('testee_email', $candidate->email)
                        ->whereIn('status', ['analyzed', 'completed'])
                        ->latest()
                        ->first();
                }

                $cultureFit = \App\Models\CultureFitResult::where('candidate_id', $candidate->id)
                    ->latest()
                    ->first();

                if (!$cultureFit && $candidate->email) {
                    $cultureFit = \App\Models\CultureFitResult::where('testee_email', $candidate->email)
                        ->latest()
                        ->first();
                }

                $application->disc_result = $disc ? [
                    'id'                  => $disc->id,
                    'primary_profile'     => $disc->primary_profile,
                    'profile_percentages' => $disc->profile_percentages,
                ] : null;

                $application->culture_fit_result = $cultureFit ? [
                    'id'              => $cultureFit->id,
                    'cultural_profile' => $cultureFit->cultural_profile,
                ] : null;

                // Busca os mapeamentos (AssessmentApplication e AssessmentResult)
                $assessments = \App\Models\AssessmentApplication::where('candidate_id', $candidate->id)
                    ->where('status', 'completed')
                    ->with('result', 'test')
                    ->get();
                
                if ($assessments->isEmpty() && $candidate->email) {
                    $assessments = \App\Models\AssessmentApplication::where('respondent_email', $candidate->email)
                        ->where('status', 'completed')
                        ->with('result', 'test')
                        ->get();
                }

                $application->assessments = $assessments->map(function ($assessment) {
                    return [
                        'id' => $assessment->id,
                        'test_name' => $assessment->test ? $assessment->test->name : 'Mapeamento',
                        'overall_score' => $assessment->result ? $assessment->result->overall_score : null,
                        'classification' => $assessment->result ? $assessment->result->classification : null,
                    ];
                });

                // Busca o parecer (CandidateReport) se houver
                $report = \App\Models\CandidateReport::where('job_id', $jobId)
                    ->where('candidate_name', $candidate->name)
                    ->first();

                $application->candidate_report = $report ? [
                    'id' => $report->id,
                    'status' => $report->status,
                    'report_type' => $report->report_type,
                ] : null;
            });

            return response()->json($applications, 200);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar candidaturas', [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erro ao buscar candidaturas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Adiciona/Vincula candidato (novo ou existente) a uma vaga
     */
    public function addCandidateToJob(Request $request, $jobId)
    {
        $candidateType = $request->input('candidate_type', 'new'); // 'new' ou 'existing'
        
        $rules = [
            'candidate_type' => 'required|in:new,existing',
            'pipeline_stage' => 'nullable|in:new,contacting,interview_scheduled,interviewed,shortlisted,rejected,hired',
            'interview_feedback' => 'nullable|string',
            'admin_notes' => 'nullable|string',
            'resume_file' => 'nullable|file|mimes:pdf,doc,docx,txt|max:5120',
            'parecer_file' => 'nullable|file|mimes:pdf,doc,docx,txt|max:5120',
            'disc_file' => 'nullable|file|mimes:pdf,doc,docx,txt|max:5120',
            'culture_fit_file' => 'nullable|file|mimes:pdf,doc,docx,txt|max:5120',
            'mapeamento_file' => 'nullable|file|mimes:pdf,doc,docx,txt|max:5120',
            
            'link_disc_id' => 'nullable|exists:disc_test_results,id',
            'link_culture_fit_id' => 'nullable|exists:culture_fit_results,id',
            'link_assessment_id' => 'nullable|exists:assessment_applications,id',
        ];

        if ($candidateType === 'existing') {
            $rules['candidate_id'] = 'required|exists:candidates,id';
        } else {
            $rules['name'] = 'required|string|max:255';
            $rules['email'] = 'required|email|max:255';
            $rules['phone'] = 'nullable|string|max:20';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $job = Job::findOrFail($jobId);
            $candidate = null;

            if ($candidateType === 'existing') {
                $candidate = Candidate::findOrFail($request->candidate_id);
            } else {
                // Verifica se candidato já existe com esse e-mail
                $candidate = Candidate::where('email', $request->email)->first();

                if ($candidate) {
                    $candidate->update([
                        'name' => $request->name,
                        'phone' => $request->phone ?: $candidate->phone,
                    ]);
                } else {
                    $candidate = Candidate::create([
                        'name' => $request->name,
                        'email' => $request->email,
                        'phone' => $request->phone,
                        'status' => 'pending',
                    ]);
                }
            }

            // Upload do currículo se fornecido
            if ($request->hasFile('resume_file')) {
                $file = $request->file('resume_file');
                $fileName = time() . '_resume_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('resumes', $fileName, 'public');

                $candidate->update([
                    'file_path' => $filePath,
                    'file_original_name' => $file->getClientOriginalName(),
                    'status' => 'pending',
                ]);

                // Analisa currículo em background
                $this->analyzeCandidateInBackground($candidate);
            }

            // Verifica se candidatura já existe
            $application = CandidateJobApplication::where('candidate_id', $candidate->id)
                ->where('job_id', $job->id)
                ->first();

            if ($application) {
                return response()->json([
                    'message' => 'Este candidato já está associado a esta vaga.'
                ], 400);
            }

            // Cria candidatura
            $applicationData = [
                'candidate_id' => $candidate->id,
                'job_id' => $job->id,
                'status' => 'pending',
                'pipeline_stage' => $request->input('pipeline_stage', 'new'),
                'interview_feedback' => $request->input('interview_feedback'),
                'admin_notes' => $request->input('admin_notes'),
            ];

            // Upload de outros arquivos específicos
            $fileTypes = ['parecer', 'disc', 'culture_fit', 'mapeamento'];
            foreach ($fileTypes as $type) {
                $fileField = $type . '_file';
                if ($request->hasFile($fileField)) {
                    $file = $request->file($fileField);
                    $fileName = time() . '_' . $type . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs($type . 's', $fileName, 'public');

                    $applicationData[$type . '_file_path'] = $filePath;
                    $applicationData[$type . '_file_original_name'] = $file->getClientOriginalName();
                }
            }

            $application = CandidateJobApplication::create($applicationData);

            // Vincula testes existentes se solicitado
            if ($request->filled('link_disc_id')) {
                \App\Models\DiscTestResult::where('id', $request->link_disc_id)
                    ->update(['candidate_id' => $candidate->id]);
            }
            if ($request->filled('link_culture_fit_id')) {
                \App\Models\CultureFitResult::where('id', $request->link_culture_fit_id)
                    ->update(['candidate_id' => $candidate->id]);
            }
            if ($request->filled('link_assessment_id')) {
                \App\Models\AssessmentApplication::where('id', $request->link_assessment_id)
                    ->update(['candidate_id' => $candidate->id]);
            }

            // Se o candidato já tem currículo e a candidatura foi criada, roda análise de aderência
            if ($candidate->file_path) {
                $this->analyzeJobMatchInBackground($candidate, $job, $application);
            }

            return response()->json([
                'message' => 'Candidato adicionado e vinculado à vaga com sucesso!',
                'candidate' => $candidate,
                'application' => $application
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erro ao adicionar candidato à vaga: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro ao adicionar candidato à vaga',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateApplicationFilesAndTests(Request $request, $jobId, $applicationId)
    {
        $rules = [
            'resume_file' => 'nullable|file|mimes:pdf,doc,docx,txt|max:5120',
            'parecer_file' => 'nullable|file|mimes:pdf,doc,docx,txt|max:5120',
            'disc_file' => 'nullable|file|mimes:pdf,doc,docx,txt|max:5120',
            'culture_fit_file' => 'nullable|file|mimes:pdf,doc,docx,txt|max:5120',
            'mapeamento_file' => 'nullable|file|mimes:pdf,doc,docx,txt|max:5120',
            
            'link_disc_id' => 'nullable',
            'link_culture_fit_id' => 'nullable',
            'link_assessment_id' => 'nullable',

            'remove_resume_file' => 'nullable',
            'remove_parecer_file' => 'nullable',
            'remove_disc_file' => 'nullable',
            'remove_culture_fit_file' => 'nullable',
            'remove_mapeamento_file' => 'nullable',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $application = CandidateJobApplication::where('id', $applicationId)
                ->where('job_id', $jobId)
                ->firstOrFail();

            $candidate = $application->candidate;
            $job = $application->job;

            // Desvincular/Remover arquivos físicos se solicitado
            if ($request->input('remove_resume_file') == '1' || $request->input('remove_resume_file') === 'true' || $request->input('remove_resume_file') === true) {
                $candidate->update([
                    'file_path' => null,
                    'file_original_name' => null,
                ]);
                
                $application->update([
                    'adherence_score' => null,
                    'strengths' => null,
                    'attention_points' => null,
                    'ai_analysis' => null,
                    'status' => 'pending',
                ]);
            }

            $fileFieldsToClear = [];
            foreach (['parecer', 'disc', 'culture_fit', 'mapeamento'] as $type) {
                $removeField = 'remove_' . $type . '_file';
                if ($request->input($removeField) == '1' || $request->input($removeField) === 'true' || $request->input($removeField) === true) {
                    $fileFieldsToClear[$type . '_file_path'] = null;
                    $fileFieldsToClear[$type . '_file_original_name'] = null;
                }
            }
            if (!empty($fileFieldsToClear)) {
                $application->update($fileFieldsToClear);
            }

            // Upload do currículo se fornecido
            if ($request->hasFile('resume_file')) {
                $file = $request->file('resume_file');
                $fileName = time() . '_resume_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('resumes', $fileName, 'public');

                $candidate->update([
                    'file_path' => $filePath,
                    'file_original_name' => $file->getClientOriginalName(),
                ]);

                // Roda análise de match
                $this->analyzeJobMatchInBackground($candidate, $job, $application);
            }

            // Upload de outros arquivos
            $fileTypes = ['parecer', 'disc', 'culture_fit', 'mapeamento'];
            $updatedData = [];
            foreach ($fileTypes as $type) {
                $fileField = $type . '_file';
                if ($request->hasFile($fileField)) {
                    $file = $request->file($fileField);
                    $fileName = time() . '_' . $type . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs($type . 's', $fileName, 'public');

                    $updatedData[$type . '_file_path'] = $filePath;
                    $updatedData[$type . '_file_original_name'] = $file->getClientOriginalName();
                }
            }

            if (!empty($updatedData)) {
                $application->update($updatedData);
            }

            // Vincula/Desvincula testes existentes do banco
            if ($request->has('link_disc_id')) {
                $linkDiscId = $request->input('link_disc_id');
                \App\Models\DiscTestResult::where('candidate_id', $candidate->id)->update(['candidate_id' => null]);
                if (!empty($linkDiscId)) {
                    \App\Models\DiscTestResult::where('id', $linkDiscId)->update(['candidate_id' => $candidate->id]);
                }
            }
            if ($request->has('link_culture_fit_id')) {
                $linkCultureFitId = $request->input('link_culture_fit_id');
                \App\Models\CultureFitResult::where('candidate_id', $candidate->id)->update(['candidate_id' => null]);
                if (!empty($linkCultureFitId)) {
                    \App\Models\CultureFitResult::where('id', $linkCultureFitId)->update(['candidate_id' => $candidate->id]);
                }
            }
            if ($request->has('link_assessment_id')) {
                $linkAssessmentId = $request->input('link_assessment_id');
                \App\Models\AssessmentApplication::where('candidate_id', $candidate->id)->update(['candidate_id' => null]);
                if (!empty($linkAssessmentId)) {
                    \App\Models\AssessmentApplication::where('id', $linkAssessmentId)->update(['candidate_id' => $candidate->id]);
                }
            }

            return response()->json([
                'message' => 'Arquivos e testes atualizados com sucesso!',
                'application' => $application->fresh()
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar arquivos da candidatura: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro ao atualizar arquivos da candidatura',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Busca testes realizados por e-mail para vincular
     */
    public function searchTestsByEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $email = $request->email;
        $candidate = Candidate::where('email', $email)->first();
        $candidateId = $candidate ? $candidate->id : null;

        // DISC
        $discQuery = \App\Models\DiscTestResult::whereIn('status', ['analyzed', 'completed']);
        if ($candidateId) {
            $discQuery->where(function($q) use ($candidateId, $email) {
                $q->where('candidate_id', $candidateId)
                  ->orWhere('testee_email', $email);
            });
        } else {
            $discQuery->where('testee_email', $email);
        }
        $discResults = $discQuery->orderBy('created_at', 'desc')->get();

        // Culture Fit
        $fitQuery = \App\Models\CultureFitResult::query();
        if ($candidateId) {
            $fitQuery->where(function($q) use ($candidateId, $email) {
                $q->where('candidate_id', $candidateId)
                  ->orWhere('testee_email', $email);
            });
        } else {
            $fitQuery->where('testee_email', $email);
        }
        $fitResults = $fitQuery->orderBy('created_at', 'desc')->get();

        // Mapeamentos (Assessments)
        $assessmentQuery = \App\Models\AssessmentApplication::where('status', 'completed')->with('test');
        if ($candidateId) {
            $assessmentQuery->where(function($q) use ($candidateId, $email) {
                $q->where('candidate_id', $candidateId)
                  ->orWhere('respondent_email', $email);
            });
        } else {
            $assessmentQuery->where('respondent_email', $email);
        }
        $assessments = $assessmentQuery->orderBy('created_at', 'desc')->get();

        return response()->json([
            'disc_results' => $discResults->map(function($r) {
                return [
                    'id' => $r->id,
                    'profile' => $r->primary_profile,
                    'date' => $r->created_at->format('d/m/Y'),
                ];
            }),
            'culture_fit_results' => $fitResults->map(function($r) {
                return [
                    'id' => $r->id,
                    'profile' => $r->cultural_profile,
                    'date' => $r->created_at->format('d/m/Y'),
                ];
            }),
            'assessments' => $assessments->map(function($r) {
                return [
                    'id' => $r->id,
                    'test_name' => $r->test ? $r->test->name : 'Mapeamento',
                    'date' => $r->completed_at ? $r->completed_at->format('d/m/Y') : $r->created_at->format('d/m/Y'),
                ];
            })
        ]);
    }

    public function downloadApplicationFile($jobId, $applicationId, $fileType)
    {
        $application = CandidateJobApplication::where('job_id', $jobId)->findOrFail($applicationId);
        
        if ($fileType === 'resume') {
            $candidate = $application->candidate;
            if (!$candidate || !$candidate->file_path) {
                return response()->json(['message' => 'Este candidato não possui arquivo de currículo'], 404);
            }
            
            $filePath = storage_path('app/public/' . $candidate->file_path);
            
            if (!file_exists($filePath)) {
                return response()->json(['message' => 'Arquivo não encontrado'], 404);
            }
            
            return response()->download($filePath, $candidate->file_original_name);
        }

        $pathField = $fileType . '_file_path';
        $nameField = $fileType . '_file_original_name';
        
        if (!in_array($fileType, ['parecer', 'disc', 'culture_fit', 'mapeamento'])) {
            return response()->json(['message' => 'Tipo de arquivo inválido'], 400);
        }
        
        $path = $application->$pathField;
        $originalName = $application->$nameField;
        
        if (!$path) {
            return response()->json(['message' => 'Arquivo não encontrado para esta candidatura'], 404);
        }
        
        $filePath = storage_path('app/public/' . $path);
        
        if (!file_exists($filePath)) {
            return response()->json(['message' => 'Arquivo físico não encontrado'], 404);
        }
        
        return response()->download($filePath, $originalName);
    }

    /**
     * Retorna os comentários da candidatura
     */
    public function getComments($applicationId)
    {
        try {
            $comments = \App\Models\ApplicationComment::where('candidate_job_application_id', $applicationId)
                ->with('user:id,name,role')
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json($comments, 200);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar comentários da candidatura: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro ao buscar comentários',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Adiciona um novo comentário à candidatura
     */
    public function addComment(Request $request, $applicationId)
    {
        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $application = CandidateJobApplication::findOrFail($applicationId);

            $comment = \App\Models\ApplicationComment::create([
                'candidate_job_application_id' => $application->id,
                'user_id' => Auth::id(),
                'comment' => $request->comment,
            ]);

            // Carrega o usuário para retornar formatado
            $comment->load('user:id,name,role');

            return response()->json($comment, 201);
        } catch (\Exception $e) {
            Log::error('Erro ao adicionar comentário: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro ao adicionar comentário',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exclui um comentário da candidatura
     */
    public function deleteComment($applicationId, $commentId)
    {
        try {
            $comment = \App\Models\ApplicationComment::where('id', $commentId)
                ->where('candidate_job_application_id', $applicationId)
                ->firstOrFail();

            // Verifica se o usuário logado é o autor do comentário
            if ($comment->user_id !== Auth::id()) {
                return response()->json([
                    'message' => 'Você não tem permissão para excluir este comentário.'
                ], 403);
            }

            $comment->delete();

            return response()->json([
                'message' => 'Comentário excluído com sucesso!'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao excluir comentário: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro ao excluir comentário',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exibe detalhes de um candidato
     */
    public function show($id)
    {
        $candidate = Candidate::with('jobApplications.job')->findOrFail($id);
        return response()->json($candidate, 200);
    }

    /**
     * Download do arquivo do currículo
     */
    public function download($id)
    {
        $candidate = Candidate::findOrFail($id);

        if (!$candidate->file_path) {
            return response()->json(['message' => 'Este candidato não possui arquivo de currículo'], 404);
        }

        $filePath = storage_path('app/public/' . $candidate->file_path);

        if (!file_exists($filePath)) {
            return response()->json(['message' => 'Arquivo não encontrado'], 404);
        }

        return response()->download($filePath, $candidate->file_original_name);
    }

    /**
     * Deleta um candidato
     */
    public function destroy($id)
    {
        $candidate = Candidate::findOrFail($id);

        // Remove arquivo físico apenas se existir
        if ($candidate->file_path && Storage::disk('public')->exists($candidate->file_path)) {
            Storage::disk('public')->delete($candidate->file_path);
        }

        $candidate->delete();

        return response()->json([
            'message' => 'Candidato removido com sucesso'
        ], 200);
    }

    /**
     * Atualiza dados do candidato
     */
    public function update(Request $request, $id)
    {
        $candidate = Candidate::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:candidates,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'professional_area' => 'nullable|string|max:100',
            'resume_file' => 'nullable|file|mimes:pdf,doc,docx,txt|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = $request->only([
                'name', 'email', 'phone', 'city', 'professional_area'
            ]);

            $fileUploaded = false;

            // Remove o currículo anterior se solicitado
            $removeResume = $request->input('remove_resume_file');
            if ($removeResume == '1' || $removeResume === 'true' || $removeResume === true) {
                if ($candidate->file_path && Storage::disk('public')->exists($candidate->file_path)) {
                    Storage::disk('public')->delete($candidate->file_path);
                }
                $updateData['file_path'] = null;
                $updateData['file_original_name'] = null;
                $updateData['qualifications_summary'] = null;
                $updateData['status'] = 'analyzed'; // Sem currículo, o status é considerado resolvido (analisado)
            }

            if ($request->hasFile('resume_file')) {
                $file = $request->file('resume_file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('resumes', $fileName, 'public');

                // Remove antigo se existir
                if ($candidate->file_path && Storage::disk('public')->exists($candidate->file_path)) {
                    Storage::disk('public')->delete($candidate->file_path);
                }

                $updateData['file_path'] = $filePath;
                $updateData['file_original_name'] = $file->getClientOriginalName();
                $updateData['status'] = 'pending';
                $fileUploaded = true;
            }

            $candidate->update($updateData);

            if ($fileUploaded) {
                $this->analyzeCandidateInBackground($candidate);
            }

            return response()->json([
                'message' => 'Candidato atualizado com sucesso',
                'candidate' => $candidate
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar candidato: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro ao atualizar candidato',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vincula candidatos existentes a uma vaga
     */
    public function linkCandidatesToJob(Request $request, $jobId)
    {
        $validator = Validator::make($request->all(), [
            'candidate_ids' => 'required|array|min:1',
            'candidate_ids.*' => 'exists:candidates,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $job = Job::findOrFail($jobId);
            $candidateIds = $request->candidate_ids;
            $linkedCount = 0;
            $skippedCount = 0;

            foreach ($candidateIds as $candidateId) {
                $candidate = Candidate::findOrFail($candidateId);

                // Verifica se já existe candidatura
                $existingApplication = CandidateJobApplication::where('candidate_id', $candidateId)
                    ->where('job_id', $jobId)
                    ->first();

                if ($existingApplication) {
                    $skippedCount++;
                    continue;
                }

                // Cria candidatura
                $application = CandidateJobApplication::create([
                    'candidate_id' => $candidateId,
                    'job_id' => $jobId,
                    'status' => 'pending',
                ]);

                // Analisa aderência em background
                $this->analyzeJobMatchInBackground($candidate, $job, $application);
                $linkedCount++;
            }

            $message = "Vinculação concluída: {$linkedCount} candidato(s) vinculado(s)";
            if ($skippedCount > 0) {
                $message .= ", {$skippedCount} já vinculado(s) anteriormente";
            }

            return response()->json([
                'message' => $message,
                'linked' => $linkedCount,
                'skipped' => $skippedCount
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao vincular candidatos', [
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erro ao vincular candidatos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Desvincula um candidato de uma vaga
     */
    public function unlinkCandidateFromJob($jobId, $applicationId)
    {
        try {
            $application = CandidateJobApplication::where('id', $applicationId)
                ->where('job_id', $jobId)
                ->firstOrFail();

            $application->delete();

            return response()->json([
                'message' => 'Candidato desvinculado com sucesso'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao desvincular candidato', [
                'job_id' => $jobId,
                'application_id' => $applicationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erro ao desvincular candidato',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Extrai dados do currículo (nome, email, telefone) antes de salvar
     */
    public function extractData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resume_file' => 'required|file|max:10240', // Aumentei para 10MB e removi mimes estritos para evitar falsos negativos
            'store_temp' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Arquivo inválido',
                'errors' => $validator->errors(),
                'debug_files' => $_FILES // Apenas para debug temporário
            ], 422);
        }

        try {
            $file = $request->file('resume_file');
            // Salva temporariamente para extração - Usando disco public para garantir caminho correto
            $path = $file->store('temp_resumes', 'public');
            $fullPath = storage_path('app/public/' . $path);

            // Extrai texto
            $text = $this->openAIService->extractTextFromFile($fullPath);
            
            // Se não for para manter (fluxo normal), deleta. Se for batch (store_temp=true), mantém.
            $shouldStore = $request->boolean('store_temp');
            
            if (!$shouldStore && file_exists($fullPath)) {
                unlink($fullPath);
            }

            if (!$text) {
                // Se falhar a leitura e era pra salvar, deleta mesmo assim pra não deixar lixo
                if ($shouldStore && file_exists($fullPath)) {
                    unlink($fullPath);
                }
                return response()->json(['message' => 'Não foi possível ler o conteúdo do arquivo. Verifique se o arquivo não está corrompido.'], 422);
            }

            // Extrai dados com IA
            $categories = Category::where('is_active', true)->get(['id', 'name'])->toArray();
            $data = $this->openAIService->extractContactInfo($text, $categories);

            if (!$data) {
                // Remove arquivo temp para não deixar lixo em disco
                if ($shouldStore && file_exists($fullPath)) {
                    unlink($fullPath);
                }
                return response()->json(['message' => 'Não foi possível extrair dados do currículo'], 422);
            }

            // Se for batch, retorna o caminho temporário e o nome original
            if ($shouldStore) {
                $data['temp_path'] = $path;
                $data['original_name'] = $file->getClientOriginalName();
            }

            return response()->json($data, 200);

        } catch (\Exception $e) {
            Log::error('Erro na extração de dados: ' . $e->getMessage());
            return response()->json(['message' => 'Erro ao processar arquivo'], 500);
        }
    }

    /**
     * Salva múltiplos candidatos após conferência (Importação em Lote)
     */
    public function batchStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'candidates' => 'required|array',
            'candidates.*.name' => 'required|string|max:255',
            'candidates.*.email' => 'required|email|max:255',
            'candidates.*.temp_path' => 'required|string',
            'candidates.*.original_name' => 'required|string',
            'candidates.*.category_id' => 'nullable|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Dados inválidos', 'errors' => $validator->errors()], 422);
        }

        $created = 0;
        $errors = 0;
        $errorDetails = [];

        foreach ($request->candidates as $index => $candidateData) {
            try {
                $tempPath = $candidateData['temp_path'];
                $fullTempPath = storage_path('app/public/' . $tempPath);

                if (!file_exists($fullTempPath)) {
                    Log::error("Arquivo temporário não encontrado: $fullTempPath");
                    $errors++;
                    $errorDetails[] = [
                        'index' => $index,
                        'name' => $candidateData['name'] ?? '(desconhecido)',
                        'reason' => 'Arquivo temporário não encontrado. Reenvie o currículo.',
                    ];
                    continue;
                }

                // Move de temp para definitivo
                $newFileName = time() . '_' . uniqid() . '_' . $candidateData['original_name'];
                $newPath = 'resumes/' . $newFileName;
                
                // Move o arquivo
                Storage::disk('public')->move($tempPath, $newPath);

                // Verifica/Cria candidato
                $candidate = Candidate::where('email', $candidateData['email'])->first();

                if ($candidate) {
                    $candidate->update([
                        'name' => $candidateData['name'],
                        'phone' => $candidateData['phone'] ?? null,
                        'file_path' => $newPath,
                        'file_original_name' => $candidateData['original_name'],
                        'status' => 'pending',
                        'professional_area' => $candidateData['professional_area'] ?? null,
                    ]);

                } else {
                    $candidate = Candidate::create([
                        'name' => $candidateData['name'],
                        'email' => $candidateData['email'],
                        'phone' => $candidateData['phone'] ?? null,
                        'file_path' => $newPath,
                        'file_original_name' => $candidateData['original_name'],
                        'status' => 'pending',
                        'professional_area' => $candidateData['professional_area'] ?? null,
                    ]);
                }

                // Vincula categoria
                if (!empty($candidateData['category_id'])) {
                    $candidate->preferences()->sync([$candidateData['category_id']]);
                }

                // Dispara análise em background
                $this->analyzeCandidateInBackground($candidate);

                $created++;

            } catch (\Exception $e) {
                Log::error('Erro ao importar candidato em lote: ' . $e->getMessage());
                $errors++;
                $errorDetails[] = [
                    'index' => $index,
                    'name' => $candidateData['name'] ?? '(desconhecido)',
                    'reason' => 'Erro interno ao processar o arquivo.',
                ];
            }
        }

        return response()->json([
            'message' => "Importação concluída. $created salvos, $errors erros.",
            'created_count' => $created,
            'error_count' => $errors,
            'errors' => $errorDetails,
        ], 200);
    }

    // =========================================================================
    // IMPORTAÇÃO EM LOTE VIA FILA (novo fluxo assíncrono)
    // =========================================================================

    /**
     * Inicia um batch e retorna batch_id imediatamente.
     * POST /candidates/batch-start
     */
    public function batchStart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'total_files' => 'required|integer|min:1|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Dados inválidos', 'errors' => $validator->errors()], 422);
        }

        $batch = BatchUpload::create([
            'user_id'     => Auth::id(),
            'status'      => 'pending',
            'total_files' => $request->total_files,
        ]);

        return response()->json(['batch_id' => $batch->id], 201);
    }

    /**
     * Recebe UM arquivo, salva em disco e despacha job de IA.
     * Retorna imediatamente sem chamar a OpenAI.
     * POST /candidates/batch-add-file
     */
    public function batchAddFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'batch_id'    => 'required|exists:batch_uploads,id',
            'resume_file' => 'required|file|mimes:pdf,docx,txt|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Dados inválidos', 'errors' => $validator->errors()], 422);
        }

        $batch = BatchUpload::findOrFail($request->batch_id);

        if ($batch->user_id !== Auth::id()) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        try {
            $file     = $request->file('resume_file');
            $tempPath = $file->store('temp_resumes', 'public');

            $batchFile = BatchUploadFile::create([
                'batch_upload_id' => $batch->id,
                'original_name'   => $file->getClientOriginalName(),
                'temp_path'       => $tempPath,
                'status'          => 'queued',
            ]);

            if ($batch->status === 'pending') {
                $batch->update(['status' => 'processing']);
            }

            ProcessResumeJob::dispatch($batchFile->id);

            return response()->json(['file_id' => $batchFile->id], 202);

        } catch (\Exception $e) {
            Log::error('batchAddFile erro', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erro ao salvar arquivo'], 500);
        }
    }

    /**
     * Retorna progresso e resultados do batch.
     * GET /candidates/batch-status/{id}
     */
    public function batchStatus($id)
    {
        $batch = BatchUpload::with('files')->findOrFail($id);

        if ($batch->user_id !== Auth::id()) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        return response()->json([
            'batch_id'        => $batch->id,
            'status'          => $batch->status,
            'total_files'     => $batch->total_files,
            'processed_files' => $batch->processed_files,
            'failed_files'    => $batch->failed_files,
            'files'           => $batch->files->map(fn($f) => [
                'id'               => $f->id,
                'original_name'    => $f->original_name,
                'status'           => $f->status,
                'name'             => $f->name,
                'email'            => $f->email,
                'phone'            => $f->phone,
                'professional_area'=> $f->professional_area,
                'category_id'      => $f->category_id,
                'error_message'    => $f->error_message,
            ]),
        ]);
    }

    /**
     * Salva candidatos confirmados após revisão do usuário.
     * POST /candidates/batch-confirm
     */
    public function batchConfirm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'batch_id'                        => 'required|exists:batch_uploads,id',
            'candidates'                      => 'required|array|min:1',
            'candidates.*.file_id'            => [
                'required',
                'integer',
                'distinct',
                Rule::exists('batch_upload_files', 'id')->where(function ($query) use ($request) {
                    return $query->where('batch_upload_id', $request->input('batch_id'));
                }),
            ],
            'candidates.*.name'               => 'required|string|max:255',
            'candidates.*.email'              => 'nullable|email|max:255',
            'candidates.*.phone'              => 'nullable|string|max:20',
            'candidates.*.professional_area'  => 'nullable|string|max:100',
            'candidates.*.category_id'        => 'nullable|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Dados inválidos', 'errors' => $validator->errors()], 422);
        }

        $batch = BatchUpload::findOrFail($request->batch_id);

        if ($batch->user_id !== Auth::id()) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $created  = 0;
        $errors   = 0;
        $warnings = [];
        $errorDetails = [];

        foreach ($request->candidates as $index => $candidateData) {
            try {
                $batchFile = BatchUploadFile::where('batch_upload_id', $batch->id)
                    ->whereKey($candidateData['file_id'])
                    ->first();

                if (!$batchFile || !Storage::disk('public')->exists($batchFile->temp_path)) {
                    $errors++;
                    $errorDetails[] = [
                        'index'  => $index,
                        'name'   => $candidateData['name'],
                        'reason' => 'Arquivo temporário não encontrado.',
                    ];
                    continue;
                }

                // Somente arquivos processados com sucesso podem ser confirmados.
                if ($batchFile->status !== 'done') {
                    $errors++;
                    $errorDetails[] = [
                        'index'  => $index,
                        'name'   => $candidateData['name'],
                        'reason' => 'Arquivo não está disponível para confirmação.',
                    ];
                    continue;
                }

                $newFileName = time() . '_' . uniqid() . '_' . $batchFile->original_name;
                $newPath     = 'resumes/' . $newFileName;
                Storage::disk('public')->move($batchFile->temp_path, $newPath);

                $email = !empty($candidateData['email']) ? $candidateData['email'] : null;

                // Só busca por e-mail existente se o e-mail foi informado
                $candidate = $email
                    ? Candidate::where('email', $email)->first()
                    : null;

                if ($candidate) {
                    $candidate->update([
                        'name'               => $candidateData['name'],
                        'phone'              => $candidateData['phone'] ?? null,
                        'file_path'          => $newPath,
                        'file_original_name' => $batchFile->original_name,
                        'status'             => 'pending',
                        'professional_area'  => $candidateData['professional_area'] ?? null,
                    ]);
                } else {
                    $candidate = Candidate::create([
                        'name'               => $candidateData['name'],
                        'email'              => $email,
                        'phone'              => $candidateData['phone'] ?? null,
                        'file_path'          => $newPath,
                        'file_original_name' => $batchFile->original_name,
                        'status'             => 'pending',
                        'professional_area'  => $candidateData['professional_area'] ?? null,
                    ]);
                }

                if (!$email) {
                    $warnings[] = [
                        'index' => $index,
                        'name'  => $candidateData['name'],
                        'reason' => 'E-mail não informado. Candidato salvo sem e-mail.',
                    ];
                }

                if (!empty($candidateData['category_id'])) {
                    $candidate->preferences()->sync([$candidateData['category_id']]);
                }

                AnalyzeCandidateJob::dispatch($candidate->id);

                $batchFile->update(['status' => 'confirmed']);
                $created++;

            } catch (\Exception $e) {
                Log::error('batchConfirm erro ao salvar candidato', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
                $errors++;
                $errorDetails[] = [
                    'index'  => $index,
                    'name'   => $candidateData['name'] ?? '(desconhecido)',
                    'reason' => 'Erro interno ao salvar.',
                ];
            }
        }

        $remainingDone = $batch->files()->where('status', 'done')->count();
        $errorCount    = $batch->files()->where('status', 'error')->count();
        $confirmedCount = $batch->files()->where('status', 'confirmed')->count();

        if ($confirmedCount > 0 && $remainingDone === 0 && $errorCount === 0) {
            $batchStatus = 'confirmed';
        } elseif ($confirmedCount > 0) {
            $batchStatus = 'partially_confirmed';
        } else {
            $batchStatus = 'done';
        }

        $batch->update(['status' => $batchStatus]);

        return response()->json([
            'message'         => "Importação concluída. $created salvos, $errors erros.",
            'created_count'   => $created,
            'error_count'     => $errors,
            'errors'          => $errorDetails,
            'warning_count'   => count($warnings),
            'warnings'        => $warnings,
            'batch_status'    => $batchStatus,
            'remaining_done'  => $remainingDone,
            'batch_errors'    => $errorCount,
        ]);
    }

    /**
     * Lista os batches do usuário autenticado.
     * GET /candidates/batches
     */
    public function batches()
    {
        $batches = BatchUpload::where('user_id', Auth::id())
            ->whereNotIn('status', ['confirmed'])
            ->withCount([
                'files',
                'files as done_count'  => fn($q) => $q->where('status', 'done'),
                'files as error_count' => fn($q) => $q->where('status', 'error'),
            ])
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn($b) => !($b->status === 'done' && $b->done_count === 0))
            ->values()
            ->map(fn($b) => [
                'id'               => $b->id,
                'status'           => $b->status,
                'total_files'      => $b->total_files,
                'processed_files'  => $b->processed_files,
                'failed_files'     => $b->failed_files,
                'done_count'       => $b->done_count,
                'error_count'      => $b->error_count,
                'created_at'       => $b->created_at->toIso8601String(),
            ]);

        return response()->json($batches);
    }

    /**
     * Remove um batch e seus arquivos temporários.
     * DELETE /candidates/batches/{id}
     */
    public function destroyBatch($id)
    {
        $batch = BatchUpload::with('files')->findOrFail($id);

        if ($batch->user_id !== Auth::id()) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        // Remove arquivos temporários ainda não confirmados
        foreach ($batch->files as $file) {
            if ($file->status !== 'confirmed' && Storage::disk('public')->exists($file->temp_path)) {
                Storage::disk('public')->delete($file->temp_path);
            }
        }

        $batch->delete();

        return response()->json(['message' => 'Importação removida.']);
    }

    /**
     * Recebe dados do construtor de currículo, gera PDF, salva no banco e analisa com IA.
     * POST /resume-builder (público)
     */
    public function submitFromBuilder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'personal'           => 'required|array',
            'personal.name'      => 'required|string|max:255',
            'personal.email'     => 'required|email|max:255',
            'personal.phone'     => 'nullable|string|max:20',
            'personal.city'      => 'nullable|string|max:100',
            'personal.linkedin'  => 'nullable|string|max:255',
            'personal.objective' => 'nullable|string',
            'experiences'        => 'nullable|array',
            'education'          => 'nullable|array',
            'courses'            => 'nullable|array',
            'skills'             => 'nullable|array',
            'languages'          => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dados inválidos.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $data       = $request->all();
            $name       = $request->input('personal.name');
            $email      = $request->input('personal.email');
            $resumeText = $this->buildTextFromFormData($data);

            // ── Gerar PDF com Dompdf ──
            $html     = $this->buildResumeHTML($data);
            $pdf      = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
            $filename = 'curriculo-' . Str::slug($name) . '-' . time() . '.pdf';
            $filePath = 'resumes/builder/' . $filename;

            Storage::disk('public')->put($filePath, $pdf->output());

            // ── Upsert candidato ──
            $candidate = Candidate::where('email', $email)->first();

            if ($candidate) {
                $candidate->update([
                    'name'               => $name,
                    'phone'              => $request->input('personal.phone'),
                    'city'               => $request->input('personal.city'),
                    'file_path'          => $filePath,
                    'file_original_name' => $filename,
                    'status'             => 'pending',
                ]);
            } else {
                $candidate = Candidate::create([
                    'name'               => $name,
                    'email'              => $email,
                    'phone'              => $request->input('personal.phone'),
                    'city'               => $request->input('personal.city'),
                    'file_path'          => $filePath,
                    'file_original_name' => $filename,
                    'status'             => 'pending',
                ]);
            }

            // ── Análise IA ──
            $analysis = $this->openAIService->analyzeCandidateResume($resumeText);

            if ($analysis) {
                $candidate->update([
                    'city'                   => $analysis['city'] ?? $candidate->city,
                    'professional_area'      => $analysis['professional_area'],
                    'qualifications_summary' => $analysis['qualifications_summary'],
                    'status'                 => 'analyzed',
                ]);
            } else {
                $candidate->update(['status' => 'error']);
            }

            return response()->json([
                'success'  => true,
                'filename' => $filename,
            ], 201);

        } catch (\Exception $e) {
            Log::error('submitFromBuilder error: ' . $e->getMessage());
            return response()->json(['message' => 'Erro ao processar o currículo.'], 500);
        }
    }

    /**
     * Download público de PDF gerado pelo construtor de currículo.
     */
    public function downloadBuilderResume(string $filename)
    {
        // Sanitiza o nome do arquivo para evitar path traversal
        $filename = basename($filename);
        $filePath = 'resumes/builder/' . $filename;

        if (!Storage::disk('public')->exists($filePath)) {
            return response()->json(['message' => 'Arquivo não encontrado.'], 404);
        }

        $fullPath = Storage::disk('public')->path($filePath);

        return response()->download($fullPath, $filename, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Gera HTML do currículo para renderização com Dompdf.
     */
    private function buildResumeHTML(array $data): string
    {
        $p   = $data['personal']    ?? [];
        $exp = $data['experiences'] ?? [];
        $edu = $data['education']   ?? [];
        $crs = $data['courses']     ?? [];
        $skl = $data['skills']      ?? [];
        $lng = $data['languages']   ?? [];

        $e = fn($v) => htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');

        // Contatos
        $contacts = array_filter([
            $e($p['email']    ?? ''),
            $e($p['phone']    ?? ''),
            $e($p['city']     ?? ''),
            $e($p['linkedin'] ?? ''),
        ]);
        $contactLine = implode(' &nbsp;&middot;&nbsp; ', $contacts);

        // Seção
        $section = function (string $title, string $content): string {
            return "<div class=\"section\">
                <div class=\"section-title\">{$title}</div>
                {$content}
            </div>";
        };

        // Experiências
        $expHTML = '';
        foreach ($exp as $item) {
            $end    = !empty($item['current']) ? 'Atual' : ($item['end_date'] ?? '');
            $parts  = array_filter([$item['start_date'] ?? '', $end]);
            $period = implode(' – ', $parts);
            $desc   = !empty($item['description'])
                ? '<p class="desc">' . $e($item['description']) . '</p>'
                : '';
            $expHTML .= "<div class=\"exp-item\">
                <table width=\"100%\"><tr>
                    <td><strong>{$e($item['role'] ?? '')}</strong>" .
                    ($item['company'] ? " &nbsp;&middot;&nbsp; {$e($item['company'])}" : '') .
                    "</td>
                    <td align=\"right\" class=\"period\">{$e($period)}</td>
                </tr></table>
                {$desc}
            </div>";
        }

        // Formação
        $eduHTML = '';
        foreach ($edu as $item) {
            $end    = !empty($item['studying']) ? 'Cursando' : ($item['end_year'] ?? '');
            $parts  = array_filter([$item['start_year'] ?? '', $end]);
            $period = implode(' – ', $parts);
            $level  = !empty($item['level']) ? " <span class=\"muted\">({$e($item['level'])})</span>" : '';
            $eduHTML .= "<div class=\"edu-item\">
                <table width=\"100%\"><tr>
                    <td><strong>{$e($item['course'] ?? '')}</strong>" .
                    ($item['institution'] ? " &nbsp;&middot;&nbsp; {$e($item['institution'])}" : '') .
                    "{$level}</td>
                    <td align=\"right\" class=\"period\">{$e($period)}</td>
                </tr></table>
            </div>";
        }

        // Cursos
        $crsHTML = '';
        foreach ($crs as $item) {
            $year    = !empty($item['year']) ? " <span class=\"muted\">({$e($item['year'])})</span>" : '';
            $inst    = !empty($item['institution']) ? " &nbsp;&middot;&nbsp; {$e($item['institution'])}" : '';
            $crsHTML .= "<div class=\"course-item\">{$e($item['name'] ?? '')}{$inst}{$year}</div>";
        }

        // Habilidades
        $sklHTML = '';
        foreach ($skl as $skill) {
            $sklHTML .= "<span class=\"tag\">{$e($skill)}</span> ";
        }

        // Idiomas
        $lngHTML = '';
        foreach ($lng as $item) {
            $level   = !empty($item['level']) ? " – {$e($item['level'])}" : '';
            $lngHTML .= "<span class=\"lang\"><strong>{$e($item['language'] ?? '')}</strong>{$level}</span> ";
        }

        return '<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: Arial, Helvetica, sans-serif; font-size:12px; color:#1f2937; padding:20mm 15mm; }
  .header { border-bottom:2px solid #003366; padding-bottom:10px; margin-bottom:14px; }
  .header h1 { font-size:20px; font-weight:bold; color:#003366; margin-bottom:3px; }
  .header .contacts { color:#6b7280; font-size:10px; }
  .section { margin-bottom:13px; }
  .section-title { font-size:8px; font-weight:bold; text-transform:uppercase; letter-spacing:2px; color:#003366; border-bottom:1px solid #e5e7eb; padding-bottom:3px; margin-bottom:7px; }
  .exp-item { margin-bottom:8px; }
  .edu-item { margin-bottom:6px; }
  .course-item { margin-bottom:3px; font-size:11px; }
  .desc { color:#4b5563; font-size:11px; margin-top:2px; }
  .period { color:#9ca3af; font-size:10px; white-space:nowrap; }
  .muted { color:#9ca3af; }
  .tag { background:#dbeafe; color:#003366; font-size:10px; padding:2px 7px; border-radius:10px; display:inline-block; margin:1px; }
  .lang { margin-right:14px; font-size:11px; }
  .objective { color:#374151; font-size:11px; line-height:1.5; }
</style>
</head>
<body>
  <div class="header">
    <h1>' . $e($p['name'] ?? '') . '</h1>
    <div class="contacts">' . $contactLine . '</div>
  </div>
  ' . (!empty($p['objective']) ? $section('Objetivo', '<p class="objective">' . $e($p['objective']) . '</p>') : '') . '
  ' . ($expHTML ? $section('Experiências Profissionais', $expHTML) : '') . '
  ' . ($eduHTML ? $section('Formação Acadêmica', $eduHTML) : '') . '
  ' . ($crsHTML ? $section('Cursos e Certificações', $crsHTML) : '') . '
  ' . ($sklHTML ? $section('Habilidades', '<div style="margin-top:3px">' . $sklHTML . '</div>') : '') . '
  ' . ($lngHTML ? $section('Idiomas', '<div style="margin-top:3px">' . $lngHTML . '</div>') : '') . '
</body>
</html>';
    }

    /**
     * Monta texto plano do currículo a partir dos dados do formulário.
     */
    private function buildTextFromFormData(array $data): string
    {
        $p   = $data['personal']    ?? [];
        $exp = $data['experiences'] ?? [];
        $edu = $data['education']   ?? [];
        $crs = $data['courses']     ?? [];
        $skl = $data['skills']      ?? [];
        $lng = $data['languages']   ?? [];

        $lines = [];

        $lines[] = '=== DADOS PESSOAIS ===';
        $lines[] = 'Nome: '  . ($p['name']  ?? '');
        $lines[] = 'Email: ' . ($p['email'] ?? '');
        if (!empty($p['phone']))    $lines[] = 'Telefone: ' . $p['phone'];
        if (!empty($p['city']))     $lines[] = 'Cidade: '   . $p['city'];
        if (!empty($p['linkedin'])) $lines[] = 'LinkedIn: ' . $p['linkedin'];

        if (!empty($p['objective'])) {
            $lines[] = '';
            $lines[] = '=== OBJETIVO / RESUMO PROFISSIONAL ===';
            $lines[] = $p['objective'];
        }

        if (!empty($exp)) {
            $lines[] = '';
            $lines[] = '=== EXPERIÊNCIAS PROFISSIONAIS ===';
            foreach ($exp as $e) {
                $end    = !empty($e['current']) ? 'Atual' : ($e['end_date'] ?? '');
                $period = ($e['start_date'] ?? '') . ($end ? ' - ' . $end : '');
                $lines[] = ($e['role'] ?? '') . ' | ' . ($e['company'] ?? '') . ($period ? ' | ' . $period : '');
                if (!empty($e['description'])) $lines[] = $e['description'];
                $lines[] = '';
            }
        }

        if (!empty($edu)) {
            $lines[] = '=== FORMAÇÃO ACADÊMICA ===';
            foreach ($edu as $ed) {
                $end    = !empty($ed['studying']) ? 'Cursando' : ($ed['end_year'] ?? '');
                $period = ($ed['start_year'] ?? '') . ($end ? ' - ' . $end : '');
                $lines[] = ($ed['level'] ?? '') . ' em ' . ($ed['course'] ?? '') . ' | ' . ($ed['institution'] ?? '') . ($period ? ' | ' . $period : '');
            }
            $lines[] = '';
        }

        if (!empty($crs)) {
            $lines[] = '=== CURSOS E CERTIFICAÇÕES ===';
            foreach ($crs as $c) {
                $year    = !empty($c['year']) ? ' (' . $c['year'] . ')' : '';
                $lines[] = ($c['name'] ?? '') . (!empty($c['institution']) ? ' | ' . $c['institution'] : '') . $year;
            }
            $lines[] = '';
        }

        if (!empty($skl)) {
            $lines[] = '=== HABILIDADES ===';
            $lines[] = implode(', ', $skl);
            $lines[] = '';
        }

        if (!empty($lng)) {
            $lines[] = '=== IDIOMAS ===';
            foreach ($lng as $l) {
                $lines[] = ($l['language'] ?? '') . ': ' . ($l['level'] ?? '');
            }
        }

        return implode("\n", $lines);
    }
}
