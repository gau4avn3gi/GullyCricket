<?php

namespace App\Models;

use Database\Factories\ScoreDeliveryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['cricket_innings_id', 'sequence_number', 'batter_runs', 'extras_type', 'extra_runs', 'total_runs', 'is_wicket', 'legal_ball'])]
class ScoreDelivery extends Model
{
    /** @use HasFactory<ScoreDeliveryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_wicket' => 'boolean',
            'legal_ball' => 'boolean',
        ];
    }

    public function innings(): BelongsTo
    {
        return $this->belongsTo(CricketInnings::class, 'cricket_innings_id');
    }
}
