<?php

use App\NativeComponents\Players;
use App\NativeComponents\Scoreboard;
use Illuminate\Support\Facades\Route;

Route::native('/', Scoreboard::class);
Route::native('/players', Players::class);
