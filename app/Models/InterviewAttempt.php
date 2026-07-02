<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'question',
        'answer',
        'score',
        'feedback',
        'source'
    ];

    protected $casts = [
        'feedback' => 'array',
        'score' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
