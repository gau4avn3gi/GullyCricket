<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light dark">
        <title>GullyCricket – Vijaynagar</title>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-950 antialiased dark:bg-slate-950 dark:text-slate-50">
        <main class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-4 py-8 sm:px-7 sm:py-10">
            <header class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div aria-hidden="true" class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-900 text-lg font-black text-white shadow-sm">GC</div>
                    <div class="flex flex-col gap-1">
                        <p class="text-sm font-bold tracking-[0.18em] text-emerald-700 dark:text-emerald-300">GULLY CRICKET</p>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ $selectedMatch ? 'Live Match' : ($homeSection === 'new' ? 'New Matches' : ($homeSection === 'old' ? 'Old Matches' : 'GullyCricket – Vijaynagar')) }}</h1>
                        <p class="text-sm text-slate-600 dark:text-slate-400">Avoid Cheating.</p>
                    </div>
                </div>
                @if ($selectedMatch || $homeSection !== 'home')
                    <nav class="flex gap-2">
                        <a href="{{ route('scorebook.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-900">{{ $selectedMatch ? 'Home' : 'Back' }}</a>
                        <a href="{{ route('players.preview') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-900">Squad</a>
                    </nav>
                @endif
            </header>

            <p class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-100">
                This browser preview uses Laravel's local database. The installed phone app has its own private database, so scores do not sync between browser and phone.
            </p>

            @if (session('status'))
                <p role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100">{{ session('status') }}</p>
            @endif

            @if ($errors->any())
                <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900 dark:bg-red-950 dark:text-red-100">
                    <p class="font-semibold">Please fix the following:</p>
                    <ul class="mt-2 list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-[minmax(16rem,0.82fr)_minmax(0,1.8fr)]">
                <aside class="flex flex-col gap-6">
                    @if (! $selectedMatch && $homeSection === 'home')
                        <section class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <a href="{{ route('scorebook.index', ['section' => 'new']) }}" class="rounded-xl bg-emerald-700 px-4 py-4 text-center text-lg font-semibold text-white hover:bg-emerald-800">New Matches</a>
                            <a href="{{ route('scorebook.index', ['section' => 'old']) }}" class="rounded-xl border border-slate-300 px-4 py-4 text-center text-lg font-semibold hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800">Old Matches</a>
                        </section>
                    @endif

                    @if ($selectedMatch || $homeSection === 'new')
                    <section class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div>
                            <h2 class="text-lg font-semibold">Start a match</h2>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Choose the teams and overs per innings.</p>
                        </div>
                        <form method="POST" action="{{ route('scorebook.matches.store') }}" class="flex flex-col gap-3">
                            @csrf
                            <label class="flex flex-col gap-1.5 text-sm font-medium" for="team_one">
                                Team one
                                <input id="team_one" name="team_one" type="text" maxlength="100" required value="{{ old('team_one') }}" placeholder="Gully XI" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 font-normal outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 dark:border-slate-700 dark:bg-slate-950">
                            </label>
                            <label class="flex flex-col gap-1.5 text-sm font-medium" for="team_two">
                                Team two
                                <input id="team_two" name="team_two" type="text" maxlength="100" required value="{{ old('team_two') }}" placeholder="Street XI" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 font-normal outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 dark:border-slate-700 dark:bg-slate-950">
                            </label>
                            <label class="flex flex-col gap-1.5 text-sm font-medium" for="overs_limit">
                                Overs per innings
                                <input id="overs_limit" name="overs_limit" type="number" min="1" max="50" required value="{{ old('overs_limit', 10) }}" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 font-normal outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 dark:border-slate-700 dark:bg-slate-950">
                            </label>
                            <label class="flex flex-col gap-1.5 text-sm font-medium" for="batting_first">
                                Batting first
                                <select id="batting_first" name="batting_first" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 font-normal outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 dark:border-slate-700 dark:bg-slate-950">
                                    <option value="team_one" @selected(old('batting_first', 'team_one') === 'team_one')>Team one</option>
                                    <option value="team_two" @selected(old('batting_first') === 'team_two')>Team two</option>
                                </select>
                            </label>
                            <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-400">Six legal balls make an over. Wides and no-balls add a penalty run and do not use a legal ball.</p>
                            <button type="submit" class="rounded-xl bg-emerald-700 px-4 py-3 font-semibold text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 dark:focus:ring-offset-slate-900">Create match</button>
                        </form>
                    </section>
                    @endif

                    @if ($selectedMatch || $homeSection === 'old')
                    <section class="flex flex-col gap-3">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-lg font-semibold">Recent matches</h2>
                            <span class="text-xs text-slate-500 dark:text-slate-400">{{ $matches->count() }} saved</span>
                        </div>
                        @forelse ($matches as $match)
                            @php $lastInnings = $match->innings->first(); @endphp
                            <a href="{{ route('scorebook.match', $match) }}" class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-emerald-500 dark:border-slate-800 dark:bg-slate-900">
                                <span class="min-w-0">
                                    <span class="block truncate font-semibold">{{ $match->team_one }} vs {{ $match->team_two }}</span>
                                    <span class="mt-1 block text-sm text-slate-600 dark:text-slate-400">{{ $match->overs_limit }} overs · {{ $lastInnings?->status === 'completed' ? 'Complete' : 'In progress' }}</span>
                                </span>
                                <span class="text-emerald-700 dark:text-emerald-300">Open</span>
                            </a>
                            <form method="POST" action="{{ route('scorebook.matches.destroy', $match) }}" onsubmit="return confirm('Delete this match and all of its score history?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-950">Delete match</button>
                            </form>
                        @empty
                            <p class="rounded-2xl border border-dashed border-slate-300 px-4 py-7 text-center text-sm text-slate-600 dark:border-slate-700 dark:text-slate-400">Your first match will appear here.</p>
                        @endforelse
                    </section>
                    @endif
                </aside>

                <section class="flex min-w-0 flex-col gap-5">
                    @if ($selectedMatch && $innings && $summary)
                        @php
                            $isLocked = $innings->locked_at !== null;
                            $canScore = ! $isLocked && $innings->status === 'active';
                            $opponent = $selectedMatch->team_one === $innings->batting_team ? $selectedMatch->team_two : $selectedMatch->team_one;
                            $entryClass = 'rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-emerald-950';
                        @endphp

                        <article class="flex flex-col gap-4 rounded-3xl bg-emerald-900 p-6 text-white shadow-sm sm:p-8">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-bold tracking-[0.18em] text-emerald-200">{{ $isLocked ? 'SCORE LOCKED' : ($innings->status === 'completed' ? 'INNINGS COMPLETE' : 'LIVE SCORE') }} · INNINGS {{ $innings->innings_number }}</p>
                                    <h2 class="mt-2 text-xl font-semibold">{{ $innings->batting_team }} <span class="font-normal text-emerald-200">vs {{ $opponent }}</span></h2>
                                </div>
                                <div class="text-right">
                                    <p class="text-5xl font-bold tracking-tight">{{ $summary['runs'] }}/{{ $summary['wickets'] }}</p>
                                    <p class="mt-1 text-sm text-emerald-200">{{ $summary['overs'] }} / {{ $selectedMatch->overs_limit }} overs</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-x-6 gap-y-2 border-t border-emerald-700 pt-4 text-sm text-emerald-100">
                                <span>Run rate <strong class="text-white">{{ $summary['run_rate'] }}</strong></span>
                                <span>Extras <strong class="text-white">{{ $summary['extras'] }}</strong></span>
                                <span>Legal balls <strong class="text-white">{{ $summary['legal_balls'] }}</strong></span>
                                <span>{{ $summary['legal_balls'] % 6 === 0 && $summary['legal_balls'] > 0 ? 'Over complete · next ball 1' : ($summary['legal_balls'] % 6).' of 6 legal balls this over' }}</span>
                            </div>
                            @if ($matchProgress)
                                <p class="rounded-lg bg-emerald-800 px-3 py-2 text-sm font-semibold text-white">{{ $matchProgress }}</p>
                            @endif
                        </article>

                        <section class="flex flex-wrap gap-3">
                            @foreach ($inningsSummaries as $inningsSummary)
                                <a href="{{ route('scorebook.match', ['match' => $selectedMatch, 'innings' => $inningsSummary['innings']->innings_number]) }}" class="flex flex-1 items-center justify-between gap-4 rounded-xl border px-4 py-3 shadow-sm {{ $inningsSummary['innings']->innings_number === $innings->innings_number ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950' : 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900' }}">
                                    <span class="text-sm font-medium">{{ $inningsSummary['innings']->batting_team }}</span>
                                    <span class="font-bold">{{ $inningsSummary['summary']['runs'] }}/{{ $inningsSummary['summary']['wickets'] }} <span class="text-xs font-normal text-slate-500">{{ $inningsSummary['summary']['overs'] }} ov</span></span>
                                </a>
                            @endforeach
                        </section>

                        @php
                            $activeOverNumber = $canScore
                                ? min((int) $selectedMatch->overs_limit, intdiv($summary['legal_balls'], 6) + 1)
                                : max(1, min((int) $selectedMatch->overs_limit, intdiv(max(0, $summary['legal_balls'] - 1), 6) + 1));
                            $activeBallNumber = ($summary['legal_balls'] % 6) + 1;
                            $lastDeliveryId = $deliveries->last()?->id;
                        @endphp
                        <section class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <h3 class="text-lg font-semibold">Overs and ball slots</h3>
                                <div class="flex flex-col gap-1">
                                    <p class="text-sm text-slate-600 dark:text-slate-400">Each over has six legal-ball slots. Wides and no-balls stay with the current slot and do not advance it.</p>
                                    @if ($lastDeliveryId && ! $isLocked)
                                        <a href="#last-recorded-ball" class="text-sm font-semibold text-emerald-700 underline dark:text-emerald-300">Edit last ball</a>
                                    @endif
                                </div>
                            </div>
                            @foreach ($overBreakdown as $overNumber => $over)
                                <div class="flex flex-col gap-2 border-t border-slate-100 pt-3 first:border-0 first:pt-0 dark:border-slate-800">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-semibold">Over {{ $overNumber }}</h4>
                                        @if ($canScore && $overNumber === $activeOverNumber)
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">Current over</span>
                                        @endif
                                    </div>
                                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-6">
                                        @foreach ($over['balls'] as $ballNumber => $slot)
                                            @php
                                                $slotLabel = collect($slot['deliveries'])->map(fn ($slotDelivery) => match ($slotDelivery->extras_type) {
                                                    'wide' => 'Wd '.$slotDelivery->extra_runs,
                                                    'no_ball' => 'Nb '.$slotDelivery->total_runs,
                                                    'bye' => 'B '.$slotDelivery->extra_runs,
                                                    'leg_bye' => 'Lb '.$slotDelivery->extra_runs,
                                                    default => (string) $slotDelivery->batter_runs.($slotDelivery->is_wicket ? ' W' : ''),
                                                })->implode(' + ');
                                                $isExpectedBall = $canScore && $overNumber === $activeOverNumber && $ballNumber === $activeBallNumber;
                                                $isLastRecordedSlot = collect($slot['deliveries'])->contains(fn ($slotDelivery) => $slotDelivery->id === $lastDeliveryId);
                                            @endphp
                                            <details id="{{ $isLastRecordedSlot ? 'last-recorded-ball' : 'ball-'.$overNumber.'-'.$ballNumber }}" @if ($isLastRecordedSlot) open @endif class="min-h-16 rounded-xl border p-2 {{ $isExpectedBall ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950' : 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-950' }}">
                                                <summary class="cursor-pointer text-xs font-semibold">Ball {{ $ballNumber }}{{ $isExpectedBall ? ' · next' : ' · options' }}</summary>
                                                <div class="mt-2 flex flex-col gap-3">
                                                <span class="text-xs font-semibold">Ball {{ $ballNumber }}{{ $isExpectedBall ? ' · next' : '' }}</span>
                                                <span class="mt-1 break-words text-sm text-slate-600 dark:text-slate-400">{{ $slotLabel !== '' ? $slotLabel : '—' }}</span>
                                                @if ($isExpectedBall)
                                                    <div class="mt-3 flex flex-col gap-2 border-t border-slate-200 pt-3 dark:border-slate-700">
                                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Runs</p>
                                                        <div class="flex flex-wrap gap-2">
                                                            @foreach ([0, 1, 2, 3, 4, 6] as $runs)
                                                                <form method="POST" action="{{ route('scorebook.deliveries.store', $selectedMatch) }}">
                                                                    @csrf
                                                                    <input type="hidden" name="batter_runs" value="{{ $runs }}">
                                                                    <input type="hidden" name="extras_type" value="none">
                                                                    <button type="submit" class="{{ $entryClass }} min-w-12">{{ $runs }}</button>
                                                                </form>
                                                            @endforeach
                                                            <form method="POST" action="{{ route('scorebook.deliveries.store', $selectedMatch) }}">
                                                                @csrf
                                                                <input type="hidden" name="batter_runs" value="0">
                                                                <input type="hidden" name="extras_type" value="none">
                                                                <input type="hidden" name="is_wicket" value="1">
                                                                <button type="submit" class="rounded-lg bg-red-700 px-3 py-2 text-sm font-semibold text-white hover:bg-red-800">Wicket</button>
                                                            </form>
                                                        </div>
                                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Extras</p>
                                                        <div class="flex flex-wrap gap-2">
                                                            @foreach ([['Wide (+1)', 'wide'], ['No Ball (+1)', 'no_ball']] as [$label, $type])
                                                                <form method="POST" action="{{ route('scorebook.deliveries.store', $selectedMatch) }}">
                                                                    @csrf
                                                                    <input type="hidden" name="batter_runs" value="0">
                                                                    <input type="hidden" name="extras_type" value="{{ $type }}">
                                                                    <input type="hidden" name="extra_runs" value="1">
                                                                    <button type="submit" class="{{ $entryClass }}">{{ $label }}</button>
                                                                </form>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                                @if ($slot['deliveries'] !== [] && ! $isLocked)
                                                    <details class="mt-2">
                                                        <summary class="cursor-pointer text-xs font-semibold text-emerald-800 dark:text-emerald-300">Correct this ball</summary>
                                                        <div class="mt-2 flex flex-col gap-3 rounded-lg bg-white p-3 dark:bg-slate-900">
                                                            @foreach ($slot['deliveries'] as $deliveryIndex => $slotDelivery)
                                                                @include('scorebook.partials.delivery-correction', ['delivery' => $slotDelivery, 'deliveryNumber' => $deliveryIndex + 1])
                                                            @endforeach
                                                        </div>
                                                    </details>
                                                @endif
                                                </div>
                                            </details>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </section>

                        @unless ($canScore)
                            <section class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950">
                                <div>
                                    <h3 class="font-semibold text-amber-950 dark:text-amber-100">{{ $isLocked ? 'This innings is locked' : 'Innings complete' }}</h3>
                                    <p class="mt-1 text-sm text-amber-900/80 dark:text-amber-200/80">{{ $isLocked ? 'Unlock it to enter a correction.' : 'Review the score or start the next innings.' }}</p>
                                </div>
                                <form method="POST" action="{{ route('scorebook.innings.lock', [$selectedMatch, $innings]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-xl bg-amber-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-950 dark:bg-amber-700">{{ $isLocked ? 'Unlock innings' : 'Lock innings' }}</button>
                                </form>
                            </section>
                        @endunless
                        @if ($canStartSecondInnings)
                            <form method="POST" action="{{ route('scorebook.innings.second', $selectedMatch) }}">
                                @csrf
                                <button type="submit" class="w-full rounded-xl bg-emerald-700 px-4 py-3 font-semibold text-white hover:bg-emerald-800">Start second innings</button>
                            </form>
                        @endif

                        <section class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-semibold">Delivery history</h3>
                                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Use the correction menu inside a ball slot to change or remove its delivery.</p>
                                </div>
                                @if ($canScore || ($innings->status === 'completed' && ! $isLocked))
                                    <form method="POST" action="{{ route('scorebook.innings.lock', [$selectedMatch, $innings]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold hover:bg-slate-100 dark:border-slate-700 dark:hover:bg-slate-800">Lock</button>
                                    </form>
                                @endif
                            </div>

                            @forelse ($deliveries as $delivery)
                                @php
                                    $notation = match ($delivery->extras_type) {
                                        'wide' => 'Wd+'.$delivery->extra_runs,
                                        'no_ball' => 'Nb+'.($delivery->batter_runs + 1),
                                        'bye' => 'B'.$delivery->extra_runs,
                                        'leg_bye' => 'Lb'.$delivery->extra_runs,
                                        default => (string) $delivery->batter_runs,
                                    };
                                @endphp
                                <div class="flex flex-col gap-3 border-t border-slate-100 pt-3 first:border-0 first:pt-0 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="font-semibold">{{ $delivery->over_ball }} · {{ $notation }}{{ $delivery->is_wicket ? ' · Wicket' : '' }}</p>
                                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $delivery->total_runs }} run{{ $delivery->total_runs === 1 ? '' : 's' }}{{ $delivery->legal_ball ? '' : ' · not counted in the over' }}</p>
                                    </div>
                                </div>
                            @empty
                                <p class="rounded-xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-600 dark:bg-slate-950 dark:text-slate-400">No deliveries yet. Open the next ball slot in the over panel to score the first ball.</p>
                            @endforelse
                        </section>

                        @if ($audits->isNotEmpty())
                            <details class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                                <summary class="cursor-pointer font-semibold">Score history · {{ $audits->count() }} recent changes</summary>
                                <ol class="mt-4 flex flex-col gap-3">
                                    @foreach ($audits as $audit)
                                        <li class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3 first:border-0 first:pt-0 dark:border-slate-800">
                                            <span class="text-sm font-medium">{{ \Illuminate\Support\Str::headline($audit->event_type) }}</span>
                                            <time class="text-xs text-slate-500 dark:text-slate-400">{{ $audit->created_at->format('M j, Y · g:i a') }}</time>
                                        </li>
                                    @endforeach
                                </ol>
                            </details>
                        @endif
                    @else
                        <section class="flex min-h-72 flex-col items-center justify-center gap-3 rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center dark:border-slate-700 dark:bg-slate-900">
                            <span class="text-sm font-bold tracking-[0.18em] text-emerald-700 dark:text-emerald-300">PLAY FAIR · SCORE CLEAR</span>
                            <h2 class="text-2xl font-bold">Your scorebook starts here</h2>
                            <p class="max-w-md text-sm leading-relaxed text-slate-600 dark:text-slate-400">Create a match to track overs, legal balls, runs, extras, wickets, corrections, and a lockable score history.</p>
                        </section>
                    @endif
                </section>
            </div>

            <footer class="flex flex-col items-center gap-1 border-t border-slate-200 pt-5 text-center dark:border-slate-800">
                <p class="text-xs font-bold tracking-[0.16em] text-slate-600 dark:text-slate-400">GULLY CRICKET · AVOID CHEATING</p>
                <p class="text-xs text-slate-500 dark:text-slate-500">Made by Gaurav · © 2026</p>
            </footer>
        </main>
    </body>
</html>
