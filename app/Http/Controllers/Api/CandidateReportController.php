<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CandidateReport;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class CandidateReportController extends Controller
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    public function index(Request $request)
    {
        $query = CandidateReport::with(['job', 'client'])
            ->where('report_type', 'sara');

        // Filtro por nome do candidato
        if ($request->filled('candidate_name')) {
            $query->where('candidate_name', 'like', '%' . $request->candidate_name . '%');
        }

        // Filtro por vaga
        if ($request->filled('job_id')) {
            $query->where('job_id', $request->job_id);
        }

        // Filtro por cliente
        if ($request->filled('recruitment_client_id')) {
            $query->where('recruitment_client_id', $request->recruitment_client_id);
        }

        // Filtro por entrevistador
        if ($request->filled('interviewer_name')) {
            $query->where('interviewer_name', 'like', '%' . $request->interviewer_name . '%');
        }

        // Filtro por range de data
        if ($request->filled('date_from')) {
            $query->whereDate('interview_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('interview_date', '<=', $request->date_to);
        }

        $reports = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($reports);
    }

    public function interviewers()
    {
        $interviewers = CandidateReport::where('report_type', 'sara')
            ->whereNotNull('interviewer_name')
            ->where('interviewer_name', '!=', '')
            ->distinct()
            ->orderBy('interviewer_name')
            ->pluck('interviewer_name');

        return response()->json($interviewers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'candidate_name' => 'required|string',
            'interviewer_name' => 'required|string',
            'interview_date' => 'required|date',
            'job_id' => 'nullable|exists:job_listings,id',
            'recruitment_client_id' => 'nullable|exists:recruitment_clients,id',
            'status' => 'nullable|in:recommended,recommended_with_reservations,not_recommended',
            'transcription' => 'nullable|string',
            'complementary_prompt' => 'nullable|string',
            'audio_path' => 'nullable|string',
        ]);

        $data = $request->all();
        $data['report_type'] = 'sara';

        // Se vier com audio_path, define expiração de 1 semana
        if (!empty($data['audio_path'])) {
            $data['audio_expires_at'] = now()->addWeek();
        }

        $report = CandidateReport::create($data);

        return response()->json($report, 201);
    }

    public function show($id)
    {
        $report = CandidateReport::with(['job', 'client'])
            ->where('report_type', 'sara')
            ->findOrFail($id);
        return response()->json($report);
    }

    public function update(Request $request, $id)
    {
        $report = CandidateReport::where('report_type', 'sara')->findOrFail($id);
        $report->update($request->all());
        return response()->json($report);
    }

    public function destroy($id)
    {
        $report = CandidateReport::where('report_type', 'sara')->findOrFail($id);
        if ($report->audio_path) {
            Storage::disk('local')->delete($report->audio_path);
        }
        $report->delete();
        return response()->json(null, 204);
    }

    public function downloadPdf($id)
    {
        $report = CandidateReport::with(['job', 'client'])
            ->where('report_type', 'sara')
            ->findOrFail($id);

        $pdf = Pdf::loadView('reports.candidate', compact('report'));

        return $pdf->download("parecer_{$report->candidate_name}.pdf");
    }

    /**
     * @deprecated Mantido apenas para servir PDFs Player a partir do cache antigo de pareceres tipo 'sara'.
     * Para novos pareceres Player, use /api/player-reports.
     */
    public function downloadPlayerPdf($id)
    {
        set_time_limit(120);

        $report = CandidateReport::with(['job', 'client'])
            ->where('report_type', 'sara')
            ->findOrFail($id);

        // Se não tem player_data em cache, gera via IA
        if (empty($report->player_data)) {
            $reportData = [
                'candidate_name' => $report->candidate_name,
                'interviewer_name' => $report->interviewer_name,
                'interview_date' => $report->interview_date ? $report->interview_date->format('d/m/Y') : '',
                'job_title' => $report->job ? $report->job->title : 'N/A',
                'client_name' => $report->client ? $report->client->name : 'N/A',
                'summary' => $report->summary,
                'behavioral_posture' => $report->behavioral_posture,
                'technical_skills' => $report->technical_skills,
                'strengths' => $report->strengths,
                'development_points' => $report->development_points,
                'final_opinion' => $report->final_opinion,
                'status' => $report->status,
            ];

            $rawTranscription = $report->transcription ?: null;
            $complementaryPrompt = $report->complementary_prompt ?: null;

            $playerData = $this->openAIService->structurePlayerReport($reportData, $rawTranscription, $complementaryPrompt);

            // Validar consistência se houver transcrição disponível
            if ($rawTranscription) {
                $validation = $this->openAIService->validateReportConsistency($rawTranscription, $playerData);
                if ($validation['has_issues']) {
                    Log::warning('Inconsistências no parecer Player gerado', [
                        'candidate' => $report->candidate_name,
                        'issues' => $validation['issues'],
                    ]);
                }
            }

            $report->update(['player_data' => $playerData]);
            $report->refresh();
        }

        $playerData = $report->player_data;

        $pdf = Pdf::loadView('reports.candidate_player', compact('report', 'playerData'));
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download("parecer_player_{$report->candidate_name}.pdf");
    }

    /**
     * Re-executa a análise de IA para um parecer Sara existente.
     * Usa a transcrição salva ou re-transcreve o áudio (se ainda disponível).
     */
    public function regenerate($id)
    {
        set_time_limit(300);

        $report = CandidateReport::with(['job', 'client'])
            ->where('report_type', 'sara')
            ->findOrFail($id);

        // Tenta obter a transcrição
        $transcription = $report->transcription;

        if (empty($transcription) && $report->audio_path) {
            // Verifica se o áudio ainda existe e não expirou
            $audioNotExpired = !$report->audio_expires_at || $report->audio_expires_at->isFuture();
            $audioExists = Storage::disk('local')->exists($report->audio_path);

            if ($audioNotExpired && $audioExists) {
                try {
                    $absolutePath = Storage::disk('local')->path($report->audio_path);
                    $transcription = $this->openAIService->transcribeAudio($absolutePath);
                } catch (\Exception $e) {
                    Log::error('Falha ao re-transcrever áudio no regenerate', [
                        'report_id' => $id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }

        if (empty($transcription)) {
            return response()->json([
                'error' => 'Nenhuma transcrição disponível para re-executar a análise. O áudio pode ter expirado ou não ter sido salvo.'
            ], 422);
        }

        $complementaryPrompt = $report->complementary_prompt ?: null;

        // Re-executa agente Sara
        $saraData = $this->openAIService->structureCandidateReport($transcription, null, $complementaryPrompt);

        $report->update([
            'summary' => $saraData['summary'] ?? $report->summary,
            'technical_skills' => $saraData['technical_skills'] ?? $report->technical_skills,
            'behavioral_posture' => $saraData['behavioral_posture'] ?? $report->behavioral_posture,
            'strengths' => $saraData['strengths'] ?? $report->strengths,
            'development_points' => $saraData['development_points'] ?? $report->development_points,
            'final_opinion' => $saraData['final_opinion'] ?? $report->final_opinion,
            'status' => $saraData['status'] ?? $report->status,
            'sara_data' => $saraData['sara_data'] ?? $report->sara_data,
            'transcription' => $transcription,
            'regeneration_count' => $report->regeneration_count + 1,
            'last_regenerated_at' => now(),
        ]);

        $report->refresh();

        return response()->json($report);
    }

    /**
     * Faz streaming do áudio salvo para auditoria do recrutador.
     * Retorna 404 se o áudio expirou ou não existe.
     */
    public function streamAudio($id)
    {
        $report = CandidateReport::where('report_type', 'sara')->findOrFail($id);

        if (empty($report->audio_path)) {
            return response()->json(['error' => 'Nenhum áudio salvo para este parecer.'], 404);
        }

        $audioExpired = $report->audio_expires_at && $report->audio_expires_at->isPast();
        if ($audioExpired) {
            return response()->json(['error' => 'O áudio deste parecer expirou.'], 410);
        }

        if (!Storage::disk('local')->exists($report->audio_path)) {
            return response()->json(['error' => 'Arquivo de áudio não encontrado.'], 404);
        }

        $extension = pathinfo($report->audio_path, PATHINFO_EXTENSION);
        $mimeMap = [
            'webm' => 'audio/webm',
            'mp3'  => 'audio/mpeg',
            'wav'  => 'audio/wav',
            'm4a'  => 'audio/mp4',
            'ogg'  => 'audio/ogg',
        ];
        $mime = $mimeMap[$extension] ?? 'audio/webm';

        return Storage::disk('local')->response($report->audio_path, "gravacao_{$report->candidate_name}.{$extension}", [
            'Content-Type' => $mime,
        ]);
    }

    public function processAudio(Request $request)
    {
        set_time_limit(300); // 5 minutes execution time

        $request->validate([
            'audio' => 'required|file|mimes:mp3,wav,m4a,webm,ogg',
            'resume' => 'nullable|file|mimes:pdf,docx,doc,txt|max:10240',
            'complementary_prompt' => 'nullable|string',
        ]);

        $file = $request->file('audio');

        // Salva o áudio permanentemente (expira em 1 semana)
        $permanentPath = $file->store('candidate_audios', 'local');

        try {
            $absolutePath = Storage::disk('local')->path($permanentPath);

            if (!file_exists($absolutePath)) {
                throw new \Exception("Arquivo de áudio não encontrado no caminho: " . $absolutePath);
            }

            // 1. Transcreve o áudio
            $transcription = $this->openAIService->transcribeAudio($absolutePath);

            // 2. Extrai texto do currículo se fornecido
            $resumeText = null;
            if ($request->hasFile('resume')) {
                $resumeFile = $request->file('resume');
                $resumePath = $resumeFile->store('temp_resumes', 'local');

                try {
                    $fullResumePath = Storage::disk('local')->path($resumePath);

                    if (file_exists($fullResumePath)) {
                        $resumeText = $this->openAIService->extractTextFromFile($fullResumePath);
                    } else {
                        Log::error("Resume file not found at path: " . $fullResumePath);
                    }

                    Storage::disk('local')->delete($resumePath);
                } catch (\Exception $e) {
                    Log::error("Failed to extract resume text: " . $e->getMessage());
                    if (isset($resumePath)) {
                        Storage::disk('local')->delete($resumePath);
                    }
                }
            }

            // 3. Estrutura o parecer com ambas as entradas + prompt complementar
            $complementaryPrompt = $request->input('complementary_prompt');
            $structuredData = $this->openAIService->structureCandidateReport($transcription, $resumeText, $complementaryPrompt ?: null);

            return response()->json([
                'transcription' => $transcription,
                'structured_data' => $structuredData,
                'audio_path' => $permanentPath,
            ]);

        } catch (\Exception $e) {
            // Se falhar, apaga o áudio salvo para não ocupar espaço
            Storage::disk('local')->delete($permanentPath);
            return response()->json(['error' => 'Failed to process audio: ' . $e->getMessage()], 500);
        }
    }
}
