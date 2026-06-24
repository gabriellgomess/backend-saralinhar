<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BatchUpload extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'total_files',
        'processed_files',
        'failed_files',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(BatchUploadFile::class);
    }

    /**
     * Recalcula os contadores e status a partir dos arquivos filhos.
     * Chamado ao final de cada ProcessResumeJob.
     */
    public function recalculate(): void
    {
        $total   = $this->files()->count();
        $done    = $this->files()->whereIn('status', ['done', 'error'])->count();
        $failed  = $this->files()->where('status', 'error')->count();

        $this->update([
            'processed_files' => $done,
            'failed_files'    => $failed,
            'status'          => ($total > 0 && $done >= $total) ? 'done' : 'processing',
        ]);
    }
}
