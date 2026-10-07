<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithdrawalMethod extends Model
{
    protected $fillable = ['name', 'required_fields', 'is_active'];

    protected $casts = [
        'required_fields' => 'array',
        'is_active' => 'boolean',
    ];

    public function accounts()
    {
        return $this->hasMany(UserWithdrawalAccount::class, 'withdrawal_method_id');
    }
}
