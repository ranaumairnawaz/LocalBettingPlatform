<?php

namespace Database\Seeders;

use App\Models\Game;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        $games = [
            [
                'name' => 'Ludo Pro',
                'slug' => 'ludo',
                'type' => 'skill',
                'min_bet' => 50,
                'max_bet' => 5000,
                'commission_rate' => 15, // 15% cut
                'is_featured' => true,
            ],
            [
                'name' => 'Chicken Cross',
                'slug' => 'chicken',
                'type' => 'skill',
                'min_bet' => 20,
                'max_bet' => 2000,
                'commission_rate' => 12, // 12% cut
                'is_featured' => false,
            ],
            [
                'name' => 'Pool 8',
                'slug' => 'pool',
                'type' => 'skill',
                'min_bet' => 100,
                'max_bet' => 10000,
                'commission_rate' => 10,
                'is_featured' => false,
            ],
            [
                'name' => 'Cricket Pro',
                'slug' => 'cricket',
                'type' => 'skill',
                'min_bet' => 10,
                'max_bet' => 10000,
                'commission_rate' => 10,
                'is_featured' => false,
            ],
            [
                'name' => 'Super Cricket 3D',
                'slug' => 'cricket3d',
                'type' => 'skill',
                'min_bet' => 10,
                'max_bet' => 10000,
                'commission_rate' => 10,
                'is_featured' => true,
            ],
            [
                'name' => 'Carrom Club',
                'slug' => 'carrom',
                'type' => 'skill',
                'min_bet' => 10,
                'max_bet' => 10000,
                'commission_rate' => 10,
                'is_featured' => false,
            ],
            [
                'name' => '8 Ball Pool 1v1',
                'slug' => 'pool_1v1',
                'type' => 'skill',
                'min_bet' => 10,
                'max_bet' => 10000,
                'commission_rate' => 10,
                'is_featured' => false,
            ],
            [
                'name' => 'Championship Snooker',
                'slug' => 'pool_snooker',
                'type' => 'skill',
                'min_bet' => 10,
                'max_bet' => 10000,
                'commission_rate' => 10,
                'is_featured' => false,
            ],
            [
                'name' => 'Sabong Fight',
                'slug' => 'chicken_fight',
                'type' => 'casino',
                'min_bet' => 10,
                'max_bet' => 10000,
                'commission_rate' => 10,
                'is_featured' => true,
            ],
            [
                'name' => 'Aviator 3D',
                'slug' => 'aviator',
                'type' => 'casino',
                'min_bet' => 10,
                'max_bet' => 10000,
                'commission_rate' => 10,
                'is_featured' => true,
            ],
            [
                'name' => 'Tom & Jerry Race',
                'slug' => 'tom_jerry',
                'type' => 'casino',
                'min_bet' => 10,
                'max_bet' => 10000,
                'commission_rate' => 10,
                'is_featured' => true,
            ],
            [
                'name' => 'Teen Patti Live',
                'slug' => 'teen_patti',
                'type' => 'casino',
                'min_bet' => 10,
                'max_bet' => 10000,
                'commission_rate' => 10,
                'is_featured' => true,
            ],
        ];

        foreach ($games as $game) {
            Game::updateOrCreate(['slug' => $game['slug']], $game);
        }
    }
}
