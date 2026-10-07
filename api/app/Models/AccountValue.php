<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountValue extends Model
{
    protected $fillable = ['account_id', 'key', 'value', 'type'];

    public function account()
    {
        return $this->belongsTo(MethodAccount::class, 'account_id');
    }
}
