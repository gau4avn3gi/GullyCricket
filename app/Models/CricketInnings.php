<?php

namespace App\Models;

use Database\Factories\CricketInningsFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['cricket_match_id', 'innings_number', 'batting_team', 'status', 'locked_at', 'completed_at'])]
class CricketInnings extends Model
{
    protected $table = 'cricket_innings';

    /** @use HasFactory<CricketInningsFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'locked_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(CricketMatch::class, 'cricket_match_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(ScoreDelivery::class, 'cricket_innings_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(ScoreAudit::class, 'cricket_innings_id');
    }
}
