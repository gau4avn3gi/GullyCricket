<native:column class="w-full h-full safe-area bg-theme-background">
    <native:column class="w-full p-5 gap-5">
        <native:row class="w-full items-center justify-between">
            <native:column class="gap-1">
                <native:text class="text-sm font-semibold text-theme-primary">GULLY CRICKET</native:text>
                <native:text class="text-3xl font-bold text-theme-on-background">Your squad</native:text>
            </native:column>

            <native:column class="items-end gap-1">
                <native:text class="text-2xl font-bold text-theme-primary">{{ $players->count() }}</native:text>
                <native:text class="text-xs text-theme-on-surface-variant">players</native:text>
            </native:column>
        </native:row>

        <native:column class="w-full p-4 gap-3 bg-theme-surface rounded-2xl border border-theme-outline">
            <native:text class="text-lg font-semibold text-theme-on-surface">Add a player</native:text>

            <native:outlined-text-input
                label="Player name"
                placeholder="e.g. Arjun Sharma"
                native:model.blur="playerName"
            />

            <native:outlined-text-input
                label="Role (optional)"
                placeholder="Batter, bowler, all-rounder"
                native:model.blur="playerRole"
            />

            @if ($feedbackMessage)
                <native:text class="text-sm {{ $feedbackIsError ? 'text-theme-error' : 'text-theme-success' }}">
                    {{ $feedbackMessage }}
                </native:text>
            @endif

            <native:button label="Add to squad" variant="primary" @press="addPlayer" />
        </native:column>

        <native:row class="w-full items-center justify-between">
            <native:text class="text-lg font-semibold text-theme-on-background">Players</native:text>
            <native:text class="text-sm text-theme-on-surface-variant">Saved on this device</native:text>
        </native:row>
    </native:column>

    @if ($players->isEmpty())
        <native:column class="w-full flex-1 items-center justify-center gap-2 p-6">
            <native:text class="text-lg font-semibold text-theme-on-background">Your roster starts here</native:text>
            <native:text class="text-center text-theme-on-surface-variant">
                Add your first player above. Your squad stays saved on this device.
            </native:text>
        </native:column>
    @else
        <native:list class="flex-1 w-full px-5" separator>
            @foreach ($players as $player)
                <native:list-item
                    headline="{{ $player->name }}"
                    supporting="{{ $player->role ?: 'Player' }}"
                    leadingMonogram="{{ mb_strtoupper(mb_substr($player->name, 0, 1)) }}"
                    trailingIconButton="delete"
                    trailing-a11y-label="Remove {{ $player->name }}"
                    on-trailing-press="deletePlayer({{ $player->id }})"
                />
            @endforeach
        </native:list>
    @endif
</native:column>
