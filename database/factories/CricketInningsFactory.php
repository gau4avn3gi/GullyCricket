<?php

namespace Database\Factories;

use App\Models\CricketInnings;
use App\Models\CricketMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CricketInnings>
 */
class CricketInningsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cricket_match_id' => CricketMatch::factory(),
            'innings_number' => 1,
            'batting_team' => fake()->city().' XI',
            'status' => 'active',
            'locked_at' => null,
            'completed_at' => null,
        ];
    }
}
