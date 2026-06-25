<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialTransaction extends Model
{
    protected $fillable = [
        'client_id',
        'type',
        'description',
        'amount',
        'due_date',
        'payment_date',
        'status',
        'job_id',
        'candidate_id',
        'candidate_salary',
        'commission_percentage',
        'financial_service_id'
    ];

    public function client()
    {
        return $this->belongsTo(RecruitmentClient::class, 'client_id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class, 'candidate_id');
    }

    public function service()
    {
        return $this->belongsTo(FinancialService::class, 'financial_service_id');
    }

    public function recruiterCommissions()
    {
        return $this->hasMany(FinancialRecruiterCommission::class);
    }
}
