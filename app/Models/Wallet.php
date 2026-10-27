<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'opening_balance',
        'currency',
        'description',
        'is_archived',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'is_archived' => 'boolean',
        ];
    }

    /**
     * Canonical balance formula (PRD rule 3):
     * opening_balance + income - expenses + incoming_transfers - outgoing_transfers
     */
    public function getCurrentBalanceAttribute(): string
    {
        $income = $this->transactions()
            ->where('type', 'income')
            ->sum('amount');

        $expense = $this->transactions()
            ->where('type', 'expense')
            ->sum('amount');

        $outgoingTransfer = $this->transactions()
            ->where('type', 'transfer')
            ->sum('amount');

        $incomingTransfer = $this->incomingTransfers()
            ->where('type', 'transfer')
            ->sum('amount');

        $balance = (string) $this->opening_balance;
        $balance = bcadd($balance, (string) $income, 2);
        $balance = bcsub($balance, (string) $expense, 2);
        $balance = bcadd($balance, (string) $incomingTransfer, 2);
        $balance = bcsub($balance, (string) $outgoingTransfer, 2);

        return $balance;
    }

    /**
     * Format balance as Indonesian Rupiah with clean negative handling.
     */
    public function getFormattedBalanceAttribute(): string
    {
        $amount = (float) $this->current_balance;
        if ($amount < 0) {
            return '-Rp'.number_format(abs($amount), 0, ',', '.');
        }

        return 'Rp'.number_format($amount, 0, ',', '.');
    }

    /**
     * Format opening balance as Indonesian Rupiah.
     */
    public function getFormattedOpeningBalanceAttribute(): string
    {
        return 'Rp'.number_format((float) $this->opening_balance, 0, ',', '.');
    }

    /**
     * Scope: only active (non-archived) wallets.
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope: only archived wallets.
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Get wallet type label in Indonesian.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'cash' => 'Tunai',
            'bank' => 'Bank',
            'ewallet' => 'E-Wallet',
            default => 'Lainnya',
        };
    }

    /**
     * Get wallet type icon name (Lucide).
     */
    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'cash' => 'banknote',
            'bank' => 'landmark',
            'ewallet' => 'smartphone',
            default => 'wallet',
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'wallet_id');
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(Transaction::class, 'target_wallet_id');
    }

    public function goalContributions(): HasMany
    {
        return $this->hasMany(GoalContribution::class);
    }
}
