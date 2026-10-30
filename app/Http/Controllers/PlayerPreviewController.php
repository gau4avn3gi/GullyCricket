<?php

namespace App\Http\Controllers;

use App\Models\Player;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlayerPreviewController extends Controller
{
    public function index(): View
    {
        return view('players.preview', [
            'players' => Player::query()->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'role' => ['nullable', 'string', 'max:60'],
        ]);

        Player::query()->create($validated);

        return to_route('players.preview')->with('status', 'Player added to your squad.');
    }

    public function destroy(Player $player): RedirectResponse
    {
        $player->delete();

        return to_route('players.preview')->with('status', 'Player removed from your squad.');
    }
}
