<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BorrowerController;
use App\Http\Controllers\PaymentReceiptController;

Route::get('/', function () {
    $userProfilePictureUrl = null;

    if (session('user_id')) {
        $user = DB::table('users')->where('user_id', session('user_id'))->first();
        if ($user && !empty($user->profile_picture)) {
            $userProfilePictureUrl = Storage::disk('supabase')->url($user->profile_picture);
        }
    }

    return view('landing', compact('userProfilePictureUrl'));
});

// Legal pages — public, no auth required (linked from the registration
// form's consent checkbox, and from the site footer). Named under
// "legal." so they don't clash with the existing borrower.terms /
// borrower.privacy routes below.
Route::view('/privacy', 'legal.privacy')->name('legal.privacy');
Route::view('/terms', 'legal.terms')->name('legal.terms');
Route::view('/cookies', 'legal.cookies')->name('legal.cookies');
Route::view('/refunds', 'legal.refunds')->name('legal.refunds');

Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('login');
    })->name('login');

    Route::get('/register', function () {
        return view('register');
    })->name('register');

    Route::get('/forgot-password', function () {
        return view('forgot-password');
    })->name('forgot-password');
});

Route::post('/api/register',            [AuthController::class, 'register'])->middleware('throttle:5,10');
Route::post('/api/register/verify-otp', [AuthController::class, 'verifyRegisterOtp'])->middleware('throttle:5,10');
Route::post('/api/register/resend-otp', [AuthController::class, 'resendRegisterOtp'])->middleware('throttle:5,10');

Route::post('/api/login',               [AuthController::class, 'login']);
Route::post('/api/logout',              [AuthController::class, 'logout'])->name('logout');

Route::post('/api/forgot-password/send-otp', [ForgotPasswordController::class, 'sendOtp'])->middleware('throttle:5,10');
Route::post('/api/forgot-password/reset',    [ForgotPasswordController::class, 'reset'])->middleware('throttle:5,10');

// Borrower pages — Blade, protected by 'borrower' middleware
Route::middleware(['borrower', 'no-cache'])->group(function () {
    Route::get('/borrower/dashboard', [BorrowerController::class, 'dashboard'])->name('borrower.dashboard');
    Route::get('/borrower/dashboard/calendar', [BorrowerController::class, 'dashboardCalendar'])->name('borrower.dashboard.calendar');
    Route::get('/dashboard-calendar-data', [BorrowerController::class, 'dashboardCalendarData'])
    ->name('borrower.dashboard-calendar-data');
    Route::get('/borrower/personal-details', [BorrowerController::class, 'personalDetails'])->name('borrower.personal-details');
    Route::get('/borrower/apply-loan', [BorrowerController::class, 'applyLoan'])->name('borrower.apply-loan');
    Route::get('/borrower/loan-details', [BorrowerController::class, 'loanDetails'])->name('borrower.loan-details');

    // Opens the borrower's own uploaded receipt (redirects to a short-lived signed URL)
    Route::get('/borrower/payment-receipt/{receipt}/view', [PaymentReceiptController::class, 'view'])
        ->whereNumber('receipt')
        ->name('borrower.payment-receipt.view');

    // Opens the official receipt the admin recorded for a PAID installment
    Route::get('/borrower/installments/{id}/receipt', [BorrowerController::class, 'installmentReceipt'])
        ->whereNumber('id')
        ->name('borrower.installment-receipt');

    Route::get('/borrower/notifications', [BorrowerController::class, 'notifications'])->name('borrower.notifications');
    Route::get('/borrower/profile', [BorrowerController::class, 'profile'])->name('borrower.profile');
    Route::get('/borrower/faq', [BorrowerController::class, 'faq'])->name('borrower.faq');
    Route::get('/borrower/terms', [BorrowerController::class, 'terms'])->name('borrower.terms');
Route::get('/borrower/privacy', [BorrowerController::class, 'privacy'])->name('borrower.privacy');
    Route::get('/notifications/open/{key}', [BorrowerController::class, 'openNotification'])
    ->name('borrower.notification-open');
Route::post('/notifications/mark-all-read', [BorrowerController::class, 'markAllNotificationsRead'])
    ->name('borrower.notifications.mark-all-read');
});

// Borrower API — save actions, protected by 'borrower' middleware.
// Dagdag na 'throttle:20,1' dito (20 requests/minute) — mas mataas
// kaysa sa login/register (5,10) dahil naka-login na sila at hindi
// brute-forceable, pero pumipigil pa rin sa abuse/spam (hal. paulit-
// ulit na profile picture upload) o sa isang na-compromise na session
// na mag-mass-abuse ng mga endpoints na 'to.
Route::middleware(['borrower', 'no-cache', 'throttle:20,1'])->prefix('api/borrower')->group(function () {
    Route::post('/personal-details', [BorrowerController::class, 'savePersonalDetails']);
    Route::post('/apply-loan', [BorrowerController::class, 'submitLoanApplication']);
    Route::post('/payment-receipt', [PaymentReceiptController::class, 'submit']);
    Route::post('/profile/update-section', [BorrowerController::class, 'updateProfileSection']);
    Route::post('/profile/change-password', [BorrowerController::class, 'changePassword']);
    Route::post('/profile/upload-picture', [BorrowerController::class, 'updateProfilePicture'])->name('borrower.profile.upload-picture');
    Route::post('/profile/remove-picture', [BorrowerController::class, 'removeProfilePicture'])->name('borrower.profile.remove-picture');
});

// Admin pages — 'throttle:60,1' (60 requests/minute) dito, mas
// mataas kaysa sa borrower API dahil kasama rito ang normal na page
// browsing (GET requests), hindi lang ang mutating actions.
Route::middleware(['admin', 'no-cache', 'throttle:60,1'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // Notifications (header bell dropdown)
       Route::get('/notifications/{key}/open', [AdminController::class, 'notificationOpen'])
        ->name('notification-open');
    Route::post('/notifications/mark-all-read', [AdminController::class, 'notificationsMarkAllRead'])
        ->name('notifications-mark-all-read');
            Route::get('/notifications', [AdminController::class, 'notificationsIndex'])
        ->name('notifications');

    // Account Settings (header profile dropdown)
    Route::get('/account-settings', [AdminController::class, 'accountSettings'])->name('account-settings');
    Route::post('/account-settings/password', [AdminController::class, 'updateAccountPassword'])->name('account-settings.password');
    Route::post('/account-settings/profile-picture', [AdminController::class, 'updateProfilePicture'])->name('account-settings.profile-picture');
Route::post('/account-settings/profile-picture/remove', [AdminController::class, 'removeProfilePicture'])->name('account-settings.profile-picture.remove');

    Route::match(['get', 'post'], '/loan-applications', [AdminController::class, 'loanApplications'])->name('loan-applications');
    Route::match(['get', 'post'], '/loan-application-detail/{id}', [AdminController::class, 'loanApplicationDetail'])->name('loan-application-detail');

    // Borrower Verification detail (used from All Borrowers > View Details)
    Route::match(['get', 'post'], '/verification-detail/{id}', [AdminController::class, 'verificationDetail'])->name('verification-detail');

    // Bagong route para sa All Borrowers page — list lang, walang approve/reject
    Route::get('/borrowers', [AdminController::class, 'allBorrowers'])->name('borrowers');
    Route::get('/borrowers/active', [AdminController::class, 'activeBorrowers'])->name('active-borrowers');

    // Penalty Tracking
    Route::get('/penalty-tracking', [AdminController::class, 'penaltyTracking'])->name('penalty-tracking');
    Route::post('/penalty-tracking/{id}/mark-paid', [AdminController::class, 'markPenaltyPaid'])->name('penalty-mark-paid');
    Route::post('/penalty-tracking/{id}/waive', [AdminController::class, 'waivePenalty'])->name('penalty-waive');
    Route::get('/penalty-tracking-detail/{id}', [AdminController::class, 'penaltyTrackingDetail'])->name('penalty-tracking-detail');

    // Active Loans > Ongoing Loans
    Route::get('/active-loans/ongoing', [AdminController::class, 'ongoingLoans'])->name('ongoing-loans');

    // Active Loans
    Route::get('/ongoing-loan-detail/{id}', [AdminController::class, 'ongoingLoanDetail'])
        ->name('ongoing-loan-detail');

    // Active Loans > Overdue Payments
    Route::get('/active-loans/overdue', [AdminController::class, 'overduePayments'])->name('overdue-payments');

    // Active Borrowers > View Details (focused view — borrower profile +
    // overview of loans, payments, and penalties)
    Route::get('/active-borrower-detail/{id}', [AdminController::class, 'activeBorrowerDetail'])
        ->name('active-borrower-detail');

    // Overdue Payments > View Details (focused view — borrower card + overview

    Route::get('/overdue-payment-detail/{id}', [AdminController::class, 'overduePaymentDetail'])
        ->name('overdue-payment-detail');


    Route::post('/installments/{id}/mark-paid', [AdminController::class, 'markInstallmentPaid'])
        ->name('installment-mark-paid');
        Route::get('/installments/{id}/receipt', [AdminController::class, 'installmentReceipt'])
    ->name('installment-receipt');
        Route::get('/payment-receipts/{id}/file', [AdminController::class, 'borrowerReceiptFile'])
        ->name('borrower-receipt-file');
    Route::post('/installments/{id}/void', [AdminController::class, 'voidPayment'])
    ->name('admin.installments.void');

    // Send proof (na naibigay na ang pinautang) sa borrower via email.
    // Ang buong URL ay /admin/loans/{applicationId}/send-proof — ito ang tinatawag ng JS.
    Route::post('/loans/{applicationId}/send-proof', [AdminController::class, 'sendProofToBorrower'])
        ->whereNumber('applicationId')
        ->name('send-proof');

    // Active Loans > Completed Loans
    Route::get('/active-loans/completed', [AdminController::class, 'completedLoans'])->name('completed-loans');
    Route::get('/completed-loan-detail/{id}', [AdminController::class, 'completedLoanDetail'])
        ->name('completed-loan-detail');

    // Active Loans > Defaulted
    // A loan is "defaulted" once its oldest unpaid installment has been
    // overdue for 90+ days (see AdminController::DEFAULT_DAYS_LATE).
    Route::get('/active-loans/defaulted', [AdminController::class, 'defaultedLoans'])->name('defaulted-loans');
    Route::get('/defaulted-loan-detail/{id}', [AdminController::class, 'defaultedLoanDetail'])
        ->name('defaulted-loan-detail');
});