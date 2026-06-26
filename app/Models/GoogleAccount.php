<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoogleAccount extends Model
{
    use HasFactory;

    protected $table = 'user_google_accounts';

    protected $fillable = [
        'user_id',
        'google_email',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'google_channel_id',
        'google_resource_id',
        'google_channel_expiration',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'google_channel_expiration' => 'datetime',
        ];
    }

    /**
     * Get the user that owns this Google account.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
