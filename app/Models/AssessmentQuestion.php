<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentQuestion extends Model
{
    protected $fillable = [
        'assessment_test_id',
        'assessment_dimension_id',
        'code',
        'statement',
        'question_type',
        'scale_min',
        'scale_max',
        'is_reverse',
        'weight',
        'is_attention_check',
        'order',
    ];

    protected $casts = [
        'is_reverse'         => 'boolean',
        'is_attention_check' => 'boolean',
        'scale_min'          => 'integer',
        'scale_max'          => 'integer',
        'weight'             => 'float',
        'order'              => 'integer',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(AssessmentTest::class, 'assessment_test_id');
    }

    public function dimension(): BelongsTo
    {
        return $this->belongsTo(AssessmentDimension::class, 'assessment_dimension_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(AssessmentOption::class)->orderBy('order');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(AssessmentResponse::class);
    }

    /**
     * Aplica o ajuste de item reverso a um valor numérico.
     * Fórmula: resposta_ajustada = (scale_max + 1) - resposta_original
     */
    public function adjustedValue(int $rawValue): float
    {
        if ($this->is_reverse) {
            return ($this->scale_max + 1) - $rawValue;
        }

        return $rawValue;
    }
}
