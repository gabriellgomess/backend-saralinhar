<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class OpenAIUsageLog extends Model
{
    protected $table = 'openai_usage_logs';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'recruitment_client_id',
        'feature',
        'model',
        'endpoint',
        'input_tokens',
        'output_tokens',
        'total_tokens',
        'cached_input_tokens',
        'reasoning_tokens',
        'estimated_cost_usd',
        'duration_ms',
        'status',
        'http_status',
        'error_message',
        'subject_type',
        'subject_id',
        'response_id',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'estimated_cost_usd' => 'decimal:6',
        'created_at' => 'datetime',
    ];

    // Features
    const FEATURE_TRANSCRIPTION = 'audio_transcription';
    const FEATURE_CANDIDATE_REPORT = 'candidate_report';
    const FEATURE_PLAYER_REPORT = 'player_report';
    const FEATURE_PLAYER_REPORT_FROM_TRANSCRIPTION = 'player_report_from_transcription';
    const FEATURE_REPORT_VALIDATION = 'report_validation';
    const FEATURE_RESUME_ANALYSIS = 'resume_analysis';
    const FEATURE_JOB_MATCH_ANALYSIS = 'job_match_analysis';
    const FEATURE_PDF_OCR = 'pdf_ocr';
    const FEATURE_DISC_ANALYSIS = 'disc_analysis';
    const FEATURE_CULTURE_FIT_ANALYSIS = 'culture_fit_analysis';
    const FEATURE_CONTACT_EXTRACTION = 'contact_extraction';
    const FEATURE_ASSESSMENT_ANALYSIS = 'assessment_analysis';

    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recruitmentClient()
    {
        return $this->belongsTo(RecruitmentClient::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    /**
     * Registra um uso da API OpenAI (best-effort, nunca lança).
     */
    public static function record(array $data): ?self
    {
        try {
            $user = auth()->user();

            if (!array_key_exists('user_id', $data)) {
                $data['user_id'] = optional($user)->id;
            }
            if (!array_key_exists('recruitment_client_id', $data)) {
                $data['recruitment_client_id'] = optional($user)->recruitment_client_id ?? null;
            }

            if (!isset($data['estimated_cost_usd']) && !empty($data['model'])) {
                $data['estimated_cost_usd'] = static::calculateCost(
                    $data['model'],
                    (int) ($data['input_tokens'] ?? 0),
                    (int) ($data['output_tokens'] ?? 0),
                    (int) ($data['cached_input_tokens'] ?? 0)
                );
            }

            return static::create($data);
        } catch (\Throwable $e) {
            Log::warning('OpenAIUsageLog::record failed', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Calcula custo estimado em USD a partir da tabela de preços em config/services.php.
     * Preços são por 1.000.000 tokens (formato OpenAI).
     */
    public static function calculateCost(string $model, int $inputTokens, int $outputTokens, int $cachedTokens = 0): ?float
    {
        $pricing = config('services.openai.pricing', []);
        if (!isset($pricing[$model])) {
            return null;
        }

        $rates = $pricing[$model];
        $inputRate = (float) ($rates['input'] ?? 0);
        $outputRate = (float) ($rates['output'] ?? 0);
        $cachedRate = (float) ($rates['cached_input'] ?? $inputRate);

        $cachedTokens = max(0, min($cachedTokens, $inputTokens));
        $billableInput = max(0, $inputTokens - $cachedTokens);

        $cost = ($billableInput * $inputRate / 1_000_000)
              + ($cachedTokens * $cachedRate / 1_000_000)
              + ($outputTokens * $outputRate / 1_000_000);

        return round($cost, 6);
    }
}
