<?php

namespace Tests\Feature;

use App\Models\CandidateReport;
use App\Models\CandidateReportChat;
use App\Models\User;
use App\Services\OpenAIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationalReportTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $openAIServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Cria usuário de teste com perfil de admin para contornar block_roles:client se necessário
        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);

        // Mocka a integração com a OpenAI
        $this->openAIServiceMock = $this->createMock(OpenAIService::class);
        $this->app->instance(OpenAIService::class, $this->openAIServiceMock);
    }

    /**
     * Teste de inicialização de chat
     */
    public function test_can_start_conversational_chat(): void
    {
        $this->openAIServiceMock->method('processConversationalAgent')->willReturn([
            'updated_data' => ['test_field' => 'value'],
            'agent_reply' => 'Olá recrutador',
            'can_finalize' => false
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/conversational-reports/chats', [
                'candidate_name' => 'Carlos Santos',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('candidate_name', 'Carlos Santos');
        $response->assertJsonPath('status', 'active');
        $response->assertJsonPath('report_type', 'sara'); // default

        $this->assertDatabaseHas('candidate_report_chats', [
            'candidate_name' => 'Carlos Santos',
            'status' => 'active',
        ]);
    }

    /**
     * Teste de envio de mensagem de texto no chat
     */
    public function test_can_send_text_message_to_chat(): void
    {
        $this->openAIServiceMock->method('processConversationalAgent')->willReturn([
            'updated_data' => ['test_field' => 'updated_value'],
            'agent_reply' => 'Obrigado pelo dado',
            'can_finalize' => false
        ]);

        // Cria chat ativo prévio
        $chat = CandidateReportChat::create([
            'user_id' => $this->user->id,
            'candidate_name' => 'Ana Paula',
            'report_type' => 'sara',
            'extracted_data' => ['test_field' => 'initial'],
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/conversational-reports/chats/{$chat->id}/messages", [
                'message_type' => 'text',
                'content' => 'Ana tem 25 anos',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('chat.extracted_data.test_field', 'updated_value');

        $this->assertDatabaseHas('candidate_report_chat_messages', [
            'chat_id' => $chat->id,
            'sender' => 'user',
            'content' => 'Ana tem 25 anos',
        ]);

        $this->assertDatabaseHas('candidate_report_chat_messages', [
            'chat_id' => $chat->id,
            'sender' => 'assistant',
            'content' => 'Obrigado pelo dado',
        ]);
    }

    /**
     * Teste de finalização de chat e compilação do relatório
     */
    public function test_can_finalize_chat_and_generate_candidate_report(): void
    {
        // Cria chat ativo prévio com dados extraídos
        $chat = CandidateReportChat::create([
            'user_id' => $this->user->id,
            'candidate_name' => 'Felipe Melo',
            'report_type' => 'sara',
            'extracted_data' => [
                'summary' => 'Resumo do Felipe',
                'status' => 'recommended',
                'sara_data' => [
                    'personal_info' => ['name' => 'Felipe Melo']
                ]
            ],
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/conversational-reports/chats/{$chat->id}/finalize", [
                'interviewer_name' => 'Sara Linhar',
                'interview_date' => '2026-05-27',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Parecer gerado com sucesso!');

        // Verifica se a sessão do chat foi marcada como completed
        $this->assertDatabaseHas('candidate_report_chats', [
            'id' => $chat->id,
            'status' => 'completed',
        ]);

        // Verifica se o CandidateReport definitivo foi criado com as colunas mapeadas
        $this->assertDatabaseHas('candidate_reports', [
            'candidate_name' => 'Felipe Melo',
            'interviewer_name' => 'Sara Linhar',
            'report_type' => 'sara',
            'summary' => 'Resumo do Felipe',
            'status' => 'recommended',
        ]);
    }
}
