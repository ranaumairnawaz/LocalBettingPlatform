<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MethodAccount extends Model
{
    protected $fillable = ['method_id', 'receiving_limit', 'is_active'];

    public function method()
    {
        return $this->belongsTo(DepositMethod::class, 'method_id');
    }

    public function values()
    {
        return $this->hasMany(AccountValue::class, 'account_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'method_account_id');
    }
}
