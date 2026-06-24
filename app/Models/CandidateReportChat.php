<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CandidateReportChat extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'candidate_name',
        'job_id',
        'recruitment_client_id',
        'report_type',
        'extracted_data',
        'status',
    ];

    protected $casts = [
        'extracted_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(RecruitmentClient::class, 'recruitment_client_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CandidateReportChatMessage::class, 'chat_id');
    }
}
