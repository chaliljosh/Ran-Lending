<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanPenalty extends Model
{
    protected $fillable = [
        'loan_payment_schedule_id', 'active_loan_id', 'days_late',
        'late_fee_periods', 'penalty_amount', 'status', 'waived_by', 'waived_reason',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(LoanPaymentSchedule::class, 'loan_payment_schedule_id');
    }

    public function activeLoan(): BelongsTo
    {
        return $this->belongsTo(ActiveLoan::class);
    }
}