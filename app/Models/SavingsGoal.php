<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavingsGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'target_amount',
        'target_date',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'target_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(GoalContribution::class);
    }

    /**
     * Total amount saved through contributions.
     */
    public function getTotalSavedAttribute(): float
    {
        return (float) $this->contributions()->sum('amount');
    }

    /**
     * Formatted target amount.
     */
    public function getFormattedTargetAmountAttribute(): string
    {
        return 'Rp'.number_format((float) $this->target_amount, 0, ',', '.');
    }

    /**
     * Formatted saved amount.
     */
    public function getFormattedSavedAttribute(): string
    {
        return 'Rp'.number_format($this->total_saved, 0, ',', '.');
    }

    /**
     * Remaining amount to reach target.
     */
    public function getRemainingAmountAttribute(): float
    {
        return max((float) $this->target_amount - $this->total_saved, 0);
    }

    /**
     * Formatted remaining amount.
     */
    public function getFormattedRemainingAttribute(): string
    {
        return 'Rp'.number_format($this->remaining_amount, 0, ',', '.');
    }

    /**
     * Progress percentage, visually capped at 100% per PRD 5.8.
     */
    public function getProgressPercentageAttribute(): float
    {
        $target = (float) $this->target_amount;
        if ($target <= 0) {
            return 0;
        }

        return min(round(($this->total_saved / $target) * 100, 1), 100);
    }

    /**
     * Uncapped progress percentage.
     */
    public function getActualPercentageAttribute(): float
    {
        $target = (float) $this->target_amount;
        if ($target <= 0) {
            return 0;
        }

        return round(($this->total_saved / $target) * 100, 1);
    }

    /**
     * Surplus amount if saved exceeds target.
     */
    public function getSurplusAmountAttribute(): float
    {
        return max($this->total_saved - (float) $this->target_amount, 0);
    }

    /**
     * Formatted surplus amount.
     */
    public function getFormattedSurplusAttribute(): string
    {
        return 'Rp'.number_format($this->surplus_amount, 0, ',', '.');
    }

    /**
     * Check if goal target is reached.
     */
    public function getIsTargetReachedAttribute(): bool
    {
        return $this->total_saved >= (float) $this->target_amount && (float) $this->target_amount > 0;
    }

    /**
     * Status label in Indonesian.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Aktif',
            'paused' => 'Dijeda',
            'completed' => 'Selesai',
            default => ucfirst($this->status),
        };
    }

    /**
     * Scope: active goals.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: paused goals.
     */
    public function scopePaused(Builder $query): Builder
    {
        return $query->where('status', 'paused');
    }

    /**
     * Scope: completed goals.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }
}
