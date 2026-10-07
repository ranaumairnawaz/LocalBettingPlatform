<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'method',
        'method_account_id',
        'status',
        'reference',
        'proof_image',
        'admin_note',
        'withdrawal_details',
    ];

    protected $casts = [
        'withdrawal_details' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function methodAccount()
    {
        return $this->belongsTo(MethodAccount::class, 'method_account_id');
    }
}
