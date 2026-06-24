<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchUploadFile extends Model
{
    protected $fillable = [
        'batch_upload_id',
        'original_name',
        'temp_path',
        'status',
        'name',
        'email',
        'phone',
        'professional_area',
        'category_id',
        'error_message',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BatchUpload::class, 'batch_upload_id');
    }
}
