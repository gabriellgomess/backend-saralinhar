<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionApiService
{
    protected $apiUrl;
    protected $apiKey;
    protected $instanceName;
    protected $phoneNumber;
    protected $frontendUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.evolution.api_url');
        $this->apiKey = config('services.evolution.api_key');
        $this->instanceName = config('services.evolution.instance_name');
        $this->phoneNumber = config('services.evolution.phone_number');
        $this->frontendUrl = config('services.frontend.url');
    }

    /**
     * Envia uma vaga para o WhatsApp via Evolution API
     */
    public function sendJobToWhatsApp(array $jobData): bool
    {
        try {
            $message = $this->formatJobMessage($jobData);
            $phoneNumber = $this->phoneNumber;

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'apikey' => $this->apiKey,
            ])->timeout(30)->post("{$this->apiUrl}/message/sendText/{$this->instanceName}", [
                'number' => $phoneNumber,
                'text' => $message,
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('Vaga enviada para WhatsApp com sucesso', [
                    'job_id' => $jobData['id'] ?? 'N/A',
                    'job_title' => $jobData['title'] ?? 'N/A',
                    'phone_number' => $phoneNumber,
                    'message_id' => $responseData['key']['id'] ?? 'N/A',
                    'status' => $responseData['status'] ?? 'N/A',
                ]);
                return true;
            }

            Log::error('Erro ao enviar vaga para WhatsApp', [
                'status' => $response->status(),
                'body' => $response->body(),
                'job_id' => $jobData['id'] ?? 'N/A',
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Exceção ao enviar vaga para WhatsApp', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'job_id' => $jobData['id'] ?? 'N/A',
            ]);

            return false;
        }
    }

    /**
     * Formata a mensagem da vaga para o WhatsApp
     */
    protected function formatJobMessage(array $jobData): string
    {
        $message = "🚀 *NOVA VAGA DISPONÍVEL*\n\n";

        $message .= "📋 *Título:* " . ($jobData['title'] ?? 'N/A') . "\n";

        // Usa display_company se disponível, senão usa company diretamente
        $companyName = $jobData['display_company'] ?? $jobData['company'] ?? 'N/A';
        $message .= "🏢 *Empresa:* " . $companyName . "\n";

        $message .= "📍 *Local:* " . ($jobData['address'] ?? 'N/A') . "\n";
        $message .= "⏰ *Carga Horária:* " . ($jobData['workload'] ?? 'N/A') . "\n";
        $message .= "💼 *Tipo:* " . ucfirst($jobData['type'] ?? 'N/A') . "\n";

        if (!empty($jobData['salary'])) {
            $message .= "💰 *Salário:* R$ " . number_format($jobData['salary'], 2, ',', '.') . "\n";
        }

        $message .= "\n📝 *Descrição:*\n" . ($jobData['description'] ?? 'N/A') . "\n";

        if (!empty($jobData['responsibilities'])) {
            $message .= "\n🎯 *Responsabilidades:*\n" . $jobData['responsibilities'] . "\n";
        }

        if (!empty($jobData['requirements'])) {
            $message .= "\n✅ *Requisitos:*\n" . $jobData['requirements'] . "\n";
        }

        if (!empty($jobData['benefits'])) {
            $message .= "\n🎁 *Benefícios:*\n" . $jobData['benefits'] . "\n";
        }

        $message .= "\n📧 *Contato:* " . ($jobData['email'] ?? 'N/A');

        if (!empty($jobData['phone'])) {
            $message .= "\n📞 *Telefone:* " . $jobData['phone'];
        }

        // Adiciona link da vaga
        if (!empty($jobData['id'])) {
            $vagaUrl = rtrim($this->frontendUrl, '/') . '/vaga/' . $jobData['id'];
            $message .= "\n\n🔗 *Ver detalhes completos:*\n" . $vagaUrl;
        }

        $message .= "\n\n_Enviado automaticamente pelo sistema Sara Linhar_";

        return $message;
    }

    /**
     * Verifica se a instância está conectada
     */
    public function checkConnection(): bool
    {
        try {
            $response = Http::withHeaders([
                'apikey' => $this->apiKey,
            ])->timeout(10)->get("{$this->apiUrl}/instance/connectionState/{$this->instanceName}");

            if ($response->successful()) {
                $data = $response->json();
                // Verifica diferentes possíveis estruturas de resposta
                $state = $data['instance']['state'] ?? $data['state'] ?? null;
                return $state === 'open' || $state === 'connected';
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Erro ao verificar conexão Evolution API', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Envia mensagem de teste
     */
    public function sendTestMessage(): bool
    {
        try {
            $message = "🧪 *TESTE DE CONEXÃO*\n\nEsta é uma mensagem de teste do sistema Sara Linhar.\n\n_Enviado em: " . now()->format('d/m/Y H:i:s') . "_";

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'apikey' => $this->apiKey,
            ])->timeout(30)->post("{$this->apiUrl}/message/sendText/{$this->instanceName}", [
                'number' => $this->phoneNumber,
                'text' => $message,
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('Mensagem de teste enviada com sucesso', [
                    'message_id' => $responseData['key']['id'] ?? 'N/A',
                    'status' => $responseData['status'] ?? 'N/A',
                ]);
                return true;
            }

            Log::error('Erro ao enviar mensagem de teste', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Exceção ao enviar mensagem de teste', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
