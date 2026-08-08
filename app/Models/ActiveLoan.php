<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActiveLoan extends Model
{
    protected $fillable = [
        'loan_application_id', 'principal_amount', 'interest_rate',
        'term_months', 'monthly_payment', 'start_date', 'end_date', 'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function loanApplication(): BelongsTo
    {
        // loan_applications' PK ay application_id, kaya specific tayo dito
        return $this->belongsTo(LoanApplication::class, 'loan_application_id', 'application_id');
    }

    public function paymentSchedules(): HasMany
    {
        return $this->hasMany(LoanPaymentSchedule::class);
    }

    public function penalties(): HasMany
    {
        return $this->hasMany(LoanPenalty::class);
    }
}