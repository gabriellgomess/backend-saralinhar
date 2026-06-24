<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentResponse extends Model
{
    protected $fillable = [
        'assessment_application_id',
        'assessment_question_id',
        'assessment_option_id',
        'numeric_answer',
        'text_answer',
        'ranking_json',
        'sjt_pair_json',
        'response_time_seconds',
    ];

    protected $casts = [
        'numeric_answer'        => 'integer',
        'response_time_seconds' => 'integer',
        'ranking_json'          => 'array',
        'sjt_pair_json'         => 'array',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(AssessmentApplication::class, 'assessment_application_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(AssessmentQuestion::class, 'assessment_question_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(AssessmentOption::class, 'assessment_option_id');
    }
}
