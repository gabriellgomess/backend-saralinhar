<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resume extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'job_title',
        'job_company',
        'candidate_name',
        'candidate_email',
        'candidate_phone',
        'file_path',
        'file_original_name',
        'ai_analysis',
        'adherence_score',
        'strengths',
        'attention_points',
        'professional_area',
        'status',
    ];

    protected $casts = [
        'ai_analysis' => 'array',
        'adherence_score' => 'integer',
    ];

    /**
     * Relacionamento com a vaga
     */
    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}
