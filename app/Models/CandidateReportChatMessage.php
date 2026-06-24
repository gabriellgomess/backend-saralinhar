<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateReportChatMessage extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'chat_id',
        'sender',
        'message_type',
        'content',
        'audio_path',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(CandidateReportChat::class, 'chat_id');
    }
}
