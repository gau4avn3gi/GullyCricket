<?php

namespace App\NativeComponents;

use App\Models\CricketInnings;
use App\Models\CricketMatch;
use App\Models\ScoreDelivery;
use App\Services\CricketScoringService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Events\Alert\ButtonPressed;
use Native\Mobile\Facades\Dialog;

#[Lazy]
class Scoreboard extends NativeComponent
{
    public string $teamOne = '';

    public string $teamTwo = '';

    public string $oversLimit = '10';

    public string $battingFirstLabel = 'Team one';

    public ?int $matchId = null;

    public ?int $inningsNumber = null;

    public ?int $editingDeliveryId = null;

    public ?int $selectedOverNumber = null;

    public ?int $selectedBallNumber = null;

    public string $correctionDeliveryOption = '';

    public string $homeSection = 'home';

    public bool $ballEntryOpen = false;

    public ?string $feedbackMessage = null;

    public bool $feedbackIsError = false;

    private ?CricketScoringService $scoring = null;

    public function mount(CricketScoringService $scoring): void
    {
        $this->scoring = $scoring;
    }

    protected function placeholder(): View
    {
        return view('native.loading');
    }

    public function render(): View
    {
        $matches = CricketMatch::query()
            ->with(['innings' => fn ($query) => $query->orderByDesc('innings_number')])
            ->latest()
            ->limit(8)
            ->get();

        $selectedMatch = $this->matchId === null
            ? null
            : CricketMatch::query()->find($this->matchId);

        if ($selectedMatch === null && $this->matchId !== null) {
            $this->matchId = null;
            $this->editingDeliveryId = null;
        }

        $innings = $this->currentInnings();
        $summary = $innings !== null ? $this->scoring->summary($innings) : null;
        $matchProgress = $selectedMatch !== null ? $this->scoring->matchProgress($selectedMatch) : null;
        $deliveries = $innings !== null ? $this->scoring->recentDeliveries($innings, 500) : null;
        $overBreakdown = $innings !== null
            ? $this->scoring->overBreakdown($innings, (int) $selectedMatch->overs_limit)
            : [];
        $audits = $innings?->audits()->latest('created_at')->limit(8)->get();
        $inningsSummaries = $selectedMatch?->innings()->orderBy('innings_number')->get()
            ->map(fn (CricketInnings $matchInnings): array => [
                'innings' => $matchInnings,
                'summary' => $this->scoring->summary($matchInnings),
            ]);
        $canStartSecondInnings = $innings !== null
            && $innings->innings_number === 1
            && $innings->status === 'completed'
            && ! $selectedMatch->innings()->where('innings_number', 2)->exists();
        $activeOverNumber = null;
        $activeBallNumber = null;
        $selectedOver = null;
        $selectedBallDeliveries = [];
        $selectedCorrectionDelivery = null;
        $correctionOptions = [];
        $canScoreSelectedBall = false;

        if ($innings !== null && $summary !== null) {
            $canScore = $innings->locked_at === null && $innings->status === 'active';
            $activeOverNumber = $canScore
                ? min((int) $selectedMatch->overs_limit, intdiv($summary['legal_balls'], 6) + 1)
                : max(1, min((int) $selectedMatch->overs_limit, intdiv(max(0, $summary['legal_balls'] - 1), 6) + 1));
            $activeBallNumber = ($summary['legal_balls'] % 6) + 1;
            $selectedOverNumber = array_key_exists($this->selectedOverNumber ?? 0, $overBreakdown)
                ? $this->selectedOverNumber
                : $activeOverNumber;
            $selectedOver = $overBreakdown[$selectedOverNumber] ?? null;
            $selectedBallDeliveries = $selectedOver !== null && $this->selectedBallNumber !== null
                ? ($selectedOver['balls'][$this->selectedBallNumber]['deliveries'] ?? [])
                : [];
            $correctionOptions = collect($selectedBallDeliveries)
                ->values()
                ->map(fn (ScoreDelivery $delivery, int $index): string => $this->correctionOptionLabel($delivery, $index))
                ->all();
            $selectedCorrectionDelivery = collect($selectedBallDeliveries)
                ->first(fn (ScoreDelivery $delivery): bool => $delivery->id === $this->editingDeliveryId);
            $canScoreSelectedBall = $canScore
                && $selectedOverNumber === $activeOverNumber
                && $this->selectedBallNumber === $activeBallNumber;
        } else {
            $selectedOverNumber = null;
        }

        return view('native.scoreboard', [
            'matches' => $matches,
            'selectedMatch' => $selectedMatch,
            'innings' => $innings,
            'summary' => $summary,
            'matchProgress' => $matchProgress,
            'deliveries' => $deliveries,
            'overBreakdown' => $overBreakdown,
            'activeOverNumber' => $activeOverNumber,
            'activeBallNumber' => $activeBallNumber,
            'selectedOverNumber' => $selectedOverNumber,
            'selectedOver' => $selectedOver,
            'selectedBallDeliveries' => $selectedBallDeliveries,
            'selectedCorrectionDelivery' => $selectedCorrectionDelivery,
            'correctionOptions' => $correctionOptions,
            'canScoreSelectedBall' => $canScoreSelectedBall,
            'homeSection' => $this->homeSection,
            'ballEntryOpen' => $this->ballEntryOpen,
            'audits' => $audits,
            'inningsSummaries' => $inningsSummaries,
            'canStartSecondInnings' => $canStartSecondInnings,
        ]);
    }

    public function createMatch(CricketScoringService $scoring): void
    {
        $teamOne = trim($this->teamOne);
        $teamTwo = trim($this->teamTwo);
        $validator = Validator::make([
            'team_one' => $teamOne,
            'team_two' => $teamTwo,
            'overs_limit' => $this->oversLimit,
            'batting_first' => $this->battingFirstLabel === 'Team two' ? 'team_two' : 'team_one',
        ], [
            'team_one' => ['required', 'string', 'max:100'],
            'team_two' => ['required', 'string', 'max:100', Rule::notIn([$teamOne])],
            'overs_limit' => ['required', 'integer', 'between:1,50'],
            'batting_first' => ['required', Rule::in(['team_one', 'team_two'])],
        ]);

        if ($validator->fails()) {
            $this->setError($validator->errors()->first());

            return;
        }

        $match = $scoring->createMatch($validator->validated());
        $this->matchId = $match->id;
        $this->inningsNumber = 1;
        $this->selectedOverNumber = 1;
        $this->selectedBallNumber = null;
        $this->editingDeliveryId = null;
        $this->correctionDeliveryOption = '';
        $this->ballEntryOpen = false;
        $this->teamOne = '';
        $this->teamTwo = '';
        $this->setSuccess('Match started. Every delivery will be saved to the score history.');
    }

    public function openMatch(int $matchId): void
    {
        if (! CricketMatch::query()->whereKey($matchId)->exists()) {
            $this->setError('That match could not be found.');

            return;
        }

        $this->matchId = $matchId;
        $this->inningsNumber = (int) CricketMatch::query()->findOrFail($matchId)->innings()->max('innings_number');
        $this->editingDeliveryId = null;
        $this->selectedOverNumber = null;
        $this->selectedBallNumber = null;
        $this->correctionDeliveryOption = '';
        $this->homeSection = 'old';
        $this->ballEntryOpen = false;
        $this->feedbackMessage = null;
    }

    public function showMatches(): void
    {
        $this->showHome();
    }

    public function showHome(): void
    {
        $this->matchId = null;
        $this->inningsNumber = null;
        $this->editingDeliveryId = null;
        $this->selectedOverNumber = null;
        $this->selectedBallNumber = null;
        $this->correctionDeliveryOption = '';
        $this->homeSection = 'home';
        $this->ballEntryOpen = false;
        $this->feedbackMessage = null;
    }

    public function showNewMatches(): void
    {
        $this->showHome();
        $this->homeSection = 'new';
    }

    public function showOldMatches(): void
    {
        $this->showHome();
        $this->homeSection = 'old';
    }

    public function goBack(): void
    {
        if ($this->ballEntryOpen) {
            $this->closeBallEntry();

            return;
        }

        if ($this->matchId !== null || $this->homeSection !== 'home') {
            $this->showHome();

            return;
        }

        parent::onBackPressed();
    }

    public function onBackPressed(): void
    {
        $this->goBack();
    }

    public function openInnings(int $inningsNumber): void
    {
        $match = $this->currentMatch();

        if ($match === null || ! $match->innings()->where('innings_number', $inningsNumber)->exists()) {
            $this->setError('That innings could not be found in this match.');

            return;
        }

        $this->inningsNumber = $inningsNumber;
        $this->editingDeliveryId = null;
        $this->selectedOverNumber = null;
        $this->selectedBallNumber = null;
        $this->correctionDeliveryOption = '';
        $this->ballEntryOpen = false;
        $this->feedbackMessage = null;
    }

    public function openOver(int $overNumber): void
    {
        $innings = $this->currentInnings();
        $match = $this->currentMatch();

        if ($innings === null || $match === null) {
            $this->setError('Open a match before selecting an over.');

            return;
        }

        $overs = $this->scoring->overBreakdown($innings, (int) $match->overs_limit);

        if (! array_key_exists($overNumber, $overs)) {
            $this->setError('That over is not available in this innings.');

            return;
        }

        $this->selectedOverNumber = $overNumber;
        $this->selectedBallNumber = null;
        $this->editingDeliveryId = null;
        $this->correctionDeliveryOption = '';
        $this->ballEntryOpen = false;
        $this->feedbackMessage = null;
    }

    public function selectBall(int $overNumber, int $ballNumber): void
    {
        $innings = $this->currentInnings();
        $match = $this->currentMatch();

        if ($innings === null || $match === null || $ballNumber < 1 || $ballNumber > 6) {
            $this->setError('Select one of the six ball slots in this over.');

            return;
        }

        $overs = $this->scoring->overBreakdown($innings, (int) $match->overs_limit);
        $summary = $this->scoring->summary($innings);
        $canScore = $innings->locked_at === null && $innings->status === 'active';
        $activeOverNumber = $canScore
            ? min((int) $match->overs_limit, intdiv($summary['legal_balls'], 6) + 1)
            : max(1, min((int) $match->overs_limit, intdiv(max(0, $summary['legal_balls'] - 1), 6) + 1));
        $activeBallNumber = ($summary['legal_balls'] % 6) + 1;

        if (! isset($overs[$overNumber]['balls'][$ballNumber])
            || ($canScore && ($overNumber > $activeOverNumber || ($overNumber === $activeOverNumber && $ballNumber > $activeBallNumber)))) {
            $this->setError('Record the highlighted next ball before selecting a later slot.');

            return;
        }

        $this->selectedOverNumber = $overNumber;
        $this->selectedBallNumber = $ballNumber;
        $this->editingDeliveryId = null;
        $this->correctionDeliveryOption = '';
        $this->ballEntryOpen = true;
        $this->feedbackMessage = null;
    }

    public function closeBallEntry(): void
    {
        $this->ballEntryOpen = false;
    }

    public function editLastBall(CricketScoringService $scoring): void
    {
        $innings = $this->currentInnings();
        $match = $this->currentMatch();

        if ($innings === null || $match === null) {
            $this->setError('Open a match before editing a ball.');

            return;
        }

        if ($innings->locked_at !== null) {
            $this->setError('Unlock this innings before editing its last ball.');

            return;
        }

        $lastDelivery = $innings->deliveries()->orderByDesc('sequence_number')->first();

        if ($lastDelivery === null) {
            $this->setError('There are no recorded balls to edit yet.');

            return;
        }

        $overs = $scoring->overBreakdown($innings, (int) $match->overs_limit);

        foreach ($overs as $overNumber => $over) {
            foreach ($over['balls'] as $ballNumber => $slot) {
                foreach ($slot['deliveries'] as $index => $delivery) {
                    if ($delivery->id !== $lastDelivery->id) {
                        continue;
                    }

                    $this->selectedOverNumber = $overNumber;
                    $this->selectedBallNumber = $ballNumber;
                    $this->editingDeliveryId = $delivery->id;
                    $this->correctionDeliveryOption = $this->correctionOptionLabel($delivery, $index);
                    $this->ballEntryOpen = true;
                    $this->feedbackMessage = null;

                    return;
                }
            }
        }

        $this->setError('The last delivery could not be found in its over.');
    }

    public function updatedCorrectionDeliveryOption(string $option): void
    {
        $this->editingDeliveryId = null;

        if ($option === '') {
            return;
        }

        $innings = $this->currentInnings();
        $match = $this->currentMatch();
        preg_match('/^Delivery\s+(\d+)/', $option, $matches);
        $optionNumber = (int) ($matches[1] ?? 0);

        if ($innings === null || $match === null || $this->selectedOverNumber === null || $this->selectedBallNumber === null || $optionNumber < 1) {
            $this->correctionDeliveryOption = '';
            $this->setError('Choose a delivery from the selected ball.');

            return;
        }

        if ($innings->locked_at !== null) {
            $this->correctionDeliveryOption = '';
            $this->setError('Unlock the innings before correcting a delivery.');

            return;
        }

        $slots = $this->scoring->overBreakdown($innings, (int) $match->overs_limit);
        $delivery = $slots[$this->selectedOverNumber]['balls'][$this->selectedBallNumber]['deliveries'][$optionNumber - 1] ?? null;

        if (! $delivery instanceof ScoreDelivery) {
            $this->correctionDeliveryOption = '';
            $this->setError('That delivery is no longer in the selected ball.');

            return;
        }

        $this->editingDeliveryId = $delivery->id;
        $this->feedbackMessage = null;
    }

    public function confirmDeleteMatch(int $matchId): void
    {
        $match = CricketMatch::query()->find($matchId);

        if ($match === null) {
            $this->setError('That match could not be found.');

            return;
        }

        Dialog::alert(
            'Delete match?',
            'Delete '.$match->team_one.' vs '.$match->team_two.' and all of its innings, deliveries, and score history?',
            [
                ['label' => 'Cancel', 'style' => 'cancel'],
                ['label' => 'Delete', 'style' => 'destructive'],
            ],
        )->buttonPressed(function (ButtonPressed $event) use ($matchId): void {
            if ($event->index !== 1) {
                return;
            }

            $match = CricketMatch::query()->find($matchId);

            if ($match === null) {
                $this->setError('That match was already deleted.');

                return;
            }

            app(CricketScoringService::class)->deleteMatch($match);
            $this->matchId = null;
            $this->inningsNumber = null;
            $this->editingDeliveryId = null;
            $this->selectedOverNumber = null;
            $this->selectedBallNumber = null;
            $this->correctionDeliveryOption = '';
            $this->ballEntryOpen = false;
            $this->homeSection = 'home';
            $this->setSuccess('Match, innings, deliveries, and score history deleted.');
        });
    }

    public function scoreRun(int $runs, CricketScoringService $scoring): void
    {
        $this->record($scoring, ['batter_runs' => $runs, 'extras_type' => 'none'], 'Runs added.');
    }

    public function scoreWicket(CricketScoringService $scoring): void
    {
        $this->record($scoring, [
            'batter_runs' => 0,
            'extras_type' => 'none',
            'is_wicket' => true,
        ], 'Wicket recorded.');
    }

    public function scoreWide(CricketScoringService $scoring): void
    {
        $this->record($scoring, ['extras_type' => 'wide', 'extra_runs' => 1], 'Wide recorded.');
    }

    public function scoreWideRuns(int $runs, CricketScoringService $scoring): void
    {
        $this->record($scoring, ['extras_type' => 'wide', 'extra_runs' => $runs], 'Wide recorded.');
    }

    public function scoreNoBall(CricketScoringService $scoring): void
    {
        $this->record($scoring, [
            'batter_runs' => 0,
            'extras_type' => 'no_ball',
            'extra_runs' => 1,
        ], 'No-ball recorded with its one-run penalty.');
    }

    public function scoreNoBallWithRuns(int $runs, CricketScoringService $scoring): void
    {
        $this->record($scoring, [
            'batter_runs' => $runs,
            'extras_type' => 'no_ball',
            'extra_runs' => 1,
        ], 'No-ball and batter runs recorded.');
    }

    public function scoreBye(CricketScoringService $scoring): void
    {
        $this->record($scoring, ['extras_type' => 'bye', 'extra_runs' => 1], 'Bye recorded.');
    }

    public function scoreByeRuns(int $runs, CricketScoringService $scoring): void
    {
        $this->record($scoring, ['extras_type' => 'bye', 'extra_runs' => $runs], 'Bye recorded.');
    }

    public function scoreLegBye(CricketScoringService $scoring): void
    {
        $this->record($scoring, ['extras_type' => 'leg_bye', 'extra_runs' => 1], 'Leg-bye recorded.');
    }

    public function scoreLegByeRuns(int $runs, CricketScoringService $scoring): void
    {
        $this->record($scoring, ['extras_type' => 'leg_bye', 'extra_runs' => $runs], 'Leg-bye recorded.');
    }

    public function editDelivery(int $deliveryId): void
    {
        $innings = $this->currentInnings();

        if ($innings === null || ! $innings->deliveries()->whereKey($deliveryId)->exists()) {
            $this->setError('That delivery could not be found in this innings.');

            return;
        }

        $this->editingDeliveryId = $deliveryId;
        $this->feedbackMessage = null;
    }

    public function cancelEdit(): void
    {
        $this->editingDeliveryId = null;
        $this->correctionDeliveryOption = '';
    }

    public function correctAsRun(int $runs, CricketScoringService $scoring): void
    {
        $this->correct($scoring, [
            'batter_runs' => $runs,
            'extras_type' => 'none',
            'extra_runs' => 0,
            'is_wicket' => false,
        ], 'Delivery corrected as a regular ball.');
    }

    public function correctAsWicket(CricketScoringService $scoring): void
    {
        $this->correct($scoring, [
            'batter_runs' => 0,
            'extras_type' => 'none',
            'extra_runs' => 0,
            'is_wicket' => true,
        ], 'Delivery corrected to a wicket.');
    }

    public function correctAsWide(CricketScoringService $scoring): void
    {
        $this->correct($scoring, [
            'batter_runs' => 0,
            'extras_type' => 'wide',
            'extra_runs' => 1,
            'is_wicket' => false,
        ], 'Delivery corrected to a wide.');
    }

    public function correctAsNoBall(CricketScoringService $scoring): void
    {
        $this->correct($scoring, [
            'batter_runs' => 0,
            'extras_type' => 'no_ball',
            'extra_runs' => 1,
            'is_wicket' => false,
        ], 'Delivery corrected to a no-ball.');
    }

    public function correctAsBye(CricketScoringService $scoring): void
    {
        $this->correct($scoring, ['extras_type' => 'bye', 'extra_runs' => 1], 'Delivery corrected to a bye.');
    }

    public function correctAsLegBye(CricketScoringService $scoring): void
    {
        $this->correct($scoring, ['extras_type' => 'leg_bye', 'extra_runs' => 1], 'Delivery corrected to a leg-bye.');
    }

    public function deleteDelivery(CricketScoringService $scoring): void
    {
        $innings = $this->currentInnings();
        $delivery = $this->selectedDelivery($innings);

        if ($innings === null || $delivery === null) {
            $this->setError('Select a delivery to remove.');

            return;
        }

        $this->runAction(function () use ($scoring, $innings, $delivery): void {
            $scoring->deleteDelivery($innings, $delivery);
            $this->editingDeliveryId = null;
            $this->correctionDeliveryOption = '';
        }, 'Delivery removed and saved to the score history.');
    }

    public function toggleLock(CricketScoringService $scoring): void
    {
        $innings = $this->currentInnings();

        if ($innings === null) {
            $this->setError('Open a match before locking its score.');

            return;
        }

        $this->runAction(function () use ($scoring, $innings): void {
            $locked = $scoring->toggleLock($innings);
            $this->editingDeliveryId = null;
            $this->correctionDeliveryOption = '';
            $this->setSuccess($locked ? 'Innings locked. Unlock it to make a correction.' : 'Innings unlocked. Changes are recorded in the history.');
        });
    }

    public function startSecondInnings(CricketScoringService $scoring): void
    {
        $match = $this->currentMatch();

        if ($match === null) {
            $this->setError('Open a match before starting its next innings.');

            return;
        }

        $this->runAction(function () use ($scoring, $match): void {
            $innings = $scoring->startSecondInnings($match);
            $this->inningsNumber = $innings->innings_number;
            $this->editingDeliveryId = null;
            $this->correctionDeliveryOption = '';
            $this->selectedOverNumber = null;
            $this->selectedBallNumber = null;
            $this->ballEntryOpen = false;
        }, 'Second innings started. The first innings has been locked.');
    }

    public function openPlayers(): void
    {
        $this->navigate('/players');
    }

    private function record(CricketScoringService $scoring, array $delivery, string $message): void
    {
        $innings = $this->currentInnings();

        if ($innings === null) {
            $this->setError('Open or create a match before scoring.');

            return;
        }

        if ($innings->status === 'active' && $innings->locked_at === null) {
            $legalBalls = $scoring->summary($innings)['legal_balls'];
            $nextOverNumber = min((int) $innings->match()->value('overs_limit'), intdiv($legalBalls, 6) + 1);
            $nextBallNumber = ($legalBalls % 6) + 1;

            if ($this->selectedOverNumber !== $nextOverNumber || $this->selectedBallNumber !== $nextBallNumber) {
                $this->setError('Tap the highlighted next ball slot before entering its result.');

                return;
            }
        }

        $this->runAction(fn () => $scoring->recordDelivery($innings, $delivery), $message);

        $innings->refresh();

        if ($innings->status === 'active' && $innings->locked_at === null) {
            $legalBalls = $scoring->summary($innings)['legal_balls'];
            $this->selectedOverNumber = min((int) $innings->match()->value('overs_limit'), intdiv($legalBalls, 6) + 1);
            $this->selectedBallNumber = ($legalBalls % 6) + 1;
        }
    }

    private function correct(CricketScoringService $scoring, array $replacement, string $message): void
    {
        $innings = $this->currentInnings();
        $delivery = $this->selectedDelivery($innings);

        if ($innings === null || $delivery === null) {
            $this->setError('Select a delivery to correct.');

            return;
        }

        $this->runAction(function () use ($scoring, $innings, $delivery, $replacement): void {
            $scoring->editDelivery($innings, $delivery, $replacement);
            $this->editingDeliveryId = null;
            $this->correctionDeliveryOption = '';
        }, $message);
    }

    private function runAction(callable $action, ?string $successMessage = null): void
    {
        try {
            $action();

            if ($successMessage !== null) {
                $this->setSuccess($successMessage);
            }
        } catch (ValidationException $exception) {
            $this->setError($exception->errors()[array_key_first($exception->errors())][0]);
        }
    }

    private function currentMatch(): ?CricketMatch
    {
        return $this->matchId === null ? null : CricketMatch::query()->find($this->matchId);
    }

    private function currentInnings(): ?CricketInnings
    {
        $match = $this->currentMatch();

        if ($match === null) {
            return null;
        }

        return $match->innings()
            ->when($this->inningsNumber !== null, fn ($query) => $query->where('innings_number', $this->inningsNumber))
            ->orderByDesc('innings_number')
            ->first();
    }

    private function selectedDelivery(?CricketInnings $innings): ?ScoreDelivery
    {
        if ($innings === null || $this->editingDeliveryId === null) {
            return null;
        }

        return $innings->deliveries()->whereKey($this->editingDeliveryId)->first();
    }

    private function correctionOptionLabel(ScoreDelivery $delivery, int $index): string
    {
        $notation = match ($delivery->extras_type) {
            'wide' => 'Wide +'.$delivery->extra_runs,
            'no_ball' => 'No-ball +'.$delivery->total_runs,
            'bye' => 'Bye '.$delivery->extra_runs,
            'leg_bye' => 'Leg-bye '.$delivery->extra_runs,
            default => $delivery->batter_runs.' run'.($delivery->batter_runs === 1 ? '' : 's'),
        };

        if ($delivery->is_wicket) {
            $notation .= ' · wicket';
        }

        return 'Delivery '.($index + 1).' · '.$notation;
    }

    private function setSuccess(string $message): void
    {
        $this->feedbackMessage = $message;
        $this->feedbackIsError = false;
    }

    private function setError(string $message): void
    {
        $this->feedbackMessage = $message;
        $this->feedbackIsError = true;
    }
}
