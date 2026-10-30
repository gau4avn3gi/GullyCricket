<native:scroll-view class="w-full h-full bg-theme-background">
    <native:column class="w-full p-5 gap-5">
        <native:row class="w-full items-center justify-between">
            <native:column class="gap-1">
                <native:text class="text-sm font-semibold text-theme-primary">GULLY CRICKET</native:text>
                <native:text class="text-3xl font-bold text-theme-on-background">{{ $selectedMatch ? 'Live Match' : ($homeSection === 'new' ? 'New Matches' : ($homeSection === 'old' ? 'Old Matches' : 'GullyCricket – Vijaynagar')) }}</native:text>
                <native:text class="text-sm text-theme-on-surface-variant">Avoid Cheating.</native:text>
            </native:column>
            @if ($selectedMatch || $homeSection !== 'home')
                <native:row class="gap-2">
                    @if ($selectedMatch)
                        <native:button label="Home" variant="ghost" @press="showHome" />
                    @else
                        <native:button label="Back" variant="ghost" @press="goBack" />
                    @endif
                    <native:button label="Squad" variant="secondary" @press="openPlayers" />
                </native:row>
            @endif
        </native:row>

        @if ($feedbackMessage)
            <native:column class="w-full p-3 bg-theme-surface rounded-xl border border-theme-outline">
                <native:text class="text-sm {{ $feedbackIsError ? 'text-theme-error' : 'text-theme-success' }}">{{ $feedbackMessage }}</native:text>
            </native:column>
        @endif

        @if ($selectedMatch && $innings && $summary)
            @php
                $isLocked = $innings->locked_at !== null;
                $canScore = ! $isLocked && $innings->status === 'active';
            @endphp

            <native:row class="w-full items-center justify-between">
                <native:column class="gap-1">
                    <native:text class="text-xs font-semibold text-theme-primary">INNINGS {{ $innings->innings_number }}</native:text>
                    <native:text class="text-xl font-bold text-theme-on-background">{{ $innings->batting_team }}</native:text>
                    <native:text class="text-sm text-theme-on-surface-variant">vs {{ $selectedMatch->team_one === $innings->batting_team ? $selectedMatch->team_two : $selectedMatch->team_one }}</native:text>
                </native:column>
                <native:row class="gap-2">
                    <native:button label="Delete match" variant="destructive" @press="confirmDeleteMatch({{ $selectedMatch->id }})" />
                </native:row>
            </native:row>

            @if ($inningsSummaries->count() > 1)
                <native:row class="w-full gap-2 flex-wrap">
                    @foreach ($inningsSummaries as $inningsSummary)
                        <native:button
                            label="Innings {{ $inningsSummary['innings']->innings_number }} · {{ $inningsSummary['summary']['runs'] }}/{{ $inningsSummary['summary']['wickets'] }}"
                            variant="{{ $inningsSummary['innings']->innings_number === $innings->innings_number ? 'primary' : 'secondary' }}"
                            @press="openInnings({{ $inningsSummary['innings']->innings_number }})"
                        />
                    @endforeach
                </native:row>
            @endif

            <native:column class="w-full p-5 gap-3 bg-theme-primary rounded-2xl">
                <native:text class="text-sm text-theme-on-primary">{{ $innings->status === 'completed' ? 'INNINGS COMPLETE' : ($isLocked ? 'SCORE LOCKED' : 'LIVE SCORE') }}</native:text>
                <native:text class="text-5xl font-bold text-theme-on-primary">{{ $summary['runs'] }}/{{ $summary['wickets'] }}</native:text>
                <native:text class="text-lg font-semibold text-theme-on-primary">{{ $summary['overs'] }} / {{ $selectedMatch->overs_limit }} overs</native:text>
                <native:row class="w-full justify-between">
                    <native:text class="text-sm text-theme-on-primary">Run rate {{ $summary['run_rate'] }}</native:text>
                    <native:text class="text-sm text-theme-on-primary">Extras {{ $summary['extras'] }}</native:text>
                </native:row>
                <native:text class="text-sm text-theme-on-primary">Legal balls this innings: {{ $summary['legal_balls'] }} · {{ $summary['legal_balls'] % 6 === 0 && $summary['legal_balls'] > 0 ? 'Over complete · next ball 1' : ($summary['legal_balls'] % 6).' of 6 legal balls this over' }}</native:text>
                @if ($matchProgress)
                    <native:text class="text-sm font-semibold text-theme-on-primary">{{ $matchProgress }}</native:text>
                @endif
            </native:column>

            <native:column class="w-full p-4 gap-2 bg-theme-surface rounded-2xl border border-theme-outline">
                <native:text class="text-base font-semibold text-theme-on-surface">Match summary</native:text>
                @foreach ($inningsSummaries as $inningsSummary)
                    <native:row class="w-full items-center justify-between">
                        <native:text class="text-sm text-theme-on-surface-variant">{{ $inningsSummary['innings']->batting_team }}</native:text>
                        <native:text class="text-sm font-semibold text-theme-on-surface">{{ $inningsSummary['summary']['runs'] }}/{{ $inningsSummary['summary']['wickets'] }} · {{ $inningsSummary['summary']['overs'] }} ov</native:text>
                    </native:row>
                @endforeach
            </native:column>

            @if ($isLocked)
                <native:column class="w-full p-4 gap-2 bg-theme-surface rounded-2xl border border-theme-outline">
                    <native:text class="text-base font-semibold text-theme-on-surface">This innings is locked</native:text>
                    <native:text class="text-sm text-theme-on-surface-variant">Scoring and corrections are paused. Unlocking is added to the history.</native:text>
                    <native:button label="Unlock innings" variant="secondary" @press="toggleLock" />
                </native:column>
            @elseif ($innings->status === 'completed')
                <native:column class="w-full p-4 gap-2 bg-theme-surface rounded-2xl border border-theme-outline">
                    <native:text class="text-base font-semibold text-theme-on-surface">Innings complete</native:text>
                    <native:text class="text-sm text-theme-on-surface-variant">You can review or correct deliveries, then lock the score.</native:text>
                    <native:button label="Lock innings" variant="secondary" @press="toggleLock" />
                </native:column>
            @endif

            <native:column class="w-full p-4 gap-3 bg-theme-surface rounded-2xl border border-theme-outline">
                <native:row class="w-full items-center justify-between">
                    <native:text class="text-lg font-semibold text-theme-on-surface">Overs and ball slots</native:text>
                    @if (! $deliveries->isEmpty() && ! $isLocked)
                        <native:button label="Edit last ball" variant="secondary" @press="editLastBall" />
                    @endif
                </native:row>
                <native:text class="text-sm text-theme-on-surface-variant">Tap an over, then one of its six legal-ball slots. Wides and no-balls appear in the same slot and leave it open.</native:text>
                <native:row class="w-full gap-2 flex-wrap">
                    @foreach ($overBreakdown as $overNumber => $over)
                        <native:button
                            label="Over {{ $overNumber }}{{ $overNumber === $activeOverNumber && $canScore ? ' · live' : '' }}"
                            variant="{{ $overNumber === $selectedOverNumber ? 'primary' : 'secondary' }}"
                            @press="openOver({{ $overNumber }})"
                        />
                    @endforeach
                </native:row>

                @if ($selectedOver)
                    <native:text class="text-base font-semibold text-theme-on-surface">Over {{ $selectedOverNumber }} · choose a ball</native:text>
                    <native:row class="w-full gap-2 flex-wrap">
                        @foreach ($selectedOver['balls'] as $ballNumber => $slot)
                            @php
                                $slotDeliveries = $slot['deliveries'];
                                $slotLabel = collect($slotDeliveries)->map(fn ($slotDelivery) => match ($slotDelivery->extras_type) {
                                    'wide' => 'Wd '.$slotDelivery->extra_runs,
                                    'no_ball' => 'Nb '.$slotDelivery->total_runs,
                                    'bye' => 'B '.$slotDelivery->extra_runs,
                                    'leg_bye' => 'Lb '.$slotDelivery->extra_runs,
                                    default => (string) $slotDelivery->batter_runs.($slotDelivery->is_wicket ? ' W' : ''),
                                })->implode(' + ');
                                $isExpectedBall = $selectedOverNumber === $activeOverNumber && $ballNumber === $activeBallNumber && $canScore;
                                $isSelectedBall = $ballNumber === $selectedBallNumber;
                            @endphp
                            <native:column class="min-w-20 items-center gap-1 p-2 bg-theme-background rounded-xl">
                                <native:button
                                    label="{{ $isExpectedBall ? 'Next · ball '.$ballNumber : 'Ball '.$ballNumber }}"
                                    variant="{{ $isSelectedBall || $isExpectedBall ? 'primary' : 'secondary' }}"
                                    @press="selectBall({{ $overNumber }}, {{ $ballNumber }})"
                                />
                                <native:text class="text-xs text-theme-on-surface-variant">{{ $slotLabel !== '' ? $slotLabel : ($isExpectedBall ? 'Ready' : '—') }}</native:text>
                            </native:column>
                        @endforeach
                    </native:row>
                @endif
            </native:column>

            @if ($selectedBallNumber !== null)
                <native:bottom-sheet :visible="$ballEntryOpen" detents="medium,large" @dismiss="closeBallEntry">
                    <native:scroll-view class="w-full">
                        <native:column class="w-full p-5 gap-4 bg-theme-surface">
                            <native:text class="text-xl font-bold text-theme-on-surface">Over {{ $selectedOverNumber }} · Ball {{ $selectedBallNumber }}</native:text>

            @if ($canScoreSelectedBall)
                <native:column class="w-full p-4 gap-3 bg-theme-surface rounded-2xl border border-theme-primary">
                    <native:text class="text-lg font-semibold text-theme-on-surface">Enter Over {{ $selectedOverNumber }} · Ball {{ $selectedBallNumber }}</native:text>
                    <native:text class="text-sm text-theme-on-surface-variant">Choose what happened on this delivery. Each tap saves it immediately.</native:text>
                    <native:row class="w-full gap-2 flex-wrap">
                        @foreach ([0, 1, 2, 3, 4, 6] as $runs)
                            <native:button label="{{ $runs }} runs" variant="secondary" @press="scoreRun({{ $runs }})" />
                        @endforeach
                        <native:button label="Wicket" variant="destructive" @press="scoreWicket" />
                    </native:row>
                    <native:text class="text-sm font-semibold text-theme-on-surface">Extras (do not use a legal-ball slot)</native:text>
                    <native:row class="w-full gap-2 flex-wrap">
                        <native:button label="Wide (+1)" variant="secondary" @press="scoreWide" />
                        <native:button label="No Ball (+1)" variant="secondary" @press="scoreNoBall" />
                    </native:row>
                </native:column>
            @endif
                <native:column class="w-full p-4 gap-3 bg-theme-surface rounded-2xl border border-theme-outline">
                    <native:text class="text-lg font-semibold text-theme-on-surface">Over {{ $selectedOverNumber }} · Ball {{ $selectedBallNumber }}</native:text>
                    @forelse ($selectedBallDeliveries as $slotDelivery)
                        @php
                            $slotNotation = match ($slotDelivery->extras_type) {
                                'wide' => 'Wide +'.$slotDelivery->extra_runs,
                                'no_ball' => 'No-ball +'.$slotDelivery->total_runs,
                                'bye' => 'Bye +'.$slotDelivery->extra_runs,
                                'leg_bye' => 'Leg-bye +'.$slotDelivery->extra_runs,
                                default => $slotDelivery->batter_runs.' run'.($slotDelivery->batter_runs === 1 ? '' : 's'),
                            };
                        @endphp
                        <native:row class="w-full items-center justify-between">
                            <native:text class="text-sm text-theme-on-surface-variant">{{ $slotNotation }}{{ $slotDelivery->is_wicket ? ' · Wicket' : '' }} · {{ $slotDelivery->total_runs }} total</native:text>
                        </native:row>
                    @empty
                        <native:text class="text-sm text-theme-on-surface-variant">This slot has no recorded deliveries.</native:text>
                    @endforelse

                    @if (! $isLocked && $selectedBallDeliveries !== [])
                        <native:select
                            label="Correct a delivery from this ball"
                            placeholder="Choose delivery"
                            :options="$correctionOptions"
                            native:model="correctionDeliveryOption"
                        />
                    @endif

                    @if ($selectedCorrectionDelivery && ! $isLocked)
                        @php
                            $correctionNotation = match ($selectedCorrectionDelivery->extras_type) {
                                'wide' => 'Wide +'.$selectedCorrectionDelivery->extra_runs,
                                'no_ball' => 'No-ball +'.$selectedCorrectionDelivery->total_runs,
                                'bye' => 'Bye '.$selectedCorrectionDelivery->extra_runs,
                                'leg_bye' => 'Leg-bye '.$selectedCorrectionDelivery->extra_runs,
                                default => $selectedCorrectionDelivery->batter_runs.' run'.($selectedCorrectionDelivery->batter_runs === 1 ? '' : 's'),
                            };
                        @endphp
                        <native:column class="w-full p-3 gap-2 bg-theme-background rounded-xl">
                            <native:text class="text-sm font-semibold text-theme-on-background">Correct {{ $correctionNotation }}{{ $selectedCorrectionDelivery->is_wicket ? ' · Wicket' : '' }}</native:text>
                            <native:row class="w-full gap-2 flex-wrap">
                                @foreach ([0, 1, 2, 3, 4, 6] as $runs)
                                    <native:button label="{{ $runs }} runs" size="sm" variant="secondary" @press="correctAsRun({{ $runs }})" />
                                @endforeach
                            </native:row>
                            <native:row class="w-full gap-2 flex-wrap">
                                <native:button label="Wide" size="sm" variant="secondary" @press="correctAsWide" />
                                <native:button label="No-ball" size="sm" variant="secondary" @press="correctAsNoBall" />
                                <native:button label="Wicket" size="sm" variant="destructive" @press="correctAsWicket" />
                            </native:row>
                            <native:row class="w-full gap-2">
                                <native:button label="Remove delivery" size="sm" variant="destructive" @press="deleteDelivery" />
                                <native:button label="Close" size="sm" variant="ghost" @press="cancelEdit" />
                            </native:row>
                        </native:column>
                    @endif
                </native:column>

                            <native:button label="Close" variant="ghost" @press="closeBallEntry" />
                        </native:column>
                    </native:scroll-view>
                </native:bottom-sheet>
            @endif

            @if ($canStartSecondInnings)
                <native:column class="w-full gap-3">
                    <native:button label="Start second innings" variant="primary" @press="startSecondInnings" />
                </native:column>
            @endif

            <native:column class="w-full gap-3">
                <native:row class="w-full items-center justify-between">
                    <native:text class="text-lg font-semibold text-theme-on-background">Recent deliveries</native:text>
                    @if ($innings->status === 'completed' && ! $isLocked)
                        <native:button label="Lock score" variant="secondary" @press="toggleLock" />
                    @endif
                </native:row>

                @if ($deliveries->isEmpty())
                    <native:column class="w-full p-5 gap-2 bg-theme-surface rounded-xl border border-theme-outline">
                        <native:text class="font-semibold text-theme-on-surface">No deliveries yet</native:text>
                        <native:text class="text-sm text-theme-on-surface-variant">The score starts when you record the first ball.</native:text>
                    </native:column>
                @else
                    @foreach ($deliveries as $delivery)
                        @php
                            $notation = match ($delivery->extras_type) {
                                'wide' => 'Wd+'.$delivery->extra_runs,
                                'no_ball' => 'Nb+'.($delivery->batter_runs + 1),
                                'bye' => 'B'.$delivery->extra_runs,
                                'leg_bye' => 'Lb'.$delivery->extra_runs,
                                default => (string) $delivery->batter_runs,
                            };
                        @endphp

                        <native:column class="w-full p-3 gap-2 bg-theme-surface rounded-xl border border-theme-outline">
                            <native:row class="w-full items-center justify-between">
                                <native:column class="gap-1">
                                    <native:text class="font-semibold text-theme-on-surface">{{ $delivery->over_ball }} · {{ $notation }}{{ $delivery->is_wicket ? ' · Wicket' : '' }}</native:text>
                                    <native:text class="text-sm text-theme-on-surface-variant">{{ $delivery->total_runs }} run{{ $delivery->total_runs === 1 ? '' : 's' }}{{ $delivery->legal_ball ? '' : ' · does not count in over' }}</native:text>
                                </native:column>
                            </native:row>
                        </native:column>
                    @endforeach
                @endif
            </native:column>

            @if ($audits->isNotEmpty())
                <native:column class="w-full gap-2">
                    <native:text class="text-lg font-semibold text-theme-on-background">Score history</native:text>
                    @foreach ($audits as $audit)
                        <native:text class="text-sm text-theme-on-surface-variant">{{ \Illuminate\Support\Str::headline($audit->event_type) }} · {{ $audit->created_at->format('M j, g:i a') }}</native:text>
                    @endforeach
                </native:column>
            @endif

        @elseif ($homeSection === 'new')
            <native:column class="w-full p-4 gap-3 bg-theme-surface rounded-2xl border border-theme-outline">
                <native:text class="text-lg font-semibold text-theme-on-surface">Start a match</native:text>
                <native:outlined-text-input label="Team one" placeholder="e.g. Gully XI" native:model.blur="teamOne" />
                <native:outlined-text-input label="Team two" placeholder="e.g. Street XI" native:model.blur="teamTwo" />
                <native:select label="Overs per innings" :options="['5', '6', '8', '10', '12', '15', '20', '25', '50']" native:model="oversLimit" />
                <native:select label="Batting first" :options="['Team one', 'Team two']" native:model="battingFirstLabel" />
                <native:text class="text-xs text-theme-on-surface-variant">Six legal balls make an over. Wides and no-balls are recorded but do not use a legal ball.</native:text>
                <native:button label="Create match" variant="primary" @press="createMatch" />
            </native:column>
        @elseif ($homeSection === 'old')
            <native:row class="w-full items-center justify-between">
                <native:text class="text-lg font-semibold text-theme-on-background">Old matches</native:text>
                <native:text class="text-sm text-theme-on-surface-variant">Saved on this device</native:text>
            </native:row>

            @forelse ($matches as $match)
                @php $lastInnings = $match->innings->first(); @endphp
                <native:list-item
                    headline="{{ $match->team_one }} vs {{ $match->team_two }}"
                    supporting="{{ $match->overs_limit }} overs · {{ $lastInnings?->status === 'completed' ? 'Complete' : 'In progress' }}"
                    leadingMonogram="{{ mb_strtoupper(mb_substr($match->team_one, 0, 1)) }}"
                    trailingIconButton="chevron.right"
                    trailing-a11y-label="Open match"
                    on-trailing-press="openMatch({{ $match->id }})"
                />
                <native:button label="Delete match" variant="destructive" @press="confirmDeleteMatch({{ $match->id }})" />
            @empty
                <native:column class="w-full p-5 gap-2 bg-theme-surface rounded-xl border border-theme-outline">
                    <native:text class="font-semibold text-theme-on-surface">No matches yet</native:text>
                    <native:text class="text-sm text-theme-on-surface-variant">Your saved matches will appear here.</native:text>
                </native:column>
            @endforelse
        @else
            <native:column class="w-full gap-4">
                <native:button label="New Matches" variant="primary" @press="showNewMatches" />
                <native:button label="Old Matches" variant="secondary" @press="showOldMatches" />
            </native:column>
        @endif

        <native:column class="w-full items-center gap-1 pt-3">
            <native:text class="text-xs font-semibold text-theme-on-surface-variant">GULLY CRICKET · AVOID CHEATING</native:text>
            <native:text class="text-xs text-theme-on-surface-variant">Made by Gaurav · © 2026</native:text>
        </native:column>
    </native:column>
</native:scroll-view>
