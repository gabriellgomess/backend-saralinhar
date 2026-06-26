<?php

namespace App\Services;

use App\Models\GoogleAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Carbon\Carbon;

class GoogleCalendarService
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $redirectUri;

    public function __construct()
    {
        $this->clientId = config('services.google.client_id') ?? env('GOOGLE_CLIENT_ID', '');
        $this->clientSecret = config('services.google.client_secret') ?? env('GOOGLE_CLIENT_SECRET', '');
        $this->redirectUri = config('services.google.redirect_uri') ?? env('GOOGLE_REDIRECT_URI', '');
    }

    /**
     * Generates the Google OAuth authorization URL.
     */
    public function getAuthUrl(string $state): string
    {
        $query = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', [
                'https://www.googleapis.com/auth/calendar.readonly', // Read-only access to calendar
                'https://www.googleapis.com/auth/userinfo.email'     // Access to user email
            ]),
            'access_type' => 'offline',
            'prompt' => 'consent', // Ensures we get a refresh_token
            'state' => $state
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $query;
    }

    /**
     * Exchanges OAuth authorization code for access and refresh tokens.
     */
    public function exchangeCodeForTokens(string $code): array
    {
        $response = Http::post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
            'code' => $code,
        ]);

        if ($response->failed()) {
            Log::error('Google Calendar OAuth exchange failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('Failed to exchange authorization code for Google tokens.');
        }

        return $response->json();
    }

    /**
     * Fetches the email address associated with the given access token.
     */
    public function getGoogleEmail(string $accessToken): ?string
    {
        $response = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v3/userinfo');

        if ($response->failed()) {
            return null;
        }

        return $response->json('email');
    }

    /**
     * Retrieves a fresh, valid access token for the given GoogleAccount model.
     * Refreshes the token automatically if it is expired or close to expiring.
     */
    public function getFreshAccessToken(GoogleAccount $googleAccount): string
    {
        // If expired or expiring in the next 60 seconds, refresh it
        if ($googleAccount->token_expires_at === null || $googleAccount->token_expires_at->isPast() || $googleAccount->token_expires_at->diffInSeconds(now()) < 60) {
            if (empty($googleAccount->refresh_token)) {
                throw new \Exception('No refresh token available to refresh Google session.');
            }

            $response = Http::post('https://oauth2.googleapis.com/token', [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $googleAccount->refresh_token,
            ]);

            if ($response->failed()) {
                Log::error('Failed to refresh Google access token', [
                    'account_id' => $googleAccount->id,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Failed to refresh Google API access token.');
            }

            $data = $response->json();
            
            $updateData = [
                'access_token' => $data['access_token'],
                'token_expires_at' => now()->addSeconds($data['expires_in']),
            ];

            // Google sometimes returns a new refresh token, update it if present
            if (isset($data['refresh_token'])) {
                $updateData['refresh_token'] = $data['refresh_token'];
            }

            $googleAccount->update($updateData);
        }

        return $googleAccount->access_token;
    }

    /**
     * Lists events from the primary calendar.
     */
    public function listEvents(GoogleAccount $googleAccount, ?string $timeMin = null, ?string $timeMax = null, int $maxResults = 250): array
    {
        try {
            $accessToken = $this->getFreshAccessToken($googleAccount);

            $params = [
                'singleEvents' => 'true',
                'orderBy' => 'startTime',
                'maxResults' => $maxResults,
            ];

            if ($timeMin) {
                $params['timeMin'] = Carbon::parse($timeMin)->toRfc3339String();
            } else {
                // Default to 1 month ago
                $params['timeMin'] = now()->subMonth()->toRfc3339String();
            }

            if ($timeMax) {
                $params['timeMax'] = Carbon::parse($timeMax)->toRfc3339String();
            } else {
                // Default to 3 months ahead
                $params['timeMax'] = now()->addMonths(3)->toRfc3339String();
            }

            $response = Http::withToken($accessToken)->get('https://www.googleapis.com/calendar/v3/calendars/primary/events', $params);

            if ($response->failed()) {
                Log::error('Failed to fetch events from Google Calendar API', [
                    'account_id' => $googleAccount->id,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            return $response->json('items') ?? [];
        } catch (\Exception $e) {
            Log::error('Exception in GoogleCalendarService::listEvents: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Subscribes to calendar notifications (Webhooks).
     */
    public function watchEvents(GoogleAccount $googleAccount, string $webhookUrl): ?array
    {
        try {
            $accessToken = $this->getFreshAccessToken($googleAccount);

            // Channel ID must be unique
            $channelId = 'chan-' . $googleAccount->id . '-' . uniqid();

            $response = Http::withToken($accessToken)->post('https://www.googleapis.com/calendar/v3/calendars/primary/events/watch', [
                'id' => $channelId,
                'type' => 'web_hook',
                'address' => $webhookUrl,
            ]);

            if ($response->failed()) {
                Log::error('Failed to subscribe to Google Calendar watch channel', [
                    'account_id' => $googleAccount->id,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

            $data = $response->json();

            // Store channel subscription details
            $googleAccount->update([
                'google_channel_id' => $channelId,
                'google_resource_id' => $data['resourceId'] ?? null,
                'google_channel_expiration' => isset($data['expiration']) 
                    ? Carbon::createFromTimestampMs($data['expiration']) 
                    : now()->addDays(7), // Google usually expires watch channels in a week
            ]);

            return $data;
        } catch (\Exception $e) {
            Log::error('Exception in GoogleCalendarService::watchEvents: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Stops a calendar watch subscription.
     */
    public function stopWatching(GoogleAccount $googleAccount): bool
    {
        if (empty($googleAccount->google_channel_id) || empty($googleAccount->google_resource_id)) {
            return false;
        }

        try {
            $accessToken = $this->getFreshAccessToken($googleAccount);

            $response = Http::withToken($accessToken)->post('https://www.googleapis.com/calendar/v3/channels/stop', [
                'id' => $googleAccount->google_channel_id,
                'resourceId' => $googleAccount->google_resource_id,
            ]);

            if ($response->failed() && $response->status() !== 404) {
                Log::error('Failed to stop Google Calendar watch channel', [
                    'account_id' => $googleAccount->id,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }

            $googleAccount->update([
                'google_channel_id' => null,
                'google_resource_id' => null,
                'google_channel_expiration' => null,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Exception in GoogleCalendarService::stopWatching: ' . $e->getMessage());
            return false;
        }
    }
}
