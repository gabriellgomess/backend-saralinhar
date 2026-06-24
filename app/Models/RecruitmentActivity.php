<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\RecruitmentClient;

class RecruitmentActivity extends Model
{
    protected $fillable = [
        'client_id',
        'job_title',
        'opening_date',
        'sla_deadline',
        'feedback_sent_date',
        'hiring_date',
        'candidate_name',
        'candidate_contact',
        'salary',
        'commission_percentage',
        'commission_value',
        'payment_date',
        'feedback_30_days_date',
        'replacement_45_days',
        'observations',
    ];

    protected $casts = [
        'opening_date' => 'date',
        'sla_deadline' => 'date',
        'feedback_sent_date' => 'date',
        'hiring_date' => 'date',
        'payment_date' => 'date',
        'feedback_30_days_date' => 'date',
        'replacement_45_days' => 'boolean',
        'salary' => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'commission_value' => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(RecruitmentClient::class, 'client_id');
    }
}
