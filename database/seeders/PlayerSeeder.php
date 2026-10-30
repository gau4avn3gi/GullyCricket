<?php

namespace Database\Seeders;

use App\Models\Player;
use Illuminate\Database\Seeder;

class PlayerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Player::factory()->create([
            'name' => 'Aarav Singh',
            'role' => 'Batter',
        ]);

        Player::factory()->create([
            'name' => 'Kabir Patel',
            'role' => 'Bowler',
        ]);

        Player::factory()->create([
            'name' => 'Rohan Das',
            'role' => 'All-rounder',
        ]);
    }
}
