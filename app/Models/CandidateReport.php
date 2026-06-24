<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_type',
        'job_id',
        'recruitment_client_id',
        'candidate_name',
        'interviewer_name',
        'interview_date',
        'summary',
        'technical_skills',
        'behavioral_posture',
        'strengths',
        'development_points',
        'final_opinion',
        'status',
        'audio_path',
        'audio_expires_at',
        'transcription',
        'complementary_prompt',
        'player_data',
        'sara_data',
        'regeneration_count',
        'last_regenerated_at',
    ];

    protected $casts = [
        'interview_date' => 'datetime',
        'audio_expires_at' => 'datetime',
        'last_regenerated_at' => 'datetime',
        'technical_skills' => 'array',
        'strengths' => 'array',
        'development_points' => 'array',
        'player_data' => 'array',
        'sara_data' => 'array',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function client()
    {
        return $this->belongsTo(RecruitmentClient::class, 'recruitment_client_id');
    }
}
