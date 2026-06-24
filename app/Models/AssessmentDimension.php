<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentDimension extends Model
{
    protected $fillable = [
        'assessment_test_id',
        'slug',
        'name',
        'description',
        'weight',
        'order',
    ];

    protected $casts = [
        'weight' => 'float',
        'order'  => 'integer',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(AssessmentTest::class, 'assessment_test_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(AssessmentQuestion::class)->orderBy('order');
    }
}
