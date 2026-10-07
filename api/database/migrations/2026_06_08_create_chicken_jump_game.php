<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('games')->updateOrInsert(
            ['slug' => 'chicken_jump'],
            [
                'name'            => 'Chicken Jump',
                'slug'            => 'chicken_jump',
                'type'            => 'skill',
                'min_bet'         => 10,
                'max_bet'         => 10000,
                'commission_rate' => 10,
                'is_active'       => true,
                'is_featured'     => true,
                'image'           => null,
                'thumbnail'       => null,
                'guidelines'      => json_encode([
                    'heading' => 'Chicken Jump — Stack Battle Rules',
                    'rules' => [
                        'A block slides in from alternating sides. Press JUMP at the right moment to land the chicken on it.',
                        'Perfect landing (≥80% overlap) keeps the full block and scores +1.',
                        'Partial landing (40–80% overlap) trims the block to the overlap width and scores +1.',
                        'Missing the block entirely causes the chicken to fall — instant loss.',
                        'Both players play simultaneously. The first player to fall loses.',
                        'Blocks move faster every 5 successful stacks — stay sharp!',
                        'Forfeiting or leaving the game counts as an immediate loss.',
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('games')->where('slug', 'chicken_jump')->delete();
    }
};
