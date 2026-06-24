<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscQuestion extends Model
{
    protected $fillable = [
        'question_number',
        'statement_d',
        'statement_i',
        'statement_s',
        'statement_c',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'question_number' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('question_number');
    }
}
