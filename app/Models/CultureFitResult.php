<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CultureFitResult extends Model
{
    protected $fillable = [
        'user_id',
        'candidate_id',
        'testee_name',
        'testee_email',
        'testee_cpf',
        'testee_phone',
        'testee_position',
        'answers',
        'score_autonomy',
        'score_innovation',
        'score_hierarchy',
        'score_teamwork',
        'score_results',
        'score_flexibility',
        'ai_analysis',
        'cultural_profile',
        'strengths',
        'challenges',
        'ideal_environments',
        'recommendations',
        'status',
    ];

    protected $casts = [
        'answers' => 'array',
        'score_autonomy' => 'integer',
        'score_innovation' => 'integer',
        'score_hierarchy' => 'integer',
        'score_teamwork' => 'integer',
        'score_results' => 'integer',
        'score_flexibility' => 'integer',
    ];

    protected $appends = [
        'dimension_percentages',
        'dominant_dimensions',
    ];

    /**
     * Relacionamento com User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cultureFitTestToken(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CultureFitTestToken::class, 'culture_fit_result_id');
    }

    /**
     * Atributo computado: percentuais de cada dimensão
     */
    public function getDimensionPercentagesAttribute(): array
    {
        $total = $this->score_autonomy + $this->score_innovation +
                 $this->score_hierarchy + $this->score_teamwork +
                 $this->score_results + $this->score_flexibility;

        if ($total === 0) {
            return [
                'autonomy' => 0,
                'innovation' => 0,
                'hierarchy' => 0,
                'teamwork' => 0,
                'results' => 0,
                'flexibility' => 0,
            ];
        }

        return [
            'autonomy' => round(($this->score_autonomy / $total) * 100, 1),
            'innovation' => round(($this->score_innovation / $total) * 100, 1),
            'hierarchy' => round(($this->score_hierarchy / $total) * 100, 1),
            'teamwork' => round(($this->score_teamwork / $total) * 100, 1),
            'results' => round(($this->score_results / $total) * 100, 1),
            'flexibility' => round(($this->score_flexibility / $total) * 100, 1),
        ];
    }

    /**
     * Atributo computado: dimensões dominantes (top 2)
     */
    public function getDominantDimensionsAttribute(): array
    {
        $scores = [
            'autonomy' => $this->score_autonomy,
            'innovation' => $this->score_innovation,
            'hierarchy' => $this->score_hierarchy,
            'teamwork' => $this->score_teamwork,
            'results' => $this->score_results,
            'flexibility' => $this->score_flexibility,
        ];

        arsort($scores);
        return array_slice(array_keys($scores), 0, 2);
    }

    /**
     * Scope para filtrar por status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para retornar apenas testes completos/analisados
     */
    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['completed', 'analyzed']);
    }
}
