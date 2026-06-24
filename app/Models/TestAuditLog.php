<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class TestAuditLog extends Model
{
    public const UPDATED_AT = null;

    public const TYPE_DISC = 'disc';
    public const TYPE_CULTURE_FIT = 'culture_fit';

    public const ACTION_TOKEN_CREATED   = 'token_created';
    public const ACTION_TOKEN_UPDATED   = 'token_updated';
    public const ACTION_TOKEN_CANCELLED = 'token_cancelled';
    public const ACTION_TOKEN_DELETED   = 'token_deleted';
    public const ACTION_TEST_SUBMITTED  = 'test_submitted';
    public const ACTION_RESULT_VIEWED   = 'result_viewed';
    public const ACTION_PDF_DOWNLOADED  = 'pdf_downloaded';
    public const ACTION_RESULT_DELETED  = 'result_deleted';

    protected $fillable = [
        'user_id',
        'recruitment_client_id',
        'test_type',
        'action',
        'subject_type',
        'subject_id',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recruitmentClient(): BelongsTo
    {
        return $this->belongsTo(RecruitmentClient::class, 'recruitment_client_id');
    }

    /**
     * Registra um evento de auditoria. Falhas de log NUNCA propagam exceção
     * para o fluxo principal — auditoria é best-effort.
     *
     * Quando o $userIdOverride é fornecido, é usado em vez de auth()->id()
     * (útil em fluxos públicos como submissão de teste por candidato externo,
     * onde queremos atribuir o evento ao criador do token).
     *
     * O recruitment_client_id é resolvido a partir do user (auth ou override).
     */
    public static function record(
        string $testType,
        string $action,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $metadata = null,
        ?int $userIdOverride = null
    ): ?self {
        try {
            $userId = $userIdOverride ?? auth()->id();
            $recruitmentClientId = null;

            if ($userId) {
                $user = User::find($userId);
                $recruitmentClientId = $user?->recruitment_client_id;
            }

            $request = request();

            return self::create([
                'user_id'               => $userId,
                'recruitment_client_id' => $recruitmentClientId,
                'test_type'             => $testType,
                'action'                => $action,
                'subject_type'          => $subjectType,
                'subject_id'            => $subjectId,
                'metadata'              => $metadata,
                'ip_address'            => $request?->ip(),
                'user_agent'            => $request?->userAgent(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Falha ao registrar TestAuditLog', [
                'test_type' => $testType,
                'action'    => $action,
                'error'     => $e->getMessage(),
            ]);
            return null;
        }
    }
}
