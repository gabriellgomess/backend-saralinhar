<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    protected GoogleCalendarService $googleCalendarService;

    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
    }

    /**
     * Retorna a URL de autenticação do Google para o frontend redirecionar.
     * GET /api/auth/google/url
     */
    public function getAuthUrl(Request $request)
    {
        $user = $request->user();

        // Encriptar ID do usuário e timestamp no state para segurança e persistência
        $state = Crypt::encryptString(json_encode([
            'user_id' => $user->id,
            'timestamp' => now()->timestamp,
        ]));

        $url = $this->googleCalendarService->getAuthUrl($state);

        return response()->json([
            'url' => $url
        ]);
    }

    /**
     * Intercepta o callback do Google, troca o código por tokens e salva.
     * GET /api/auth/google/callback
     */
    public function handleGoogleCallback(Request $request)
    {
        $code = $request->query('code');
        $stateParam = $request->query('state');
        $error = $request->query('error');

        $frontendUrl = config('services.frontend.url') ?? env('FRONTEND_URL', 'http://localhost:5173');

        if ($error || !$code || !$stateParam) {
            Log::warning('Google OAuth callback returned error or missing params', [
                'error' => $error,
                'has_code' => !empty($code),
                'has_state' => !empty($stateParam),
            ]);
            return redirect($frontendUrl . '/dashboard/agenda?status=error&message=' . urlencode($error ?? 'missing_params'));
        }

        try {
            // Decifrar o payload do state
            $stateData = json_decode(Crypt::decryptString($stateParam), true);
            $userId = $stateData['user_id'] ?? null;

            if (!$userId) {
                throw new \Exception('Invalid state: User ID not found.');
            }

            // Trocar o código de autorização pelos tokens de acesso e refresh
            $tokens = $this->googleCalendarService->exchangeCodeForTokens($code);
            $googleEmail = $this->googleCalendarService->getGoogleEmail($tokens['access_token']);

            $user = User::findOrFail($userId);

            // Salva na tabela user_google_accounts
            $googleAccount = $user->googleAccount()->updateOrCreate(
                [], // chaves de busca (hasOne já vincula pelo user_id automaticamente)
                [
                    'google_email' => $googleEmail,
                    'access_token' => $tokens['access_token'],
                    // Google envia o refresh token apenas quando o consentimento é solicitado
                    'refresh_token' => $tokens['refresh_token'] ?? null,
                    'token_expires_at' => now()->addSeconds($tokens['expires_in']),
                ]
            );

            // Se o Google não enviou o refresh token nesta requisição mas já tínhamos um guardado, mantém o anterior
            if (!isset($tokens['refresh_token']) && $googleAccount->wasRecentlyCreated === false) {
                // Manter o refresh token existente
            }

            Log::info('Google Account connected successfully', ['user_id' => $userId, 'email' => $googleEmail]);

            // Se a URL pública do backend estiver configurada, podemos configurar o Webhook para este calendário
            $appUrl = config('app.url') ?? env('APP_URL');
            if (str_contains($appUrl, 'localhost') === false) {
                $webhookUrl = rtrim($appUrl, '/') . '/api/webhooks/google-calendar';
                $this->googleCalendarService->watchEvents($googleAccount, $webhookUrl);
            }

            return redirect($frontendUrl . '/dashboard/agenda?status=success');

        } catch (\Exception $e) {
            Log::error('Error handling Google OAuth callback: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return redirect($frontendUrl . '/dashboard/agenda?status=error&message=' . urlencode($e->getMessage()));
        }
    }
}
