<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use App\Models\Job;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ResumeController extends Controller
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Upload de currículo para uma vaga
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'job_id' => 'required|exists:job_listings,id',
            'candidate_name' => 'required|string|max:255',
            'candidate_email' => 'required|email|max:255',
            'candidate_phone' => 'nullable|string|max:20',
            'resume_file' => 'required|file|mimes:pdf,doc,docx,txt|max:5120', // 5MB max
        ]);

        try {
            // Buscar informações da vaga
            $job = Job::findOrFail($validated['job_id']);

            // Upload do arquivo
            $file = $request->file('resume_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('resumes', $filename, 'public');

            // Criar registro do currículo
            $resume = Resume::create([
                'job_id' => $validated['job_id'],
                'job_title' => $job->title,
                'job_company' => $job->company,
                'candidate_name' => $validated['candidate_name'],
                'candidate_email' => $validated['candidate_email'],
                'candidate_phone' => $validated['candidate_phone'] ?? null,
                'file_path' => $filePath,
                'file_original_name' => $file->getClientOriginalName(),
                'status' => 'pending',
            ]);

            // Processar análise de forma assíncrona (em background)
            // Por enquanto vamos fazer síncrono, mas pode ser movido para uma queue
            $this->analyzeResumeInBackground($resume->id);

            return response()->json([
                'message' => 'Currículo enviado com sucesso! Nossa equipe irá analisá-lo em breve.',
                'resume_id' => $resume->id,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Resume Upload Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erro ao enviar currículo. Tente novamente.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Processa análise do currículo em background
     */
    protected function analyzeResumeInBackground(int $resumeId)
    {
        try {
            $resume = Resume::with('job')->findOrFail($resumeId);
            $job = $resume->job;

            if (!$job) {
                Log::warning('Job not found for resume', ['resume_id' => $resumeId]);
                return;
            }

            // Extrai texto do currículo
            $fullPath = storage_path('app/public/' . $resume->file_path);
            $resumeText = $this->openAIService->extractTextFromFile($fullPath);

            if (!$resumeText) {
                $resume->update([
                    'status' => 'error',
                    'ai_analysis' => ['error' => 'Não foi possível extrair texto do arquivo']
                ]);
                return;
            }

            // Analisa com OpenAI
            $jobData = [
                'title' => $job->title,
                'company' => $job->company,
                'type' => $job->type,
                'description' => $job->description,
                'responsibilities' => $job->responsibilities,
                'requirements' => $job->requirements,
            ];

            $analysis = $this->openAIService->analyzeResume($resumeText, $jobData);

            if ($analysis) {
                $resume->update([
                    'status' => 'analyzed',
                    'ai_analysis' => $analysis,
                    'adherence_score' => $analysis['adherence_score'],
                    'strengths' => $analysis['strengths'],
                    'attention_points' => $analysis['attention_points'],
                    'professional_area' => $analysis['professional_area'],
                ]);
            } else {
                $resume->update([
                    'status' => 'error',
                    'ai_analysis' => ['error' => 'Erro na análise da IA']
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Resume Analysis Error', [
                'resume_id' => $resumeId,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Listar currículos de uma vaga (admin)
     */
    public function getByJob(Request $request, int $jobId)
    {
        $job = Job::findOrFail($jobId);

        $resumes = Resume::where('job_id', $jobId)
            ->orderBy('adherence_score', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'job' => $job,
            'resumes' => $resumes,
        ], 200);
    }

    /**
     * Listar todos os currículos (banco de talentos)
     */
    public function index(Request $request)
    {
        $query = Resume::with('job:id,title,company');

        // Filtros
        if ($request->filled('candidate_name')) {
            $query->where('candidate_name', 'like', '%' . $request->candidate_name . '%');
        }

        if ($request->filled('candidate_email')) {
            $query->where('candidate_email', 'like', '%' . $request->candidate_email . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('professional_area')) {
            $query->where('professional_area', $request->professional_area);
        }

        // Ordenação
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $resumes = $query->paginate($request->input('per_page', 20));

        return response()->json($resumes, 200);
    }

    /**
     * Visualizar um currículo específico
     */
    public function show(int $id)
    {
        $resume = Resume::with('job')->findOrFail($id);

        return response()->json($resume, 200);
    }

    /**
     * Download do arquivo de currículo
     */
    public function download(int $id)
    {
        $resume = Resume::findOrFail($id);
        $filePath = storage_path('app/public/' . $resume->file_path);

        if (!file_exists($filePath)) {
            return response()->json([
                'message' => 'Arquivo não encontrado'
            ], 404);
        }

        return response()->download($filePath, $resume->file_original_name);
    }

    /**
     * Deletar um currículo
     */
    public function destroy(int $id)
    {
        $resume = Resume::findOrFail($id);

        // Deletar arquivo
        if (Storage::disk('public')->exists($resume->file_path)) {
            Storage::disk('public')->delete($resume->file_path);
        }

        $resume->delete();

        return response()->json([
            'message' => 'Currículo deletado com sucesso'
        ], 200);
    }

    /**
     * Listar áreas profissionais únicas
     */
    public function professionalAreas()
    {
        $areas = Resume::whereNotNull('professional_area')
            ->where('professional_area', '!=', '')
            ->where('professional_area', '!=', 'Não identificada')
            ->distinct()
            ->pluck('professional_area')
            ->sort()
            ->values();

        return response()->json([
            'data' => $areas
        ], 200);
    }
}
