<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewTip extends Model
{
    protected $fillable = [
        'category',
        'text',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
