<?php

namespace App\Http\Controllers;

use App\Models\SavingsGoal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SavingsGoalController extends Controller
{
    /**
     * Display a listing of user's savings goals.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $status = $request->query('status', 'all');

        $query = $user->savingsGoals()->with('contributions');

        if (in_array($status, ['active', 'paused', 'completed'])) {
            $query->where('status', $status);
        }

        $goals = $query->orderBy('target_date')->get();

        // Summary metrics across all goals
        $allGoals = $user->savingsGoals()->with('contributions')->get();
        $totalTarget = 0.00;
        $totalSaved = 0.00;
        $activeGoalsCount = 0;

        foreach ($allGoals as $g) {
            $totalTarget += (float) $g->target_amount;
            $totalSaved += $g->total_saved;
            if ($g->status === 'active') {
                $activeGoalsCount++;
            }
        }
        $totalRemaining = max($totalTarget - $totalSaved, 0);
        $overallPercentage = $totalTarget > 0 ? min(round(($totalSaved / $totalTarget) * 100, 1), 100) : 0;

        return view('savings-goals.index', compact(
            'goals',
            'status',
            'totalTarget',
            'totalSaved',
            'totalRemaining',
            'overallPercentage',
            'activeGoalsCount'
        ));
    }

    /**
     * Show the form for creating a new savings goal.
     */
    public function create(): View
    {
        return view('savings-goals.create');
    }

    /**
     * Store a newly created savings goal in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'target_amount' => ['required', 'numeric', 'min:1000', 'max:999999999999.99'],
            'target_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'Nama target tabungan wajib diisi.',
            'name.max' => 'Nama target tabungan maksimal 150 karakter.',
            'target_amount.required' => 'Target nominal tabungan wajib diisi.',
            'target_amount.numeric' => 'Target nominal harus berupa angka.',
            'target_amount.min' => 'Target nominal tabungan minimal Rp1.000.',
            'target_amount.max' => 'Target nominal melebihi batas maksimum.',
            'target_date.date' => 'Format tanggal target tidak valid.',
            'notes.max' => 'Catatan maksimal 1000 karakter.',
        ]);

        $goal = SavingsGoal::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'target_amount' => $validated['target_amount'],
            'target_date' => $validated['target_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'active',
        ]);

        return redirect()
            ->route('savings-goals.show', $goal)
            ->with('success', 'Target tabungan berhasil dibuat.');
    }

    /**
     * Display the specified savings goal and its contributions.
     */
    public function show(SavingsGoal $savingsGoal, Request $request): View
    {
        Gate::authorize('view', $savingsGoal);

        $savingsGoal->load(['contributions.wallet' => function ($q) {
            $q->select('id', 'name', 'type');
        }]);

        $contributions = $savingsGoal->contributions()
            ->with('wallet')
            ->orderByDesc('contribution_date')
            ->orderByDesc('id')
            ->paginate(15);

        $activeWallets = $request->user()->wallets()->active()->orderBy('name')->get();

        return view('savings-goals.show', compact('savingsGoal', 'contributions', 'activeWallets'));
    }

    /**
     * Show the form for editing the specified savings goal.
     */
    public function edit(SavingsGoal $savingsGoal): View
    {
        Gate::authorize('update', $savingsGoal);

        return view('savings-goals.edit', compact('savingsGoal'));
    }

    /**
     * Update the specified savings goal in storage.
     */
    public function update(Request $request, SavingsGoal $savingsGoal): RedirectResponse
    {
        Gate::authorize('update', $savingsGoal);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'target_amount' => ['required', 'numeric', 'min:1000', 'max:999999999999.99'],
            'target_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'string', 'in:active,paused,completed'],
        ], [
            'name.required' => 'Nama target tabungan wajib diisi.',
            'target_amount.required' => 'Target nominal tabungan wajib diisi.',
            'target_amount.min' => 'Target nominal tabungan minimal Rp1.000.',
            'status.required' => 'Status target tabungan wajib dipilih.',
            'status.in' => 'Status tidak valid.',
        ]);

        $savingsGoal->update($validated);

        return redirect()
            ->route('savings-goals.show', $savingsGoal)
            ->with('success', 'Target tabungan berhasil diperbarui.');
    }

    /**
     * Update status of savings goal directly.
     */
    public function toggleStatus(Request $request, SavingsGoal $savingsGoal): RedirectResponse
    {
        Gate::authorize('update', $savingsGoal);

        $request->validate([
            'status' => ['required', 'string', 'in:active,paused,completed'],
        ]);

        $savingsGoal->update([
            'status' => $request->input('status'),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Status target tabungan berhasil diubah.');
    }

    /**
     * Remove the specified savings goal from storage.
     */
    public function destroy(SavingsGoal $savingsGoal): RedirectResponse
    {
        Gate::authorize('delete', $savingsGoal);

        $savingsGoal->delete();

        return redirect()
            ->route('savings-goals.index')
            ->with('success', 'Target tabungan berhasil dihapus.');
    }
}
