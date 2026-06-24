<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * GET /api/notifications/recent
     * Endpoint leve para polling — retorna contagem de não lidas + últimas 5.
     */
    public function recent(Request $request): JsonResponse
    {
        $user = Auth::user();

        $items = $user->notifications()
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($n) => [
                'id'         => $n->id,
                'data'       => $n->data,
                'read_at'    => $n->read_at,
                'created_at' => $n->created_at,
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'unread' => $user->unreadNotifications()->count(),
                'items'  => $items,
            ],
        ]);
    }

    /**
     * List user notifications
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        return response()->json([
            'notifications' => $user->notifications()->limit(20)->get(),
            'unread_count' => $user->unreadNotifications()->count()
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        $user = Auth::user();
        
        $notification = $user->notifications()->where('id', $id)->first();
        
        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['message' => 'Marcada como lida']);
    }

    /**
     * Mark all as read
     */
    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();
        
        return response()->json(['message' => 'Todas marcadas como lidas']);
    }

    /**
     * Subscribe to push notifications
     */
    public function subscribe(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'endpoint' => 'required',
            'keys.auth' => 'required',
            'keys.p256dh' => 'required',
        ]);

        $user->updatePushSubscription(
            $request->endpoint,
            $request->keys['p256dh'],
            $request->keys['auth']
        );

        return response()->json(['message' => 'Inscrição realizada com sucesso']);
    }
}
