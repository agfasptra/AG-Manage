<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'target_wallet_id',
        'category_id',
        'type',
        'amount',
        'transaction_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    public function targetWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'target_wallet_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Format amount as Indonesian Rupiah.
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'Rp'.number_format((float) $this->amount, 0, ',', '.');
    }

    /**
     * Get transaction type label in Indonesian.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'income' => 'Pemasukan',
            'expense' => 'Pengeluaran',
            'transfer' => 'Transfer',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get Lucide icon for transaction type.
     */
    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'income' => 'arrow-down-left',
            'expense' => 'arrow-up-right',
            'transfer' => 'arrow-left-right',
            default => 'receipt',
        };
    }

    /**
     * Check if transaction is income.
     */
    public function getIsIncomeAttribute(): bool
    {
        return $this->type === 'income';
    }

    /**
     * Check if transaction is expense.
     */
    public function getIsExpenseAttribute(): bool
    {
        return $this->type === 'expense';
    }

    /**
     * Check if transaction is transfer.
     */
    public function getIsTransferAttribute(): bool
    {
        return $this->type === 'transfer';
    }

    /**
     * Scope: filter transactions by criteria.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['type'] ?? null, function (Builder $q, string $type) {
                if (in_array($type, ['income', 'expense', 'transfer'])) {
                    $q->where('type', $type);
                }
            })
            ->when($filters['wallet_id'] ?? null, function (Builder $q, $walletId) {
                $q->where(function (Builder $sub) use ($walletId) {
                    $sub->where('wallet_id', $walletId)
                        ->orWhere('target_wallet_id', $walletId);
                });
            })
            ->when($filters['category_id'] ?? null, function (Builder $q, $categoryId) {
                $q->where('category_id', $categoryId);
            })
            ->when($filters['start_date'] ?? null, function (Builder $q, string $startDate) {
                $q->whereDate('transaction_date', '>=', $startDate);
            })
            ->when($filters['end_date'] ?? null, function (Builder $q, string $endDate) {
                $q->whereDate('transaction_date', '<=', $endDate);
            })
            ->when($filters['search'] ?? null, function (Builder $q, string $search) {
                $q->where('notes', 'like', '%'.$search.'%');
            });
    }

    /**
     * Scope: income transactions.
     */
    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', 'income');
    }

    /**
     * Scope: expense transactions.
     */
    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('type', 'expense');
    }

    /**
     * Scope: transfer transactions.
     */
    public function scopeTransfer(Builder $query): Builder
    {
        return $query->where('type', 'transfer');
    }
}
