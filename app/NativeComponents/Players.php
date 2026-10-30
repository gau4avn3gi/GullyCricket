<?php

namespace App\NativeComponents;

use App\Models\Player;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class Players extends NativeComponent
{
    public string $playerName = '';

    public string $playerRole = '';

    public ?string $feedbackMessage = null;

    public bool $feedbackIsError = false;

    public function render(): View
    {
        return view('native.players', [
            'players' => Player::query()->latest()->get(),
        ]);
    }

    public function addPlayer(): void
    {
        $validator = Validator::make([
            'name' => trim($this->playerName),
            'role' => trim($this->playerRole),
        ], [
            'name' => ['required', 'string', 'max:80'],
            'role' => ['nullable', 'string', 'max:60'],
        ]);

        if ($validator->fails()) {
            $this->feedbackMessage = $validator->errors()->first();
            $this->feedbackIsError = true;

            return;
        }

        $attributes = $validator->validated();
        $attributes['role'] = $attributes['role'] !== '' ? $attributes['role'] : null;

        Player::query()->create($attributes);

        $this->playerName = '';
        $this->playerRole = '';
        $this->feedbackMessage = 'Player added to your squad.';
        $this->feedbackIsError = false;
    }

    public function deletePlayer(int $playerId): void
    {
        $player = Player::query()->find($playerId);

        if ($player === null) {
            return;
        }

        $player->delete();
        $this->feedbackMessage = 'Player removed from your squad.';
        $this->feedbackIsError = false;
    }
}
