<?php

namespace App\Services;

use App\Models\CricketInnings;
use App\Models\CricketMatch;
use App\Models\ScoreAudit;
use App\Models\ScoreDelivery;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CricketScoringService
{
    private const EXTRAS_TYPES = ['none', 'wide', 'no_ball', 'bye', 'leg_bye'];

    /** @param array{team_one: string, team_two: string, overs_limit: int, batting_first: string} $data */
    public function createMatch(array $data): CricketMatch
    {
        return DB::transaction(function () use ($data): CricketMatch {
            $match = CricketMatch::query()->create([
                'team_one' => $data['team_one'],
                'team_two' => $data['team_two'],
                'overs_limit' => $data['overs_limit'],
                'status' => 'in_progress',
            ]);

            $battingTeam = $data['batting_first'] === 'team_two'
                ? $match->team_two
                : $match->team_one;

            $innings = $match->innings()->create([
                'innings_number' => 1,
                'batting_team' => $battingTeam,
                'status' => 'active',
            ]);

            $this->recordAudit($innings, 'innings_started', null, [
                'innings_number' => 1,
                'batting_team' => $battingTeam,
            ]);

            return $match;
        });
    }

    public function deleteMatch(CricketMatch $match): void
    {
        DB::transaction(function () use ($match): void {
            $match = CricketMatch::query()->lockForUpdate()->findOrFail($match->id);
            $match->delete();
        });
    }

    /** @param array{batter_runs?: int, extras_type: string, extra_runs?: int, is_wicket?: bool} $data */
    public function recordDelivery(CricketInnings $innings, array $data): ScoreDelivery
    {
        return DB::transaction(function () use ($innings, $data): ScoreDelivery {
            $innings = CricketInnings::query()->lockForUpdate()->findOrFail($innings->id);
            $this->ensureWritable($innings, true);

            $delivery = $innings->deliveries()->create([
                'sequence_number' => ((int) $innings->deliveries()->max('sequence_number')) + 1,
                ...$this->normalizeDelivery($data),
            ]);

            $this->recordAudit($innings, 'delivery_added', null, $this->deliveryState($delivery), $delivery->id);
            $this->refreshInningsStatus($innings);

            return $delivery;
        });
    }

    /** @param array{batter_runs?: int, extras_type: string, extra_runs?: int, is_wicket?: bool} $data */
    public function editDelivery(CricketInnings $innings, ScoreDelivery $delivery, array $data): ScoreDelivery
    {
        return DB::transaction(function () use ($innings, $delivery, $data): ScoreDelivery {
            $innings = CricketInnings::query()->lockForUpdate()->findOrFail($innings->id);
            $delivery = ScoreDelivery::query()->lockForUpdate()->findOrFail($delivery->id);

            $this->ensureDeliveryBelongsToInnings($innings, $delivery);
            $this->ensureWritable($innings, false);

            $before = $this->deliveryState($delivery);
            $delivery->update($this->normalizeDelivery($data));
            $delivery->refresh();

            $this->recordAudit($innings, 'delivery_edited', $before, $this->deliveryState($delivery), $delivery->id);
            $this->refreshInningsStatus($innings);

            return $delivery;
        });
    }

    public function deleteDelivery(CricketInnings $innings, ScoreDelivery $delivery): void
    {
        DB::transaction(function () use ($innings, $delivery): void {
            $innings = CricketInnings::query()->lockForUpdate()->findOrFail($innings->id);
            $delivery = ScoreDelivery::query()->lockForUpdate()->findOrFail($delivery->id);

            $this->ensureDeliveryBelongsToInnings($innings, $delivery);
            $this->ensureWritable($innings, false);

            $before = $this->deliveryState($delivery);
            $delivery->delete();

            $this->recordAudit($innings, 'delivery_deleted', $before, null);
            $this->refreshInningsStatus($innings);
        });
    }

    public function toggleLock(CricketInnings $innings): bool
    {
        return DB::transaction(function () use ($innings): bool {
            $innings = CricketInnings::query()->lockForUpdate()->findOrFail($innings->id);
            $wasLocked = $innings->locked_at !== null;
            $before = ['locked' => $wasLocked];

            $innings->locked_at = $wasLocked ? null : now();
            $innings->save();

            $this->recordAudit(
                $innings,
                $wasLocked ? 'innings_unlocked' : 'innings_locked',
                $before,
                ['locked' => ! $wasLocked],
            );

            return ! $wasLocked;
        });
    }

    public function startSecondInnings(CricketMatch $match): CricketInnings
    {
        return DB::transaction(function () use ($match): CricketInnings {
            $match = CricketMatch::query()->lockForUpdate()->findOrFail($match->id);
            $existingSecondInnings = $match->innings()->where('innings_number', 2)->first();

            if ($existingSecondInnings !== null) {
                return $existingSecondInnings;
            }

            $firstInnings = $match->innings()->where('innings_number', 1)->first();

            if ($firstInnings === null || $firstInnings->status !== 'completed') {
                throw ValidationException::withMessages([
                    'innings' => 'Finish the first innings before starting the next innings.',
                ]);
            }

            if ($firstInnings->locked_at === null) {
                $firstInnings->locked_at = now();
                $firstInnings->save();
                $this->recordAudit($firstInnings, 'innings_locked', ['locked' => false], ['locked' => true]);
            }

            $battingTeam = $match->team_one === $firstInnings->batting_team
                ? $match->team_two
                : $match->team_one;

            $secondInnings = $match->innings()->create([
                'innings_number' => 2,
                'batting_team' => $battingTeam,
                'status' => 'active',
            ]);

            $this->recordAudit($secondInnings, 'innings_started', null, [
                'innings_number' => 2,
                'batting_team' => $battingTeam,
            ]);

            return $secondInnings;
        });
    }

    /** @return array{runs: int, wickets: int, extras: int, legal_balls: int, overs: string, run_rate: string} */
    public function summary(CricketInnings $innings): array
    {
        $aggregate = $innings->deliveries()
            ->selectRaw('COALESCE(SUM(total_runs), 0) as runs')
            ->selectRaw('COALESCE(SUM(CASE WHEN is_wicket = 1 THEN 1 ELSE 0 END), 0) as wickets')
            ->selectRaw('COALESCE(SUM(extra_runs), 0) as extras')
            ->selectRaw('COALESCE(SUM(CASE WHEN legal_ball = 1 THEN 1 ELSE 0 END), 0) as legal_balls')
            ->first();

        $legalBalls = (int) $aggregate->legal_balls;
        $runs = (int) $aggregate->runs;
        $overs = intdiv($legalBalls, 6).'.'.($legalBalls % 6);
        $runRate = $legalBalls > 0 ? number_format($runs / ($legalBalls / 6), 2) : '0.00';

        return [
            'runs' => $runs,
            'wickets' => (int) $aggregate->wickets,
            'extras' => (int) $aggregate->extras,
            'legal_balls' => $legalBalls,
            'overs' => $overs,
            'run_rate' => $runRate,
        ];
    }

    public function matchProgress(CricketMatch $match): ?string
    {
        $innings = $match->innings()->orderBy('innings_number')->get();
        $firstInnings = $innings->first();

        if ($firstInnings === null) {
            return null;
        }

        $firstScore = $this->summary($firstInnings)['runs'];
        $secondInnings = $innings->firstWhere('innings_number', 2);

        if ($secondInnings === null) {
            return 'Target: '.($firstScore + 1).' runs';
        }

        $secondSummary = $this->summary($secondInnings);

        if ($secondInnings->status === 'completed') {
            if ($secondSummary['runs'] > $firstScore) {
                $wicketsRemaining = max(0, 10 - $secondSummary['wickets']);

                return $secondInnings->batting_team.' won by '.$wicketsRemaining.' wicket'.($wicketsRemaining === 1 ? '' : 's');
            }

            if ($secondSummary['runs'] === $firstScore) {
                return 'Match tied';
            }

            return $firstInnings->batting_team.' won by '.($firstScore - $secondSummary['runs']).' runs';
        }

        $runsRequired = max(0, $firstScore + 1 - $secondSummary['runs']);
        $ballsRemaining = max(0, ((int) $match->overs_limit * 6) - $secondSummary['legal_balls']);

        return $runsRequired.' run'.($runsRequired === 1 ? '' : 's').' needed from '.$ballsRemaining.' ball'.($ballsRemaining === 1 ? '' : 's');
    }

    /** @return EloquentCollection<int, ScoreDelivery> */
    public function recentDeliveries(CricketInnings $innings, int $limit = 20): EloquentCollection
    {
        $deliveries = $innings->deliveries()->orderBy('sequence_number')->get();
        $legalBalls = 0;

        foreach ($deliveries as $delivery) {
            $delivery->setAttribute(
                'over_ball',
                intdiv($legalBalls, 6).'.'.(($legalBalls % 6) + 1),
            );

            if ($delivery->legal_ball) {
                $legalBalls++;
            }
        }

        return $deliveries->take(-$limit)->values();
    }

    /**
     * @return array<int, array{number: int, balls: array<int, array{number: int, deliveries: array<int, ScoreDelivery>}>}>
     */
    public function overBreakdown(CricketInnings $innings, int $oversLimit): array
    {
        $deliveries = $innings->deliveries()->orderBy('sequence_number')->get();
        $overs = [];
        $legalBalls = 0;

        foreach ($deliveries as $delivery) {
            $overNumber = intdiv($legalBalls, 6) + 1;
            $ballNumber = ($legalBalls % 6) + 1;
            $overs[$overNumber] ??= ['number' => $overNumber, 'balls' => []];
            $overs[$overNumber]['balls'][$ballNumber] ??= ['number' => $ballNumber, 'deliveries' => []];
            $overs[$overNumber]['balls'][$ballNumber]['deliveries'][] = $delivery;

            if ($delivery->legal_ball) {
                $legalBalls++;
            }
        }

        $visibleOverCount = $innings->status === 'active'
            ? intdiv($legalBalls, 6) + 1
            : max(1, intdiv(max(0, $legalBalls - 1), 6) + 1);
        $visibleOverCount = min(max(1, $oversLimit), $visibleOverCount);
        $visibleOverCount = max($visibleOverCount, (int) (array_key_last($overs) ?? 1));

        for ($overNumber = 1; $overNumber <= $visibleOverCount; $overNumber++) {
            $overs[$overNumber] ??= ['number' => $overNumber, 'balls' => []];

            for ($ballNumber = 1; $ballNumber <= 6; $ballNumber++) {
                $overs[$overNumber]['balls'][$ballNumber] ??= ['number' => $ballNumber, 'deliveries' => []];
            }

            ksort($overs[$overNumber]['balls']);
        }

        ksort($overs);

        return $overs;
    }

    /** @param array<string, mixed> $data
     * @return array{batter_runs: int, extras_type: string, extra_runs: int, total_runs: int, is_wicket: bool, legal_ball: bool}
     */
    private function normalizeDelivery(array $data): array
    {
        $validated = Validator::make($data, [
            'batter_runs' => ['sometimes', 'integer', 'between:0,6'],
            'extras_type' => ['required', Rule::in(self::EXTRAS_TYPES)],
            'extra_runs' => ['sometimes', 'integer', 'between:0,6'],
            'is_wicket' => ['sometimes', 'boolean'],
        ])->validate();

        $batterRuns = (int) ($validated['batter_runs'] ?? 0);
        $extrasType = $validated['extras_type'];
        $extraRuns = (int) ($validated['extra_runs'] ?? 0);
        $isWicket = (bool) ($validated['is_wicket'] ?? false);

        if ($extrasType === 'none' && $extraRuns !== 0) {
            throw ValidationException::withMessages(['extra_runs' => 'A regular delivery cannot include extras.']);
        }

        if ($extrasType === 'wide' && ($batterRuns !== 0 || $extraRuns < 1)) {
            throw ValidationException::withMessages(['extra_runs' => 'A wide must include at least one extra run and no batter runs.']);
        }

        if ($extrasType === 'no_ball' && $extraRuns !== 1) {
            throw ValidationException::withMessages(['extra_runs' => 'A no-ball includes one penalty extra.']);
        }

        if (in_array($extrasType, ['bye', 'leg_bye'], true) && ($batterRuns !== 0 || $extraRuns < 1)) {
            throw ValidationException::withMessages(['extra_runs' => 'A bye or leg-bye must include at least one extra run and no batter runs.']);
        }

        if ($extrasType === 'no_ball' && $isWicket) {
            throw ValidationException::withMessages(['is_wicket' => 'Choose a regular delivery for this wicket.']);
        }

        $legalBall = ! in_array($extrasType, ['wide', 'no_ball'], true);

        return [
            'batter_runs' => $batterRuns,
            'extras_type' => $extrasType,
            'extra_runs' => $extrasType === 'no_ball' ? 1 : $extraRuns,
            'total_runs' => $batterRuns + ($extrasType === 'no_ball' ? 1 : $extraRuns),
            'is_wicket' => $isWicket,
            'legal_ball' => $legalBall,
        ];
    }

    private function ensureWritable(CricketInnings $innings, bool $adding): void
    {
        if ($innings->locked_at !== null) {
            throw ValidationException::withMessages(['score' => 'This innings is locked. Unlock it before changing the score.']);
        }

        if ($adding && $innings->status !== 'active') {
            throw ValidationException::withMessages(['score' => 'This innings is complete. Start the next innings or edit an existing delivery.']);
        }
    }

    private function ensureDeliveryBelongsToInnings(CricketInnings $innings, ScoreDelivery $delivery): void
    {
        if ($delivery->cricket_innings_id !== $innings->id) {
            throw ValidationException::withMessages(['delivery' => 'That delivery does not belong to the selected innings.']);
        }
    }

    private function refreshInningsStatus(CricketInnings $innings): void
    {
        $summary = $this->summary($innings);
        $match = $innings->match()->firstOrFail();
        $firstInningsRuns = $innings->innings_number === 2
            ? (int) $match->innings()->where('innings_number', 1)->firstOrFail()->deliveries()->sum('total_runs')
            : 0;
        $reachedTarget = $innings->innings_number === 2 && $summary['runs'] > $firstInningsRuns;
        $isComplete = $reachedTarget
            || $summary['legal_balls'] >= ((int) $match->overs_limit * 6)
            || $summary['wickets'] >= 10;

        $innings->status = $isComplete ? 'completed' : 'active';
        $innings->completed_at = $isComplete ? ($innings->completed_at ?? now()) : null;
        $innings->save();

        if ($innings->innings_number === 2) {
            $match->update(['status' => $isComplete ? 'completed' : 'in_progress']);
        }
    }

    /** @return array<string, int|string|bool> */
    private function deliveryState(ScoreDelivery $delivery): array
    {
        return [
            'delivery_id' => $delivery->id,
            'sequence_number' => $delivery->sequence_number,
            'batter_runs' => $delivery->batter_runs,
            'extras_type' => $delivery->extras_type,
            'extra_runs' => $delivery->extra_runs,
            'total_runs' => $delivery->total_runs,
            'is_wicket' => $delivery->is_wicket,
            'legal_ball' => $delivery->legal_ball,
        ];
    }

    /** @param array<string, mixed>|null $before
     * @param  array<string, mixed>|null  $after
     */
    private function recordAudit(
        CricketInnings $innings,
        string $eventType,
        ?array $before,
        ?array $after,
        ?int $deliveryId = null,
    ): void {
        ScoreAudit::query()->create([
            'cricket_innings_id' => $innings->id,
            'score_delivery_id' => $deliveryId,
            'event_type' => $eventType,
            'before_state' => $before,
            'after_state' => $after,
            'created_at' => now(),
        ]);
    }
}
