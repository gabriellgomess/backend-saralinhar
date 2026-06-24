<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class AssessmentApplication extends Model
{
    protected $fillable = [
        'assessment_test_id',
        'recruitment_client_id',
        'candidate_id',
        'respondent_name',
        'respondent_email',
        'application_type',
        'status',
        'token',
        'started_at',
        'completed_at',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'metadata'     => 'array',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->token)) {
                $model->token = Str::random(64);
            }
        });
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(AssessmentTest::class, 'assessment_test_id');
    }

    public function recruitmentClient(): BelongsTo
    {
        return $this->belongsTo(RecruitmentClient::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(AssessmentResponse::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(AssessmentResult::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('recruitment_client_id', $clientId);
    }
}
