<?php

namespace App\Notifications;

use App\Models\AssessmentApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AssessmentCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly AssessmentApplication $application,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $result     = $this->application->result;
        $testName   = $this->application->test?->name ?? 'Instrumento comportamental';
        $respondent = $this->application->respondent_name ?? 'Respondente';

        return [
            'type'           => 'assessment_completed',
            'title'          => 'Mapeamento respondido',
            'body'           => "{$respondent} concluiu o instrumento \"{$testName}\".",
            'score'          => $result?->overall_score,
            'classification' => $result?->classification,
            'application_id' => $this->application->id,
            'test_name'      => $testName,
            'respondent'     => $respondent,
            'url'            => "/dashboard/testes/mapeamentos/{$this->application->id}/resultado",
        ];
    }
}
