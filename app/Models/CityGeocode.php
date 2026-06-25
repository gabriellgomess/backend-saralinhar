<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CityGeocode extends Model
{
    protected $fillable = [
        'city',
        'latitude',
        'longitude',
    ];
}
