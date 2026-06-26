<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleCalendarController extends Controller
{
    protected GoogleCalendarService $googleCalendarService;

    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
    }

    /**
     * Verifica o status da conexão da conta do Google.
     * GET /api/google-calendar/status
     */
    public function status(Request $request)
    {
        $googleAccount = $request->user()->googleAccount;

        if (!$googleAccount) {
            return response()->json([
                'connected' => false,
            ]);
        }

        return response()->json([
            'connected' => true,
            'email' => $googleAccount->google_email,
            'has_refresh_token' => !empty($googleAccount->refresh_token),
            'expires_at' => $googleAccount->token_expires_at,
            'webhook_subscribed' => !empty($googleAccount->google_channel_id),
            'webhook_expiration' => $googleAccount->google_channel_expiration,
        ]);
    }

    /**
     * Retorna a lista de eventos do calendário do usuário logado.
     * GET /api/google-calendar/events
     */
    public function events(Request $request)
    {
        $googleAccount = $request->user()->googleAccount;

        if (!$googleAccount) {
            return response()->json([
                'message' => 'Google account not connected.'
            ], 400);
        }

        $timeMin = $request->query('timeMin');
        $timeMax = $request->query('timeMax');

        $events = $this->googleCalendarService->listEvents($googleAccount, $timeMin, $timeMax);

        // Formata os eventos de uma forma simples e limpa para o frontend
        $formattedEvents = collect($events)->map(function ($event) {
            return [
                'id' => $event['id'] ?? '',
                'summary' => $event['summary'] ?? '(Sem título)',
                'description' => $event['description'] ?? '',
                'start' => $event['start']['dateTime'] ?? $event['start']['date'] ?? null,
                'end' => $event['end']['dateTime'] ?? $event['end']['date'] ?? null,
                'is_all_day' => isset($event['start']['date']),
                'location' => $event['location'] ?? '',
                'html_link' => $event['htmlLink'] ?? '',
                'hangout_link' => $event['hangoutLink'] ?? null, // Google Meet Link
            ];
        });

        return response()->json($formattedEvents);
    }

    /**
     * Remove a conexão com a conta Google do usuário logado.
     * DELETE /api/google-calendar/disconnect
     */
    public function disconnect(Request $request)
    {
        $googleAccount = $request->user()->googleAccount;

        if ($googleAccount) {
            // Tenta parar o webhook watch do Google antes de apagar
            try {
                $this->googleCalendarService->stopWatching($googleAccount);
            } catch (\Exception $e) {
                Log::warning('Failed to stop watching Google Calendar during disconnect', [
                    'account_id' => $googleAccount->id,
                    'error' => $e->getMessage()
                ]);
            }

            $googleAccount->delete();
        }

        return response()->json([
            'message' => 'Google account disconnected successfully.'
        ]);
    }

    /**
     * Configura manualmente o watch de eventos para escutar Webhooks.
     * POST /api/google-calendar/watch
     */
    public function watch(Request $request)
    {
        $googleAccount = $request->user()->googleAccount;

        if (!$googleAccount) {
            return response()->json(['message' => 'Google account not connected.'], 400);
        }

        $appUrl = config('app.url') ?? env('APP_URL');
        if (str_contains($appUrl, 'localhost')) {
            return response()->json([
                'message' => 'Webhooks cannot be configured from localhost. A public HTTPS URL is required.'
            ], 400);
        }

        $webhookUrl = rtrim($appUrl, '/') . '/api/webhooks/google-calendar';
        $watchData = $this->googleCalendarService->watchEvents($googleAccount, $webhookUrl);

        if (!$watchData) {
            return response()->json(['message' => 'Failed to create watch channel.'], 500);
        }

        return response()->json([
            'message' => 'Calendar watch channel created successfully.',
            'channel_id' => $googleAccount->google_channel_id,
            'expiration' => $googleAccount->google_channel_expiration,
        ]);
    }
}
