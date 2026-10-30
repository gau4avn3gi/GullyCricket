<?php

use App\Http\Controllers\PlayerPreviewController;
use App\Http\Controllers\ScorebookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ScorebookController::class, 'index'])->name('scorebook.index');
Route::get('/matches/{match}', [ScorebookController::class, 'show'])->name('scorebook.match');
Route::post('/matches', [ScorebookController::class, 'storeMatch'])->name('scorebook.matches.store');
Route::delete('/matches/{match}', [ScorebookController::class, 'destroyMatch'])->name('scorebook.matches.destroy');
Route::post('/matches/{match}/deliveries', [ScorebookController::class, 'storeDelivery'])->name('scorebook.deliveries.store');
Route::put('/matches/{match}/deliveries/{delivery}', [ScorebookController::class, 'updateDelivery'])->name('scorebook.deliveries.update');
Route::delete('/matches/{match}/deliveries/{delivery}', [ScorebookController::class, 'destroyDelivery'])->name('scorebook.deliveries.destroy');
Route::patch('/matches/{match}/innings/{innings}/lock', [ScorebookController::class, 'toggleLock'])->name('scorebook.innings.lock');
Route::post('/matches/{match}/innings/second', [ScorebookController::class, 'startSecondInnings'])->name('scorebook.innings.second');

Route::get('/players', [PlayerPreviewController::class, 'index'])->name('players.preview');
Route::post('/players', [PlayerPreviewController::class, 'store'])->name('players.preview.store');
Route::delete('/players/{player}', [PlayerPreviewController::class, 'destroy'])->name('players.preview.destroy');
