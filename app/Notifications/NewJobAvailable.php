<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class NewJobAvailable extends Notification
{
    use Queueable;

    protected $job;

    /**
     * Create a new notification instance.
     */
    public function __construct($job)
    {
        $this->job = $job;
    }

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
            ->title("Nova vaga: {$this->job->title}")
            ->icon('/pwa-192x192.png')
            ->body("A empresa {$this->job->company} postou uma nova vaga na sua área de interesse.")
            ->action('Ver Vaga', 'view_job')
            ->data(['url' => "/app/vagas"]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'job_id' => $this->job->id,
            'title' => $this->job->title,
            'company' => $this->job->company,
            'category_id' => $this->job->category_id,
            'message' => "Nova vaga de {$this->job->title} disponível!",
            'type' => 'new_job_match'
        ];
    }
}
