<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialService extends Model
{
    protected $fillable = ['name', 'description', 'default_value'];
}
