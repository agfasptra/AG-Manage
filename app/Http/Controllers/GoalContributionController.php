<?php

namespace App\Http\Controllers;

use App\Models\GoalContribution;
use App\Models\SavingsGoal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class GoalContributionController extends Controller
{
    /**
     * Store a new contribution towards a savings goal.
     */
    public function store(Request $request, SavingsGoal $savingsGoal): RedirectResponse
    {
        Gate::authorize('view', $savingsGoal);

        $user = $request->user();

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1000', 'max:999999999999.99'],
            'contribution_date' => ['required', 'date'],
            'wallet_id' => [
                'nullable',
                'integer',
                Rule::exists('wallets', 'id')->where('user_id', $user->id),
            ],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'amount.required' => 'Nominal alokasi tabungan wajib diisi.',
            'amount.numeric' => 'Nominal alokasi harus berupa angka.',
            'amount.min' => 'Nominal alokasi minimal Rp1.000.',
            'contribution_date.required' => 'Tanggal alokasi tabungan wajib diisi.',
            'contribution_date.date' => 'Format tanggal alokasi tidak valid.',
            'wallet_id.exists' => 'Dompet sumber tidak valid atau bukan milik Anda.',
            'notes.max' => 'Catatan maksimal 500 karakter.',
        ]);

        GoalContribution::create([
            'user_id' => $user->id,
            'savings_goal_id' => $savingsGoal->id,
            'wallet_id' => $validated['wallet_id'] ?? null,
            'amount' => $validated['amount'],
            'contribution_date' => $validated['contribution_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('savings-goals.show', $savingsGoal)
            ->with('success', 'Alokasi tabungan berhasil ditambahkan.');
    }

    /**
     * Remove the specified contribution from storage.
     */
    public function destroy(GoalContribution $contribution): RedirectResponse
    {
        Gate::authorize('delete', $contribution);

        $savingsGoalId = $contribution->savings_goal_id;

        $contribution->delete();

        return redirect()
            ->route('savings-goals.show', $savingsGoalId)
            ->with('success', 'Riwayat alokasi tabungan berhasil dihapus.');
    }
}
