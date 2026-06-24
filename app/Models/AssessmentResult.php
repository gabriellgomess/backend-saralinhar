<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentResult extends Model
{
    protected $fillable = [
        'assessment_application_id',
        'overall_score',
        'dimension_scores',
        'quality_index',
        'flags',
        'report',
        'ai_narrative',
        'calculated_at',
    ];

    protected $casts = [
        'overall_score'    => 'float',
        'dimension_scores' => 'array',
        'quality_index'    => 'integer',
        'flags'            => 'array',
        'report'           => 'array',
        'calculated_at'    => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(AssessmentApplication::class, 'assessment_application_id');
    }

    /**
     * Classificação textual do score geral (linguagem de mapeamento comportamental).
     */
    public function getClassificationAttribute(): string
    {
        return match (true) {
            $this->overall_score >= 80 => 'Forte evidência comportamental percebida',
            $this->overall_score >= 60 => 'Evidência adequada',
            $this->overall_score >= 40 => 'Em desenvolvimento',
            default                    => 'Baixa evidência comportamental percebida',
        };
    }

    /**
     * Retorna dimensões ordenadas por score (maior primeiro).
     */
    public function getRankedDimensionsAttribute(): array
    {
        $scores = $this->dimension_scores ?? [];
        uasort($scores, fn($a, $b) => $b['score'] <=> $a['score']);
        return $scores;
    }
}
