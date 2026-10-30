<div class="flex flex-col gap-3 border-t border-slate-200 pt-3 first:border-0 first:pt-0 dark:border-slate-800">
    @php
        $correctionLabel = match ($delivery->extras_type) {
            'wide' => 'Wide +'.$delivery->extra_runs,
            'no_ball' => 'No-ball +'.$delivery->total_runs,
            'bye' => 'Bye '.$delivery->extra_runs,
            'leg_bye' => 'Leg-bye '.$delivery->extra_runs,
            default => $delivery->batter_runs.' run'.($delivery->batter_runs === 1 ? '' : 's'),
        };
    @endphp
    <p class="text-sm font-semibold">Delivery {{ $deliveryNumber }} Â· {{ $correctionLabel }}{{ $delivery->is_wicket ? ' Â· Wicket' : '' }}</p>
    <form method="POST" action="{{ route('scorebook.deliveries.update', [$selectedMatch, $delivery]) }}" class="grid gap-2 sm:grid-cols-2">
        @csrf
        @method('PUT')
        <label class="flex flex-col gap-1 text-xs font-medium" for="type-{{ $delivery->id }}">Delivery type
            <select id="type-{{ $delivery->id }}" name="extras_type" class="rounded-lg border border-slate-300 bg-white px-2 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                @foreach (['none' => 'Regular ball', 'wide' => 'Wide', 'no_ball' => 'No-ball', 'bye' => 'Bye', 'leg_bye' => 'Leg-bye'] as $type => $label)
                    <option value="{{ $type }}" @selected($delivery->extras_type === $type)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="flex flex-col gap-1 text-xs font-medium" for="runs-{{ $delivery->id }}">Batter runs
            <input id="runs-{{ $delivery->id }}" name="batter_runs" type="number" min="0" max="6" value="{{ $delivery->batter_runs }}" class="rounded-lg border border-slate-300 bg-white px-2 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
        </label>
        <label class="flex flex-col gap-1 text-xs font-medium" for="extras-{{ $delivery->id }}">Extra runs
            <input id="extras-{{ $delivery->id }}" name="extra_runs" type="number" min="0" max="6" value="{{ $delivery->extra_runs }}" class="rounded-lg border border-slate-300 bg-white px-2 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
        </label>
        <label class="flex items-center gap-2 text-sm font-medium">
            <input type="checkbox" name="is_wicket" value="1" @checked($delivery->is_wicket) class="size-4 accent-emerald-700">
            Wicket
        </label>
        <button type="submit" class="rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800 sm:col-span-2">Save correction</button>
    </form>
    <form method="POST" action="{{ route('scorebook.deliveries.destroy', [$selectedMatch, $delivery]) }}" onsubmit="return confirm('Remove this delivery? Its before-state will stay in the score history.')">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-sm font-semibold text-red-700 hover:underline dark:text-red-300">Remove delivery</button>
    </form>
</div>
