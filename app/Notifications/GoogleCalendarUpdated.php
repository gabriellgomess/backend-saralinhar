<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class GoogleCalendarUpdated extends Notification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title("Agenda Google Atualizada")
            ->icon('/pwa-192x192.png')
            ->body("Seus compromissos do Google Agenda foram sincronizados com o painel.")
            ->action('Ver Agenda', 'view_agenda')
            ->data(['url' => "/dashboard/agenda"]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Sua agenda do Google foi sincronizada recentemente com novos compromissos.',
            'type' => 'google_calendar_updated',
            'url' => '/dashboard/agenda'
        ];
    }
}
