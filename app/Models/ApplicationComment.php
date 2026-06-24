<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationComment extends Model
{
    protected $fillable = [
        'candidate_job_application_id',
        'user_id',
        'comment',
    ];

    /**
     * Relacionamento com a candidatura
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(CandidateJobApplication::class, 'candidate_job_application_id');
    }

    /**
     * Relacionamento com o usuário autor do comentário
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
