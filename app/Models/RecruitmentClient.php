<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecruitmentClient extends Model
{
    protected $fillable = [
        'name',
        'is_player',
        'commission_percentage',
        'type',
        'cnpj_cpf',
        'contact_name',
        'secondary_contact_name',
        'phone',
        'secondary_phone',
        'email',
        'website',
        'address',
        'city',
        'state',
        'zip_code',
        'logo_path',
        'notes',
    ];

    protected $casts = [
        'is_player' => 'boolean',
    ];

    public function activities()
    {
        return $this->hasMany(RecruitmentActivity::class, 'client_id');
    }
}
