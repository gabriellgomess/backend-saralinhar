<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CandidateReport;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class PlayerReportController extends Controller
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    public function index(Request $request)
    {
        $query = CandidateReport::with(['job', 'client'])
            ->where('report_type', 'player');

        if ($request->filled('candidate_name')) {
            $query->where('candidate_name', 'like', '%' . $request->candidate_name . '%');
        }
        if ($request->filled('job_id')) {
            $query->where('job_id', $request->job_id);
        }
        if ($request->filled('recruitment_client_id')) {
            $query->where('recruitment_client_id', $request->recruitment_client_id);
        }
        if ($request->filled('interviewer_name')) {
            $query->where('interviewer_name', 'like', '%' . $request->interviewer_name . '%');
        }
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
        $interviewers = CandidateReport::where('report_type', 'player')
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
            'transcription' => 'nullable|string',
            'complementary_prompt' => 'nullable|string',
            'audio_path' => 'nullable|string',
            'player_data' => 'nullable|array',
        ]);

        $data = $request->all();
        $data['report_type'] = 'player';

        if (!empty($data['audio_path'])) {
            $data['audio_expires_at'] = now()->addWeek();
        }

        $report = CandidateReport::create($data);

        return response()->json($report, 201);
    }

    public function show($id)
    {
        $report = CandidateReport::with(['job', 'client'])
            ->where('report_type', 'player')
            ->findOrFail($id);
        return response()->json($report);
    }

    public function update(Request $request, $id)
    {
        $report = CandidateReport::where('report_type', 'player')->findOrFail($id);
        $report->update($request->all());
        return response()->json($report);
    }

    public function destroy($id)
    {
        $report = CandidateReport::where('report_type', 'player')->findOrFail($id);
        if ($report->audio_path) {
            Storage::disk('local')->delete($report->audio_path);
        }
        $report->delete();
        return response()->json(null, 204);
    }

    public function downloadPdf($id)
    {
        $report = CandidateReport::with(['job', 'client'])
            ->where('report_type', 'player')
            ->findOrFail($id);

        if (empty($report->player_data)) {
            return response()->json([
                'error' => 'Este parecer Player não tem dados estruturados. Use a função regenerar para gerar a partir da transcrição.'
            ], 422);
        }

        $playerData = $report->player_data;

        $pdf = Pdf::loadView('reports.candidate_player', compact('report', 'playerData'));
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download("parecer_player_{$report->candidate_name}.pdf");
    }

    /**
     * Re-executa a análise de IA para um parecer Player existente, gerando direto da transcrição.
     */
    public function regenerate($id)
    {
        set_time_limit(300);

        $report = CandidateReport::with(['job', 'client'])
            ->where('report_type', 'player')
            ->findOrFail($id);

        $transcription = $report->transcription;

        if (empty($transcription) && $report->audio_path) {
            $audioNotExpired = !$report->audio_expires_at || $report->audio_expires_at->isFuture();
            $audioExists = Storage::disk('local')->exists($report->audio_path);

            if ($audioNotExpired && $audioExists) {
                try {
                    $absolutePath = Storage::disk('local')->path($report->audio_path);
                    $transcription = $this->openAIService->transcribeAudio($absolutePath);
                } catch (\Exception $e) {
                    Log::error('Falha ao re-transcrever áudio no regenerate Player', [
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

        $context = [
            'candidate_name'   => $report->candidate_name,
            'interviewer_name' => $report->interviewer_name,
            'interview_date'   => $report->interview_date ? $report->interview_date->format('d/m/Y') : '',
            'job_title'        => $report->job ? $report->job->title : 'N/A',
            'client_name'      => $report->client ? $report->client->name : 'N/A',
        ];

        $playerData = $this->openAIService->structurePlayerReportFromTranscription(
            $transcription,
            null,
            $complementaryPrompt,
            $context
        );

        $report->update([
            'transcription' => $transcription,
            'player_data' => $playerData,
            'regeneration_count' => $report->regeneration_count + 1,
            'last_regenerated_at' => now(),
        ]);

        $report->refresh();

        return response()->json($report);
    }

    public function streamAudio($id)
    {
        $report = CandidateReport::where('report_type', 'player')->findOrFail($id);

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

    /**
     * Processa áudio (+ currículo opcional) e gera diretamente o parecer Player estruturado.
     */
    public function processAudio(Request $request)
    {
        set_time_limit(300);

        // Diagnóstico: se o upload falhou no nível do PHP, registra o motivo exato
        // antes da validação devolver o genérico "The audio failed to upload.".
        $rawAudio = $request->file('audio');
        if (!$rawAudio || !$rawAudio->isValid()) {
            Log::warning('Falha no upload do áudio do parecer (player)', [
                'has_file'    => (bool) $rawAudio,
                'error_code'  => $rawAudio?->getError(),   // 1/2=tamanho, 3=parcial, 6=sem dir temp, 7=falha gravação
                'size'        => $rawAudio?->getSize(),
                'client_mime' => $rawAudio?->getClientMimeType(),
                'post_max'    => ini_get('post_max_size'),
                'upload_max'  => ini_get('upload_max_filesize'),
                'tmp_dir'     => ini_get('upload_tmp_dir') ?: sys_get_temp_dir(),
            ]);
        }

        $request->validate([
            'audio' => 'required|file|mimes:mp3,wav,m4a,webm,ogg',
            'resume' => 'nullable|file|mimes:pdf,docx,doc,txt|max:10240',
            'complementary_prompt' => 'nullable|string',
            'candidate_name' => 'nullable|string',
            'interviewer_name' => 'nullable|string',
            'interview_date' => 'nullable|string',
            'job_title' => 'nullable|string',
            'client_name' => 'nullable|string',
        ]);

        $file = $request->file('audio');
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
                    Log::error("Failed to extract resume text (Player): " . $e->getMessage());
                    if (isset($resumePath)) {
                        Storage::disk('local')->delete($resumePath);
                    }
                }
            }

            // 3. Estrutura o parecer Player direto da transcrição
            $context = [
                'candidate_name'   => $request->input('candidate_name', 'N/A'),
                'interviewer_name' => $request->input('interviewer_name', 'N/A'),
                'interview_date'   => $request->input('interview_date', 'N/A'),
                'job_title'        => $request->input('job_title', 'N/A'),
                'client_name'      => $request->input('client_name', 'N/A'),
            ];

            $complementaryPrompt = $request->input('complementary_prompt');
            $playerData = $this->openAIService->structurePlayerReportFromTranscription(
                $transcription,
                $resumeText,
                $complementaryPrompt ?: null,
                $context
            );

            return response()->json([
                'transcription' => $transcription,
                'player_data' => $playerData,
                'audio_path' => $permanentPath,
            ]);

        } catch (\Exception $e) {
            Storage::disk('local')->delete($permanentPath);
            return response()->json(['error' => 'Failed to process audio: ' . $e->getMessage()], 500);
        }
    }
}
