<?php

namespace Database\Factories;

use App\Models\CricketInnings;
use App\Models\ScoreDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScoreDelivery>
 */
class ScoreDeliveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cricket_innings_id' => CricketInnings::factory(),
            'sequence_number' => 1,
            'batter_runs' => 0,
            'extras_type' => 'none',
            'extra_runs' => 0,
            'total_runs' => 0,
            'is_wicket' => false,
            'legal_ball' => true,
        ];
    }
}
