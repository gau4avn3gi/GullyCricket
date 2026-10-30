<?php

namespace App\Models;

use Database\Factories\CricketMatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['team_one', 'team_two', 'overs_limit', 'status'])]
class CricketMatch extends Model
{
    protected $table = 'cricket_matches';

    /** @use HasFactory<CricketMatchFactory> */
    use HasFactory;

    public function innings(): HasMany
    {
        return $this->hasMany(CricketInnings::class);
    }
}
