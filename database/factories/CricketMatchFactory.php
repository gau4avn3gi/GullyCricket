<?php

namespace Database\Factories;

use App\Models\CricketMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CricketMatch>
 */
class CricketMatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_one' => fake()->city().' XI',
            'team_two' => fake()->city().' XI',
            'overs_limit' => 10,
            'status' => 'in_progress',
        ];
    }
}
