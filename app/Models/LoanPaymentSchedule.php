<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LoanPaymentSchedule extends Model
{
    protected $fillable = [
        'active_loan_id', 'installment_no', 'due_date',
        'amount_due', 'amount_paid', 'paid_at', 'status',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function activeLoan(): BelongsTo
    {
        return $this->belongsTo(ActiveLoan::class);
    }

    public function penalty(): HasOne
    {
        return $this->hasOne(LoanPenalty::class);
    }

    // Ilang araw na late — 0 kung hindi pa naman late.
    public function daysLate(): int
    {
        if ($this->status === 'paid') {
            return 0;
        }
        return max(0, now()->diffInDays($this->due_date, false) * -1);
    }
}