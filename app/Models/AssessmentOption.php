<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentOption extends Model
{
    protected $fillable = [
        'assessment_question_id',
        'label',
        'text',
        'score',
        'competency_target',
        'order',
    ];

    protected $casts = [
        'score' => 'integer',
        'order' => 'integer',
    ];

    /**
     * O score nunca é exposto ao frontend — apenas internamente para cálculo.
     */
    protected $hidden = ['score'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(AssessmentQuestion::class, 'assessment_question_id');
    }

    /**
     * Versão com score visível para uso interno (serviços de cálculo).
     */
    public function withScore(): self
    {
        return $this->makeVisible('score');
    }
}
