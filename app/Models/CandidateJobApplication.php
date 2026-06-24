<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateJobApplication extends Model
{
    protected $fillable = [
        'candidate_id',
        'job_id',
        'adherence_score',
        'strengths',
        'attention_points',
        'ai_analysis',
        'status',
        'pipeline_stage',
        'interview_date',
        'interview_feedback',
        'admin_notes',
        'parecer_file_path',
        'parecer_file_original_name',
        'disc_file_path',
        'disc_file_original_name',
        'culture_fit_file_path',
        'culture_fit_file_original_name',
        'mapeamento_file_path',
        'mapeamento_file_original_name',
    ];

    protected $casts = [
        'adherence_score' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'interview_date' => 'datetime',
    ];

    /**
     * Relacionamento com o candidato
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * Relacionamento com a vaga
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * Relacionamento com os comentários
     */
    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ApplicationComment::class, 'candidate_job_application_id');
    }
}
