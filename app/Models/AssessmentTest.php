<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentTest extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'type',
        'version',
        'disclaimer',
        'is_active',
        'config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config'    => 'array',
    ];

    public function dimensions(): HasMany
    {
        return $this->hasMany(AssessmentDimension::class)->orderBy('order');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(AssessmentQuestion::class)->orderBy('order');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(AssessmentApplication::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
