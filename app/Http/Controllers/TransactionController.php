<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TransactionController extends Controller
{
    /**
     * Display a listing of user's transactions with search, filters, and summary.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $wallets = $user->wallets()->orderBy('name')->get();
        $categories = Category::accessibleBy($user->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $filters = $request->only(['type', 'wallet_id', 'category_id', 'start_date', 'end_date', 'search']);

        $baseQuery = $user->transactions()->filter($filters);

        // Calculate summary cards from the filtered dataset
        // PRD Rule: Transfers must not be counted as income or expense
        $totalIncome = (clone $baseQuery)->where('type', 'income')->sum('amount');
        $totalExpense = (clone $baseQuery)->where('type', 'expense')->sum('amount');
        $totalTransfer = (clone $baseQuery)->where('type', 'transfer')->sum('amount');
        $transferCount = (clone $baseQuery)->where('type', 'transfer')->count();

        $netCashFlow = bcsub((string) $totalIncome, (string) $totalExpense, 2);
        $netFloat = (float) $netCashFlow;

        $formattedIncome = 'Rp'.number_format((float) $totalIncome, 0, ',', '.');
        $formattedExpense = 'Rp'.number_format((float) $totalExpense, 0, ',', '.');
        $formattedTransfer = 'Rp'.number_format((float) $totalTransfer, 0, ',', '.');
        $formattedNet = ($netFloat < 0 ? '-Rp' : 'Rp').number_format(abs($netFloat), 0, ',', '.');

        $transactions = (clone $baseQuery)
            ->with(['wallet', 'targetWallet', 'category'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('transactions.index', compact(
            'transactions',
            'wallets',
            'categories',
            'filters',
            'totalIncome',
            'totalExpense',
            'totalTransfer',
            'transferCount',
            'netCashFlow',
            'formattedIncome',
            'formattedExpense',
            'formattedTransfer',
            'formattedNet'
        ));
    }

    /**
     * Show the form for creating a new transaction.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        $activeWallets = $user->wallets()->active()->orderBy('name')->get();

        if ($activeWallets->isEmpty()) {
            return redirect()
                ->route('wallets.create')
                ->with('warning', 'Silakan buat dompet terlebih dahulu sebelum mencatat transaksi.');
        }

        $incomeCategories = Category::accessibleBy($user->id)
            ->where('type', 'income')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $expenseCategories = Category::accessibleBy($user->id)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $selectedType = $request->query('type', 'expense');
        if (! in_array($selectedType, ['income', 'expense', 'transfer'])) {
            $selectedType = 'expense';
        }

        $selectedWalletId = $request->query('wallet_id');
        $selectedTargetWalletId = $request->query('target_wallet_id');
        $todayDate = now()->toDateString();

        return view('transactions.create', compact(
            'activeWallets',
            'incomeCategories',
            'expenseCategories',
            'selectedType',
            'selectedWalletId',
            'selectedTargetWalletId',
            'todayDate'
        ));
    }

    /**
     * Store a newly created transaction in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $this->validateTransaction($request, $user->id);

        $transaction = DB::transaction(function () use ($user, $validated, $request): Transaction {
            return Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $validated['wallet_id'],
                'target_wallet_id' => $request->type === 'transfer' ? $validated['target_wallet_id'] : null,
                'category_id' => $request->type === 'transfer' ? null : $validated['category_id'],
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'transaction_date' => $validated['transaction_date'],
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        $message = match ($transaction->type) {
            'income' => 'Pemasukan berhasil dicatat.',
            'expense' => 'Pengeluaran berhasil dicatat.',
            'transfer' => 'Transfer antar-dompet berhasil dicatat.',
            default => 'Transaksi berhasil dicatat.',
        };

        return redirect()->route('transactions.index')->with('success', $message);
    }

    /**
     * Display the specified transaction (redirects to edit).
     */
    public function show(Transaction $transaction): RedirectResponse
    {
        Gate::authorize('view', $transaction);

        return redirect()->route('transactions.edit', $transaction);
    }

    /**
     * Show the form for editing the specified transaction.
     */
    public function edit(Transaction $transaction, Request $request): View
    {
        Gate::authorize('update', $transaction);

        $user = $request->user();

        // Include current transaction wallets even if archived
        $wallets = $user->wallets()
            ->where(function ($query) use ($transaction) {
                $query->where('is_archived', false)
                    ->orWhere('id', $transaction->wallet_id)
                    ->orWhere('id', $transaction->target_wallet_id);
            })
            ->orderBy('name')
            ->get();

        // Include current category even if deactivated
        $incomeCategories = Category::accessibleBy($user->id)
            ->where('type', 'income')
            ->where(function ($query) use ($transaction) {
                $query->where('is_active', true)
                    ->orWhere('id', $transaction->category_id);
            })
            ->orderBy('name')
            ->get();

        $expenseCategories = Category::accessibleBy($user->id)
            ->where('type', 'expense')
            ->where(function ($query) use ($transaction) {
                $query->where('is_active', true)
                    ->orWhere('id', $transaction->category_id);
            })
            ->orderBy('name')
            ->get();

        return view('transactions.edit', compact(
            'transaction',
            'wallets',
            'incomeCategories',
            'expenseCategories'
        ));
    }

    /**
     * Update the specified transaction in storage.
     */
    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        Gate::authorize('update', $transaction);

        $user = $request->user();

        $validated = $this->validateTransaction($request, $user->id, $transaction);

        DB::transaction(function () use ($transaction, $validated, $request): void {
            $transaction->update([
                'wallet_id' => $validated['wallet_id'],
                'target_wallet_id' => $request->type === 'transfer' ? $validated['target_wallet_id'] : null,
                'category_id' => $request->type === 'transfer' ? null : $validated['category_id'],
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'transaction_date' => $validated['transaction_date'],
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil diperbarui.');
    }

    /**
     * Remove the specified transaction from storage.
     */
    public function destroy(Transaction $transaction): RedirectResponse
    {
        Gate::authorize('delete', $transaction);

        DB::transaction(function () use ($transaction): void {
            $transaction->delete();
        });

        return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil dihapus.');
    }

    /**
     * Validate transaction input data with Indonesian error messages.
     *
     * @return array<string, mixed>
     */
    protected function validateTransaction(Request $request, int $userId, ?Transaction $existingTransaction = null): array
    {
        $isTransfer = $request->input('type') === 'transfer';

        $rules = [
            'type' => ['required', 'string', 'in:income,expense,transfer'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99'],
            'transaction_date' => ['required', 'date'],
            'wallet_id' => [
                'required',
                'integer',
                Rule::exists('wallets', 'id')->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                }),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        if ($isTransfer) {
            $rules['target_wallet_id'] = [
                'required',
                'integer',
                'different:wallet_id',
                Rule::exists('wallets', 'id')->where(function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                }),
            ];
            $rules['category_id'] = ['nullable'];
        } else {
            $rules['target_wallet_id'] = ['nullable'];
            $rules['category_id'] = [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) use ($userId, $request, $existingTransaction) {
                    $query->where(function ($sub) use ($userId) {
                        $sub->where('user_id', $userId)
                            ->orWhereNull('user_id');
                    })
                        ->where('type', $request->input('type'));

                    // Allow current category even if inactive
                    if ($existingTransaction && $existingTransaction->category_id) {
                        $query->where(function ($sub) use ($existingTransaction) {
                            $sub->where('is_active', true)
                                ->orWhere('id', $existingTransaction->category_id);
                        });
                    } else {
                        $query->where('is_active', true);
                    }
                }),
            ];
        }

        $messages = [
            'type.required' => 'Tipe transaksi wajib dipilih.',
            'type.in' => 'Tipe transaksi tidak valid.',
            'amount.required' => 'Jumlah uang wajib diisi.',
            'amount.numeric' => 'Jumlah uang harus berupa angka.',
            'amount.min' => 'Jumlah uang minimal Rp0,01.',
            'amount.max' => 'Jumlah uang melebihi batas maksimum.',
            'transaction_date.required' => 'Tanggal transaksi wajib diisi.',
            'transaction_date.date' => 'Format tanggal tidak valid.',
            'wallet_id.required' => 'Dompet wajib dipilih.',
            'wallet_id.exists' => 'Dompet yang dipilih tidak valid atau bukan milik Anda.',
            'target_wallet_id.required' => 'Dompet tujuan transfer wajib dipilih.',
            'target_wallet_id.exists' => 'Dompet tujuan tidak valid atau bukan milik Anda.',
            'target_wallet_id.different' => 'Dompet asal dan dompet tujuan transfer harus berbeda.',
            'category_id.required' => 'Kategori wajib dipilih untuk pemasukan dan pengeluaran.',
            'category_id.exists' => 'Kategori tidak valid atau tidak sesuai dengan tipe transaksi.',
            'notes.max' => 'Catatan maksimal 1000 karakter.',
        ];

        return $request->validate($rules, $messages);
    }
}
