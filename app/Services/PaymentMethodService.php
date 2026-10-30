<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Read helpers for the Admin-Assigned Payment Method feature.
 * Used by the admin Ongoing Loan Detail page and (next) the borrower Loan Details page.
 */
class PaymentMethodService
{
    /** Methods that can be NEWLY assigned (active only). */
    public static function activeMethods()
    {
        return DB::table('payment_methods')
            ->where('is_active', true)
            ->orderBy('provider')
            ->orderBy('account_label')
            ->get();
    }

    /**
     * The method currently assigned to a loan (even if it was deactivated
     * afterwards), or null when nothing is assigned.
     */
    public static function forLoan($applicationId)
    {
        return DB::table('loan_applications as la')
            ->join('payment_methods as pm', 'pm.id', '=', 'la.payment_method_id')
            ->where('la.application_id', $applicationId)
            ->select(
                'pm.id',
                'pm.provider',
                'pm.account_label',
                'pm.account_name',
                'pm.account_number',
                'pm.instructions',
                'pm.is_active'
            )
            ->first();
    }
}