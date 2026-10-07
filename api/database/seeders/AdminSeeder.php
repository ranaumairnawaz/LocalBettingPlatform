<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'username' => 'admin',
            'email' => 'admin@666egame.com',
            'password' => Hash::make('admin123'),
            'balance' => 0,
            'referral_code' => 'ADMINPRO',
            'is_admin' => true,
        ]);
    }
}
