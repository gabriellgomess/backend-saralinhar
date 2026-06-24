<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CandidateReport;
use App\Models\CandidateReportChat;
use App\Models\CandidateReportChatMessage;
use App\Models\Job;
use App\Models\RecruitmentClient;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ConversationalReportController extends Controller
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Inicia uma nova sessão de chat conversacional
     */
    public function startChat(Request $request)
    {
        $validated = $request->validate([
            'candidate_name' => 'nullable|string|max:255',
            'job_id' => 'nullable|exists:job_listings,id',
            'recruitment_client_id' => 'nullable|exists:recruitment_clients,id',
        ]);

        $candidateName = $validated['candidate_name'] ?? 'Candidato';
        $jobId = $validated['job_id'] ?? null;
        $clientId = $validated['recruitment_client_id'] ?? null;
        $reportType = 'sara';

        // Detecção automática de layout (Player ou Sara)
        if ($jobId) {
            $job = Job::find($jobId);
            if ($job && $job->recruitment_client_id) {
                $clientId = $job->recruitment_client_id;
            }
        }

        if ($clientId) {
            $client = RecruitmentClient::find($clientId);
            if ($client && $client->is_player) {
                $reportType = 'player';
            }
        }

        // Busca o template JSON vazio correspondente
        $initialData = $reportType === 'player'
            ? $this->openAIService->processConversationalAgent($reportType, [], [], '')['updated_data'] ?? []
            : $this->openAIService->processConversationalAgent($reportType, [], [], '')['updated_data'] ?? [];

        // Fallbacks se a IA falhar
        if (empty($initialData)) {
            $initialData = $reportType === 'player'
                ? [
                    'age' => '', 'children' => '', 'marital_status' => '',
                    'address' => '', 'commute' => '', 'education' => '',
                    'salary_expectation' => '', 'professional_experience' => '', 'work_history' => [],
                    'strengths_text' => '', 'development_text' => '', 'professional_challenge' => '',
                    'final_summary' => '', 'fit_percentage' => 50, 'fit_label' => 'Aderência Moderada', 'final_conclusion' => ''
                  ]
                : [
                    'summary' => '', 'technical_skills' => [], 'behavioral_posture' => '',
                    'strengths' => [], 'development_points' => [], 'final_opinion' => '',
                    'status' => 'recommended_with_reservations',
                    'sara_data' => [
                        'personal_info' => [
                            'name' => $candidateName, 'age' => '', 'marital_status' => '',
                            'city' => '', 'neighborhood' => '', 'children' => '',
                            'family_base' => '', 'hobbies' => ''
                        ],
                        'experiences' => [], 'experience_extras' => '',
                        'education_development' => [
                            'education' => '', 'courses' => '', 'certifications' => '', 'tools_systems' => ''
                        ],
                        'professional_personal_evaluation' => [
                            'professional_self_description' => '', 'strengths_text' => '', 'overcome_challenge' => '', 'development_text' => '', 'ideal_work_environment' => ''
                        ],
                        'financial' => [
                            'salary_expectation' => '', 'pj_or_clt' => '', 'benefits_expectation' => ''
                        ],
                        'logistics_availability' => [
                            'commute' => '', 'schedule_availability' => '', 'start_availability' => ''
                        ],
                        'interviewer_evaluation' => [
                            'interviewer_communication' => '', 'interviewer_posture' => ''
                        ]
                    ]
                ];
        }

        // Ajusta nome do candidato no JSON inicial se disponível
        if ($reportType === 'sara') {
            $initialData['sara_data']['personal_info']['name'] = $candidateName;
        }

        DB::beginTransaction();
        try {
            $chat = CandidateReportChat::create([
                'user_id' => auth()->id(),
                'candidate_name' => $candidateName,
                'job_id' => $jobId,
                'recruitment_client_id' => $clientId,
                'report_type' => $reportType,
                'extracted_data' => $initialData,
                'status' => 'active',
            ]);

            // Cria mensagem de boas-vindas
            $welcomeText = $reportType === 'player'
                ? "Olá! Sou o assistente virtual da Player. Vou te ajudar a estruturar o parecer de entrevista do(a) *{$candidateName}*. Fique à vontade para me enviar textos ou áudios relatando as suas impressões profissionais sobre ele(a)!"
                : "Olá! Sou a assistente virtual da Sara Linhar DHO. Vamos construir o parecer do(a) *{$candidateName}* de forma simples e interativa. Pode começar me enviando as suas anotações ou o primeiro áudio da entrevista!";

            CandidateReportChatMessage::create([
                'chat_id' => $chat->id,
                'sender' => 'assistant',
                'message_type' => 'text',
                'content' => $welcomeText,
            ]);

            DB::commit();

            return response()->json($chat->load('messages'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao iniciar chat conversacional', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Falha ao iniciar sessão de chat.'], 500);
        }
    }

    /**
     * Lista as sessões de chat do usuário logado
     */
    public function listChats(Request $request)
    {
        $chats = CandidateReportChat::with(['job', 'client'])
            ->where('user_id', auth()->id())
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        return response()->json($chats);
    }

    /**
     * Exibe os detalhes de uma sessão de chat específica
     */
    public function showChat($id)
    {
        $chat = CandidateReportChat::with(['job', 'client', 'messages'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return response()->json($chat);
    }

    /**
     * Envia uma mensagem (texto ou áudio) na sessão de chat
     */
    public function sendMessage(Request $request, $id)
    {
        $chat = CandidateReportChat::where('user_id', auth()->id())
            ->where('status', 'active')
            ->findOrFail($id);

        $request->validate([
            'message_type' => 'required|in:text,audio',
            'content' => 'required_if:message_type,text|string|nullable',
            'audio' => 'required_if:message_type,audio|file|mimes:mp3,wav,m4a,webm,ogg|nullable',
        ]);

        $messageType = $request->input('message_type');
        $content = null;
        $audioPath = null;

        DB::beginTransaction();
        try {
            if ($messageType === 'audio') {
                $file = $request->file('audio');
                $audioPath = $file->store('candidate_audios', 'local');
                $absolutePath = Storage::disk('local')->path($audioPath);

                // Transcreve usando o Whisper
                $content = $this->openAIService->transcribeAudio($absolutePath);
            } else {
                $content = $request->input('content');
            }

            // Salva a mensagem do usuário no banco
            CandidateReportChatMessage::create([
                'chat_id' => $chat->id,
                'sender' => 'user',
                'message_type' => $messageType,
                'content' => $content,
                'audio_path' => $audioPath,
            ]);

            // Busca histórico recente para contexto da IA (últimas 10 mensagens)
            $historyModels = CandidateReportChatMessage::where('chat_id', $chat->id)
                ->orderBy('created_at', 'asc')
                ->take(12)
                ->get();

            $chatHistory = [];
            foreach ($historyModels as $msg) {
                $chatHistory[] = [
                    'sender' => $msg->sender,
                    'content' => $msg->content
                ];
            }

            // Invoca o Agente Conversacional da OpenAI
            $agentResult = $this->openAIService->processConversationalAgent(
                $chat->report_type,
                $chat->extracted_data ?? [],
                $chatHistory,
                $content
            );

            $agentReply = $agentResult['agent_reply'];
            $updatedData = $agentResult['updated_data'];

            // Salva a resposta do assistente no banco
            CandidateReportChatMessage::create([
                'chat_id' => $chat->id,
                'sender' => 'assistant',
                'message_type' => 'text',
                'content' => $agentReply,
            ]);

            // Atualiza os dados acumulados do chat
            $chat->update([
                'extracted_data' => $updatedData,
            ]);

            DB::commit();

            $chat->refresh();
            return response()->json([
                'chat' => $chat->load('messages'),
                'can_finalize' => $agentResult['can_finalize'] ?? false,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            if ($audioPath) {
                Storage::disk('local')->delete($audioPath);
            }
            Log::error('Erro ao processar mensagem no chat conversacional', [
                'chat_id' => $id,
                'message' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Falha ao processar a mensagem: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Finaliza a sessão de chat e compila o parecer oficial
     */
    public function finalizeReport(Request $request, $id)
    {
        $chat = CandidateReportChat::where('user_id', auth()->id())
            ->where('status', 'active')
            ->findOrFail($id);

        $request->validate([
            'interviewer_name' => 'required|string|max:255',
            'interview_date' => 'required|date',
        ]);

        $interviewerName = $request->input('interviewer_name');
        $interviewDate = $request->input('interview_date');
        $extracted = $chat->extracted_data ?? [];

        DB::beginTransaction();
        try {
            // Concatena as transcrições das mensagens do usuário como fonte primária
            $userTranscriptions = CandidateReportChatMessage::where('chat_id', $chat->id)
                ->where('sender', 'user')
                ->pluck('content')
                ->filter()
                ->implode("\n---\n");

            // Coleta caminhos de áudio para salvar se necessário
            $audios = CandidateReportChatMessage::where('chat_id', $chat->id)
                ->where('sender', 'user')
                ->where('message_type', 'audio')
                ->pluck('audio_path')
                ->filter();

            $primaryAudioPath = $audios->first() ?? null;

            // Prepara a criação do CandidateReport oficial
            $reportData = [
                'report_type' => $chat->report_type,
                'job_id' => $chat->job_id,
                'recruitment_client_id' => $chat->recruitment_client_id,
                'candidate_name' => $chat->candidate_name ?? 'Candidato',
                'interviewer_name' => $interviewerName,
                'interview_date' => $interviewDate,
                'transcription' => $userTranscriptions,
                'audio_path' => $primaryAudioPath,
                'audio_expires_at' => $primaryAudioPath ? now()->addWeek() : null,
            ];

            if ($chat->report_type === 'player') {
                $reportData['player_data'] = $extracted;
                
                // Mapeia alguns dados básicos para retrocompatibilidade
                $reportData['summary'] = $extracted['final_summary'] ?? '';
                $reportData['behavioral_posture'] = $extracted['strengths_text'] ?? '';
                $reportData['final_opinion'] = $extracted['final_conclusion'] ?? '';
                $reportData['status'] = ($extracted['fit_percentage'] ?? 50) >= 60 ? 'recommended' : 'recommended_with_reservations';
            } else {
                $reportData['sara_data'] = isset($extracted['sara_data']) ? $extracted['sara_data'] : $extracted;

                // Mapeia campos raiz do JSON Sara para as colunas do banco
                $reportData['summary'] = $extracted['summary'] ?? '';
                $reportData['technical_skills'] = $extracted['technical_skills'] ?? [];
                $reportData['behavioral_posture'] = $extracted['behavioral_posture'] ?? '';
                $reportData['strengths'] = $extracted['strengths'] ?? [];
                $reportData['development_points'] = $extracted['development_points'] ?? [];
                $reportData['final_opinion'] = $extracted['final_opinion'] ?? '';
                $reportData['status'] = $extracted['status'] ?? 'recommended_with_reservations';
            }

            // Salva o parecer oficial definitivo
            $report = CandidateReport::create($reportData);

            // Marca o chat como concluído
            $chat->update(['status' => 'completed']);

            DB::commit();

            return response()->json([
                'message' => 'Parecer gerado com sucesso!',
                'report' => $report,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao finalizar parecer conversacional', [
                'chat_id' => $id,
                'message' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Falha ao finalizar o parecer: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Exclui uma sessão de chat conversacional
     */
    public function destroyChat($id)
    {
        $chat = CandidateReportChat::where('user_id', auth()->id())->findOrFail($id);

        // Exclui arquivos de áudio gravados associados ao chat
        $audios = CandidateReportChatMessage::where('chat_id', $chat->id)
            ->where('message_type', 'audio')
            ->pluck('audio_path')
            ->filter();

        foreach ($audios as $path) {
            Storage::disk('local')->delete($path);
        }

        $chat->delete();

        return response()->json(null, 204);
    }
}
