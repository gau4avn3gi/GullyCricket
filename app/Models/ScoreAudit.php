<?php

namespace App\Models;

use Database\Factories\ScoreAuditFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['cricket_innings_id', 'score_delivery_id', 'event_type', 'before_state', 'after_state'])]
class ScoreAudit extends Model
{
    public $timestamps = false;

    /** @use HasFactory<ScoreAuditFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'before_state' => 'array',
            'after_state' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function innings(): BelongsTo
    {
        return $this->belongsTo(CricketInnings::class, 'cricket_innings_id');
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(ScoreDelivery::class, 'score_delivery_id');
    }
}
