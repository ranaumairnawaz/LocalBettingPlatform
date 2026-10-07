<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserWithdrawalAccount extends Model
{
    protected $fillable = ['user_id', 'withdrawal_method_id', 'account_details'];

    protected $casts = [
        'account_details' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function method()
    {
        return $this->belongsTo(WithdrawalMethod::class, 'withdrawal_method_id');
    }
}
