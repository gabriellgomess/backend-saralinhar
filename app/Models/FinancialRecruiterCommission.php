<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialRecruiterCommission extends Model
{
    protected $table = 'financial_recruiter_commissions';

    protected $fillable = [
        'financial_transaction_id',
        'user_id',
        'amount',
        'percentage',
        'status',
        'payment_date'
    ];

    public function transaction()
    {
        return $this->belongsTo(FinancialTransaction::class, 'financial_transaction_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
