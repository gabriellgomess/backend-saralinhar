<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'city',
        'professional_area',
        'qualifications_summary',
        'file_path',
        'file_original_name',
        'status',
        // Campos do app EntrevistaPro AI
        'user_id',
        'desired_role',
        'work_mode',
        'education',
        'skills',
        'salary_expectation',
        'summary',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento com as candidaturas do candidato
     */
    public function jobApplications(): HasMany
    {
        return $this->hasMany(CandidateJobApplication::class);
    }

    /**
     * Relacionamento com as vagas que o candidato se candidatou (através da tabela pivô)
     */
    public function jobs()
    {
        return $this->belongsToMany(Job::class, 'candidate_job_applications')
            ->withPivot(['adherence_score', 'strengths', 'attention_points', 'ai_analysis', 'status'])
            ->withTimestamps();
    }
    /**
     * Relacionamento com as categorias de interesse (preferências)
     */
    public function preferences()
    {
        return $this->belongsToMany(Category::class, 'candidate_category');
    }
}
