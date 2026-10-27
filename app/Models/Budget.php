<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'period_month',
        'period_year',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'period_month' => 'integer',
            'period_year' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Format amount as Indonesian Rupiah.
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'Rp'.number_format((float) $this->amount, 0, ',', '.');
    }

    /**
     * Get Indonesian month name.
     */
    public function getMonthNameAttribute(): string
    {
        return Carbon::createFromDate($this->period_year, $this->period_month, 1)->translatedFormat('F');
    }

    /**
     * Calculate spent amount from valid expense transactions for the category in this period.
     */
    public function getSpentAmountAttribute(): float
    {
        $startDate = Carbon::createFromDate($this->period_year, $this->period_month, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::createFromDate($this->period_year, $this->period_month, 1)->endOfMonth()->toDateString();

        return (float) Transaction::where('user_id', $this->user_id)
            ->where('category_id', $this->category_id)
            ->where('type', 'expense')
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate)
            ->sum('amount');
    }

    /**
     * Formatted spent amount.
     */
    public function getFormattedSpentAttribute(): string
    {
        return 'Rp'.number_format($this->spent_amount, 0, ',', '.');
    }

    /**
     * Calculate remaining amount.
     */
    public function getRemainingAmountAttribute(): float
    {
        return max((float) $this->amount - $this->spent_amount, 0);
    }

    /**
     * Formatted remaining amount.
     */
    public function getFormattedRemainingAttribute(): string
    {
        return 'Rp'.number_format($this->remaining_amount, 0, ',', '.');
    }

    /**
     * Calculate percentage of budget used.
     */
    public function getPercentageUsedAttribute(): float
    {
        $limit = (float) $this->amount;
        if ($limit <= 0) {
            return 0;
        }

        return round(($this->spent_amount / $limit) * 100, 1);
    }

    /**
     * Check if budget has reached warning threshold (>= 80% and < 100%).
     */
    public function getIsWarningAttribute(): bool
    {
        return $this->percentage_used >= 80 && $this->percentage_used < 100;
    }

    /**
     * Check if budget is exceeded (>= 100%).
     */
    public function getIsExceededAttribute(): bool
    {
        return $this->spent_amount >= (float) $this->amount && (float) $this->amount > 0;
    }
}
