<?php

namespace App\Http\Controllers;

use App\Models\CricketInnings;
use App\Models\CricketMatch;
use App\Models\ScoreDelivery;
use App\Services\CricketScoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ScorebookController extends Controller
{
    public function index(Request $request, CricketScoringService $scoring): View
    {
        $homeSection = $request->query('section', 'home');

        return $this->scorebook($scoring, homeSection: is_string($homeSection) ? $homeSection : 'home');
    }

    public function show(Request $request, CricketMatch $match, CricketScoringService $scoring): View
    {
        $requestedInningsNumber = $request->query('innings');
        $selectedInningsNumber = is_numeric($requestedInningsNumber) ? (int) $requestedInningsNumber : null;

        return $this->scorebook($scoring, $match, $selectedInningsNumber, 'home');
    }

    public function storeMatch(Request $request, CricketScoringService $scoring): RedirectResponse
    {
        $validated = $request->validate([
            'team_one' => ['required', 'string', 'max:100'],
            'team_two' => ['required', 'string', 'max:100', Rule::notIn([$request->input('team_one')])],
            'overs_limit' => ['required', 'integer', 'between:1,50'],
            'batting_first' => ['required', Rule::in(['team_one', 'team_two'])],
        ]);

        $match = $scoring->createMatch($validated);

        return to_route('scorebook.match', $match)->with('status', 'Match started. Every delivery is being saved to the score history.');
    }

    public function destroyMatch(CricketMatch $match, CricketScoringService $scoring): RedirectResponse
    {
        $scoring->deleteMatch($match);

        return to_route('scorebook.index')->with('status', 'Match and its innings, deliveries, and score history were deleted.');
    }

    public function storeDelivery(Request $request, CricketMatch $match, CricketScoringService $scoring): RedirectResponse
    {
        $validated = $request->validate([
            'batter_runs' => ['sometimes', 'integer', 'between:0,6'],
            'extras_type' => ['required', Rule::in(['none', 'wide', 'no_ball', 'bye', 'leg_bye'])],
            'extra_runs' => ['sometimes', 'integer', 'between:0,6'],
            'is_wicket' => ['sometimes', 'boolean'],
        ]);

        $innings = $match->innings()->orderByDesc('innings_number')->firstOrFail();
        $scoring->recordDelivery($innings, $validated);

        return to_route('scorebook.match', $match)->with('status', 'Delivery saved.');
    }

    public function updateDelivery(Request $request, CricketMatch $match, ScoreDelivery $delivery, CricketScoringService $scoring): RedirectResponse
    {
        abort_unless($match->innings()->whereKey($delivery->cricket_innings_id)->exists(), 404);

        $validated = $request->validate([
            'batter_runs' => ['sometimes', 'integer', 'between:0,6'],
            'extras_type' => ['required', Rule::in(['none', 'wide', 'no_ball', 'bye', 'leg_bye'])],
            'extra_runs' => ['sometimes', 'integer', 'between:0,6'],
            'is_wicket' => ['sometimes', 'boolean'],
        ]);

        $scoring->editDelivery($delivery->innings, $delivery, $validated);

        return to_route('scorebook.match', [
            'match' => $match,
            'innings' => $delivery->innings->innings_number,
        ])->with('status', 'Delivery corrected. The change is in the score history.');
    }

    public function destroyDelivery(CricketMatch $match, ScoreDelivery $delivery, CricketScoringService $scoring): RedirectResponse
    {
        abort_unless($match->innings()->whereKey($delivery->cricket_innings_id)->exists(), 404);

        $innings = $delivery->innings;
        $scoring->deleteDelivery($innings, $delivery);

        return to_route('scorebook.match', [
            'match' => $match,
            'innings' => $innings->innings_number,
        ])->with('status', 'Delivery removed and saved to the score history.');
    }

    public function toggleLock(CricketMatch $match, CricketInnings $innings, CricketScoringService $scoring): RedirectResponse
    {
        abort_unless($innings->cricket_match_id === $match->id, 404);

        $locked = $scoring->toggleLock($innings);

        return to_route('scorebook.match', [
            'match' => $match,
            'innings' => $innings->innings_number,
        ])->with('status', $locked ? 'Innings locked.' : 'Innings unlocked. The change is in the score history.');
    }

    public function startSecondInnings(CricketMatch $match, CricketScoringService $scoring): RedirectResponse
    {
        $scoring->startSecondInnings($match);

        return to_route('scorebook.match', $match)->with('status', 'Second innings started. The first innings has been locked.');
    }

    private function scorebook(
        CricketScoringService $scoring,
        ?CricketMatch $selectedMatch = null,
        ?int $selectedInningsNumber = null,
        string $homeSection = 'home',
    ): View {
        $matches = CricketMatch::query()
            ->with(['innings' => fn ($query) => $query->orderByDesc('innings_number')])
            ->latest()
            ->limit(12)
            ->get();

        $homeSection = in_array($homeSection, ['home', 'new', 'old'], true) ? $homeSection : 'home';

        $innings = $selectedMatch?->innings()
            ->when($selectedInningsNumber !== null, fn ($query) => $query->where('innings_number', $selectedInningsNumber))
            ->orderByDesc('innings_number')
            ->first();
        $summary = $innings !== null ? $scoring->summary($innings) : null;
        $matchProgress = $selectedMatch !== null ? $scoring->matchProgress($selectedMatch) : null;
        $deliveries = $innings !== null ? $scoring->recentDeliveries($innings, 500) : null;
        $overBreakdown = $innings !== null
            ? $scoring->overBreakdown($innings, (int) $selectedMatch->overs_limit)
            : [];
        $audits = $innings?->audits()->latest('created_at')->limit(12)->get();
        $inningsSummaries = $selectedMatch?->innings()->orderBy('innings_number')->get()
            ->map(fn (CricketInnings $matchInnings): array => [
                'innings' => $matchInnings,
                'summary' => $scoring->summary($matchInnings),
            ]);
        $canStartSecondInnings = $innings !== null
            && $innings->innings_number === 1
            && $innings->status === 'completed'
            && ! $selectedMatch->innings()->where('innings_number', 2)->exists();

        return view('scorebook.index', compact(
            'matches',
            'selectedMatch',
            'homeSection',
            'innings',
            'summary',
            'matchProgress',
            'deliveries',
            'overBreakdown',
            'audits',
            'inningsSummaries',
            'canStartSecondInnings',
        ));
    }
}
