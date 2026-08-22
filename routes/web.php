<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BorrowerController;

Route::get('/', function () {
    return file_get_contents(public_path('landing.html'));
});

Route::get('/login', function () {
    return file_get_contents(public_path('login.html'));
})->name('login');

Route::get('/register', function () {
    return file_get_contents(public_path('register.html'));
})->name('register');

Route::get('/forgot-password', function () {
    return file_get_contents(public_path('forgot-password.html'));
})->name('forgot-password');

Route::post('/api/register',            [AuthController::class, 'register'])->middleware('throttle:5,10');
Route::post('/api/register/verify-otp', [AuthController::class, 'verifyRegisterOtp'])->middleware('throttle:5,10');
Route::post('/api/register/resend-otp', [AuthController::class, 'resendRegisterOtp'])->middleware('throttle:5,10');

Route::post('/api/login',               [AuthController::class, 'login']);
Route::post('/api/login/verify-otp',    [AuthController::class, 'verifyOtp'])->middleware('throttle:5,10');
Route::post('/api/login/resend-otp',    [AuthController::class, 'resendOtp'])->middleware('throttle:5,10');
Route::post('/api/logout',              [AuthController::class, 'logout'])->name('logout');

Route::post('/api/forgot-password/send-otp', [ForgotPasswordController::class, 'sendOtp'])->middleware('throttle:5,10');
Route::post('/api/forgot-password/reset',    [ForgotPasswordController::class, 'reset'])->middleware('throttle:5,10');

// Borrower pages — Blade, protected by 'borrower' middleware
Route::middleware('borrower')->group(function () {
    Route::get('/borrower/dashboard', [BorrowerController::class, 'dashboard'])->name('borrower.dashboard');
    Route::get('/borrower/personal-details', [BorrowerController::class, 'personalDetails'])->name('borrower.personal-details');
    Route::get('/borrower/apply-loan', [BorrowerController::class, 'applyLoan'])->name('borrower.apply-loan');
    Route::get('/borrower/loan-details', [BorrowerController::class, 'loanDetails'])->name('borrower.loan-details');
    Route::get('/borrower/notifications', [BorrowerController::class, 'notifications'])->name('borrower.notifications');
    Route::get('/borrower/profile', [BorrowerController::class, 'profile'])->name('borrower.profile');
});

// Borrower API — save actions, protected by 'borrower' middleware
Route::middleware('borrower')->prefix('api/borrower')->group(function () {
    Route::post('/personal-details', [BorrowerController::class, 'savePersonalDetails']);
    Route::post('/apply-loan', [BorrowerController::class, 'submitLoanApplication']);
    Route::post('/profile/update-section', [BorrowerController::class, 'updateProfileSection']);
    Route::post('/profile/change-password', [BorrowerController::class, 'changePassword']);
});

// Admin pages

Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::match(['get', 'post'], '/borrower-verification', [AdminController::class, 'borrowerVerification'])->name('borrower-verification');
    Route::match(['get', 'post'], '/verification-detail/{id}', [AdminController::class, 'verificationDetail'])->name('verification-detail');
    Route::match(['get', 'post'], '/loan-applications', [AdminController::class, 'loanApplications'])->name('loan-applications');
    Route::match(['get', 'post'], '/loan-application-detail/{id}', [AdminController::class, 'loanApplicationDetail'])->name('loan-application-detail');

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

    // Active Loans > Ongoing Loans > View Details (loan-focused — NOT the
    // same as Active Borrower Detail. No borrower profile info here, just
    // Loan Summary, Admin Review Status, and Payment Schedule.)
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

    // Active Loans > Completed Loans
    Route::get('/active-loans/completed', [AdminController::class, 'completedLoans'])->name('completed-loans');
    Route::get('/completed-loan-detail/{id}', [AdminController::class, 'completedLoanDetail'])
        ->name('completed-loan-detail');
});