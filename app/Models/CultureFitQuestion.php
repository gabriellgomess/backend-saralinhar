<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CultureFitQuestion extends Model
{
    protected $fillable = [
        'question_number',
        'situation',
        'statement',
        'dimension',
        'scoring_direction',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'question_number' => 'integer',
    ];

    /**
     * Scope para retornar apenas questões ativas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para ordenar por número da questão
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('question_number');
    }

    /**
     * Scope para filtrar por dimensão
     */
    public function scopeByDimension($query, $dimension)
    {
        return $query->where('dimension', $dimension);
    }
}
