<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CultureFitTestToken extends Model
{
    protected $fillable = [
        'user_id',
        'candidate_id',
        'recruitment_client_id',
        'token',
        'testee_name',
        'testee_email',
        'testee_phone',
        'testee_position',
        'job_title',
        'description',
        'status',
        'expires_at',
        'used_at',
        'culture_fit_result_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function recruitmentClient(): BelongsTo
    {
        return $this->belongsTo(RecruitmentClient::class);
    }

    public function cultureFitResult(): BelongsTo
    {
        return $this->belongsTo(CultureFitResult::class);
    }

    /**
     * Gera um token único
     */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(64);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    /**
     * Verifica se o token está ativo e válido
     */
    public function isValid(): bool
    {
        return $this->status === 'active' &&
            ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Marca o token como usado
     */
    public function markAsUsed(int $cultureFitResultId = null): void
    {
        $this->update([
            'status' => 'used',
            'used_at' => now(),
            'culture_fit_result_id' => $cultureFitResultId,
        ]);
    }

    /**
     * Scope para tokens ativos
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope para tokens válidos (não expirados)
     */
    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope para tokens do usuário
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
