<?php

use App\NativeComponents\Players;
use Illuminate\Support\Facades\Route;

Route::native('/', Players::class);
