<?php

namespace Database\Seeders;

use App\Models\CricketMatch;
use Illuminate\Database\Seeder;

class CricketMatchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $match = CricketMatch::factory()->create();
        $match->innings()->create([
            'innings_number' => 1,
            'batting_team' => $match->team_one,
            'status' => 'active',
        ]);
    }
}
