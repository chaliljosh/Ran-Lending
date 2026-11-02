<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\AdminController;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Ensures every page using admin.layout has the header data
        // (profile dropdown, notification bell) available, even when
        // the controller method rendering that page didn't explicitly
        // pass adminName/notifications/etc. itself. Only fills in
        // MISSING keys — if a controller already passed these (like
        // dashboard() does), those values are kept as-is, not overwritten.
        View::composer('admin.layout', function ($view) {
            $adminId = Session::get('user_id');
            if (!$adminId) {
                return;
            }

            $existingKeys = array_keys($view->getData());
            $headerData = app(AdminController::class)->getHeaderData($adminId);

            $missingData = array_diff_key($headerData, array_flip($existingKeys));

            if (!empty($missingData)) {
                $view->with($missingData);
            }

            // Bilang para sa "Needs Attention" sa sidebar (Loan Applications).
            // PAREHONG rules ng admin_loan_applications_list_data() sa database:
            //   - status = 'pending'
            //   - hindi pa nakaboto ang admin na ito
            //   - 3 araw o higit nang naghihintay (galing sa applied_at)
            // Isang mabilis na COUNT lang, walang cache, para laging tama ang bilang
            // pagkatapos mag-desisyon ng admin.
            $needsAttentionCount = DB::table('loan_applications as la')
                ->where('la.status', 'pending')
                ->whereRaw("la.applied_at <= now() - interval '3 days'")
                ->whereNotExists(function ($q) use ($adminId) {
                    $q->select(DB::raw(1))
                        ->from('loan_approvals as lap')
                        ->whereColumn('lap.application_id', 'la.application_id')
                        ->where('lap.admin_id', $adminId);
                })
                ->count();

            $view->with('needsAttentionCount', $needsAttentionCount);

            // Bilang para sa "Waiting for Release" sa sidebar.
            // PAREHONG rules ng AdminController::waitingReleaseQuery():
            //   - status = 'approved' o 'active'
            //   - wala pang proof_sent_at (hindi pa na-release)
            // Walang cache, kaya agad nawawala ang bilang pagkatapos mag-release.
            $waitingReleaseCount = DB::table('loan_applications')
                ->whereIn('status', ['approved', 'active'])
                ->whereNull('proof_sent_at')
                ->count();

            $view->with('waitingReleaseCount', $waitingReleaseCount);

            // Bilang para sa "On Hold" sa sidebar (Loan Applications).
            // Lahat ng application na naka-on_hold ang status.
            // Walang cache, kaya agad nag-a-update pagkatapos mag-hold o mag-lift ng hold.
            $onHoldLoanCount = DB::table('loan_applications')
                ->where('status', 'on_hold')
                ->count();

            $view->with('onHoldLoanCount', $onHoldLoanCount);

            // Bilang para sa "Overdue Payments" sa sidebar (Ongoing Loans).
            // PAREHONG function ng AdminController::overduePayments(), kaya laging
            // magkapareho ang badge at ang laman ng page.
            $overdueRpc = DB::selectOne(
                'SELECT admin_overdue_payments_data(?) as data',
                [AdminController::DEFAULT_DAYS_LATE]
            );
            $overdueData = $overdueRpc->data ?? null;
            if (is_string($overdueData)) {
                $overdueData = json_decode($overdueData);
            }
            $overdueLoanCount = count($overdueData->overdue_loans ?? []);

            $view->with('overdueLoanCount', $overdueLoanCount);
        });

        // Borrower sidebar: true kung may ongoing loan ang borrower, para
        // itago ang "Apply for a Loan". Parehong statuses ang gamit sa
        // applyLoan() at submitLoanApplication() ng BorrowerController.
        View::composer('borrower.partials.sidebar', function ($view) {
            $userId = Session::get('user_id');

            $hasOngoingLoan = $userId
                ? DB::table('loan_applications')
                    ->where('user_id', $userId)
                    ->whereIn('status', ['pending', 'approved', 'active', 'on_hold'])
                    ->exists()
                : false;

            $view->with('hasOngoingLoan', $hasOngoingLoan);
        });
    }
}