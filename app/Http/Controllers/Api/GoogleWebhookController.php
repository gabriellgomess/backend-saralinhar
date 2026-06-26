<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GoogleAccount;
use App\Notifications\GoogleCalendarUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleWebhookController extends Controller
{
    /**
     * Trata as notificações de alteração (Push/Webhooks) enviadas pelo Google Calendar.
     * POST /api/webhooks/google-calendar
     */
    public function handle(Request $request)
    {
        $channelId = $request->header('X-Goog-Channel-ID');
        $resourceId = $request->header('X-Goog-Resource-ID');
        $resourceState = $request->header('X-Goog-Resource-State'); // sync, exists, not_exists

        Log::info('Google Calendar Webhook received', [
            'channel_id' => $channelId,
            'resource_id' => $resourceId,
            'state' => $resourceState,
        ]);

        if ($resourceState === 'sync') {
            // Canal criado com sucesso, apenas handshake
            return response()->json(['status' => 'synced']);
        }

        if (empty($channelId) || empty($resourceId)) {
            return response()->json(['error' => 'Missing header identifier parameters'], 400);
        }

        // Buscar a conta do Google correspondente ao canal e recurso
        $googleAccount = GoogleAccount::where('google_channel_id', $channelId)
            ->where('google_resource_id', $resourceId)
            ->first();

        if (!$googleAccount) {
            Log::warning('Google Calendar Webhook channel not matched in database', [
                'channel_id' => $channelId,
                'resource_id' => $resourceId,
            ]);
            // Retorna 200 para que o Google não continue tentando indefinidamente
            return response()->json(['message' => 'Channel not found'], 200);
        }

        $user = $googleAccount->user;

        if ($user) {
            // Disparar notificação para o usuário (banco de dados + PWA push)
            $user->notify(new GoogleCalendarUpdated());
            
            Log::info('Google Calendar webhook processed successfully, notification sent.', [
                'user_id' => $user->id,
                'email' => $googleAccount->google_email
            ]);
        }

        return response()->json(['status' => 'success']);
    }
}
