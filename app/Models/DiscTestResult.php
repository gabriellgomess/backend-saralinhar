<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscTestResult extends Model
{
    protected $fillable = [
        'user_id',
        'candidate_id',
        'disc_test_token_id',
        'testee_name',
        'testee_email',
        'testee_cpf',
        'testee_phone',
        'testee_position',
        'answers',
        'score_d',
        'score_i',
        'score_s',
        'score_c',
        'primary_profile',
        'secondary_profile',
        'ai_analysis',
        'strengths',
        'development_areas',
        'ideal_roles',
        'work_style',
        'status',
    ];

    protected $casts = [
        'answers' => 'array',
        'score_d' => 'integer',
        'score_i' => 'integer',
        'score_s' => 'integer',
        'score_c' => 'integer',
    ];

    protected $appends = [
        'profile_percentages',
        'dominant_profiles',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function discTestToken(): BelongsTo
    {
        return $this->belongsTo(DiscTestToken::class);
    }

    public function getProfilePercentagesAttribute(): array
    {
        $total = $this->score_d + $this->score_i + $this->score_s + $this->score_c;

        if ($total === 0) {
            return [
                'D' => 0,
                'I' => 0,
                'S' => 0,
                'C' => 0,
            ];
        }

        return [
            'D' => round(($this->score_d / $total) * 100, 1),
            'I' => round(($this->score_i / $total) * 100, 1),
            'S' => round(($this->score_s / $total) * 100, 1),
            'C' => round(($this->score_c / $total) * 100, 1),
        ];
    }

    public function getDominantProfilesAttribute(): array
    {
        $scores = [
            'D' => $this->score_d,
            'I' => $this->score_i,
            'S' => $this->score_s,
            'C' => $this->score_c,
        ];

        arsort($scores);

        return array_slice(array_keys($scores), 0, 2);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeAnalyzed($query)
    {
        return $query->where('status', 'analyzed');
    }
}
