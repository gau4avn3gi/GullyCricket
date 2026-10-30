<?php

namespace Database\Factories;

use App\Models\CricketInnings;
use App\Models\ScoreAudit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScoreAudit>
 */
class ScoreAuditFactory extends Factory
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
            'score_delivery_id' => null,
            'event_type' => 'delivery_added',
            'before_state' => null,
            'after_state' => null,
            'created_at' => now(),
        ];
    }
}
