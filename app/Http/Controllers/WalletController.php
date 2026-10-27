<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class WalletController extends Controller
{
    /**
     * Display a listing of the user's wallets.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $activeWallets = $user->wallets()->active()->orderBy('name')->get();
        $archivedWallets = $user->wallets()->archived()->orderBy('name')->get();

        // Calculate total balance across all active wallets
        $totalBalance = $activeWallets->reduce(function ($carry, $wallet) {
            return bcadd($carry, $wallet->current_balance, 2);
        }, '0.00');

        $formattedTotalBalance = 'Rp'.number_format((float) $totalBalance, 0, ',', '.');

        return view('wallets.index', compact(
            'activeWallets',
            'archivedWallets',
            'totalBalance',
            'formattedTotalBalance'
        ));
    }

    /**
     * Show the form for creating a new wallet.
     */
    public function create(): View
    {
        return view('wallets.create');
    }

    /**
     * Store a newly created wallet.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:cash,bank,ewallet,other'],
            'opening_balance' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Nama dompet wajib diisi.',
            'name.max' => 'Nama dompet maksimal 100 karakter.',
            'type.required' => 'Jenis dompet wajib dipilih.',
            'type.in' => 'Jenis dompet tidak valid.',
            'opening_balance.required' => 'Saldo awal wajib diisi.',
            'opening_balance.numeric' => 'Saldo awal harus berupa angka.',
            'opening_balance.min' => 'Saldo awal tidak boleh negatif.',
            'opening_balance.max' => 'Saldo awal melebihi batas.',
            'description.max' => 'Deskripsi maksimal 500 karakter.',
        ]);

        $request->user()->wallets()->create($validated);

        return redirect()->route('wallets.index')
            ->with('success', 'Dompet berhasil dibuat!');
    }

    /**
     * Display the specified wallet.
     */
    public function show(Wallet $wallet): View
    {
        Gate::authorize('view', $wallet);

        $recentTransactions = $wallet->transactions()
            ->with('category')
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $incomingTransfers = $wallet->incomingTransfers()
            ->with('wallet')
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('wallets.show', compact('wallet', 'recentTransactions', 'incomingTransfers'));
    }

    /**
     * Show the form for editing the specified wallet.
     */
    public function edit(Wallet $wallet): View
    {
        Gate::authorize('update', $wallet);

        return view('wallets.edit', compact('wallet'));
    }

    /**
     * Update the specified wallet.
     */
    public function update(Request $request, Wallet $wallet): RedirectResponse
    {
        Gate::authorize('update', $wallet);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:cash,bank,ewallet,other'],
            'opening_balance' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Nama dompet wajib diisi.',
            'name.max' => 'Nama dompet maksimal 100 karakter.',
            'type.required' => 'Jenis dompet wajib dipilih.',
            'type.in' => 'Jenis dompet tidak valid.',
            'opening_balance.required' => 'Saldo awal wajib diisi.',
            'opening_balance.numeric' => 'Saldo awal harus berupa angka.',
            'opening_balance.min' => 'Saldo awal tidak boleh negatif.',
            'opening_balance.max' => 'Saldo awal melebihi batas.',
            'description.max' => 'Deskripsi maksimal 500 karakter.',
        ]);

        $wallet->update($validated);

        return redirect()->route('wallets.show', $wallet)
            ->with('success', 'Dompet berhasil diperbarui!');
    }

    /**
     * Archive or restore a wallet.
     */
    public function toggleArchive(Wallet $wallet): RedirectResponse
    {
        Gate::authorize('archive', $wallet);

        $wallet->update(['is_archived' => ! $wallet->is_archived]);

        $message = $wallet->is_archived
            ? 'Dompet berhasil diarsipkan.'
            : 'Dompet berhasil diaktifkan kembali.';

        return redirect()->route('wallets.index')
            ->with('success', $message);
    }

    /**
     * Remove the specified wallet (only if no transactions).
     */
    public function destroy(Wallet $wallet): RedirectResponse
    {
        Gate::authorize('delete', $wallet);

        $wallet->delete();

        return redirect()->route('wallets.index')
            ->with('success', 'Dompet berhasil dihapus.');
    }
}
