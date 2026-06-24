<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;

    protected $table = 'job_listings';

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'company',
        'address',
        'description',
        'responsibilities',
        'requirements',
        'workload',
        'salary',
        'benefits',
        'type',
        'email',
        'phone',
        'is_active',
        'is_confidential',
        'approval_status',
        'rejection_reason',
        'original_email',
        'original_phone',
        'source',
        'reference_id',
    ];

    /**
     * Retorna o email de contato baseado no tipo de usuário
     */
    public function getContactEmailAttribute()
    {
        if (auth()->check() && in_array(auth()->user()->role, ['admin', 'operational'])) {
            return $this->original_email ?: $this->email;
        }

        return 'recrutamento@saralinhar.com.br';
    }

    /**
     * Retorna o telefone de contato baseado no tipo de usuário
     */
    public function getContactPhoneAttribute()
    {
        if (auth()->check() && in_array(auth()->user()->role, ['admin', 'operational'])) {
            return $this->original_phone ?: $this->phone;
        }

        return null;
    }

    /**
     * Retorna o nome da empresa apropriado
     * Se is_confidential for true e não for admin/operational, retorna "Confidencial"
     */
    public function getDisplayCompanyAttribute()
    {
        if (auth()->check() && in_array(auth()->user()->role, ['admin', 'operational'])) {
            return $this->company;
        }

        if ($this->is_confidential) {
            return 'Confidencial';
        }

        return $this->company;
    }

    protected $casts = [
        'salary' => 'decimal:2',
        'is_active' => 'boolean',
        'is_confidential' => 'boolean',
    ];

    protected $appends = ['display_company'];

    /**
     * Relacionamento com o usuário que criou a vaga
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com a categoria da vaga
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relacionamento com currículos enviados (ANTIGO - será deprecado)
     */
    public function resumes()
    {
        return $this->hasMany(Resume::class);
    }

    /**
     * Relacionamento com candidaturas
     */
    public function candidateApplications()
    {
        return $this->hasMany(CandidateJobApplication::class);
    }

    /**
     * Relacionamento com candidatos (através da tabela pivô)
     */
    public function candidates()
    {
        return $this->belongsToMany(Candidate::class, 'candidate_job_applications')
            ->withPivot(['adherence_score', 'strengths', 'attention_points', 'ai_analysis', 'status'])
            ->withTimestamps();
    }
}
