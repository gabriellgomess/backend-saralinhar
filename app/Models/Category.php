<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }
    public function candidates()
    {
        return $this->belongsToMany(Candidate::class, 'candidate_category');
    }
}
