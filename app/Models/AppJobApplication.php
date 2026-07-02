<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppJobApplication extends Model
{
    public const STATUSES = [
        'curriculo_enviado',
        'entrevista_marcada',
        'aguardando_retorno',
        'aprovado',
        'reprovado',
        'banco_talentos',
    ];

    protected $fillable = [
        'user_id',
        'company',
        'role',
        'applied_at',
        'status',
    ];

    protected $casts = [
        'applied_at' => 'date:Y-m-d',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
