<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailApprovalController;
use App\Http\Controllers\InvestmentRequestApprovalController;
use App\Http\Controllers\InvestmentRequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentRequestApprovalController;
use App\Http\Controllers\PaymentRequestController;
use App\Http\Controllers\ProviderSearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::middleware('throttle:10,1')->group(function () {
    Route::get('/approval/{token}', [EmailApprovalController::class, 'show'])->name('approval.show');
    Route::post('/approval/{token}/approve', [EmailApprovalController::class, 'approve'])->name('approval.approve');
    Route::post('/approval/{token}/reject', [EmailApprovalController::class, 'reject'])->name('approval.reject');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('providers/search', ProviderSearchController::class)->name('providers.search');
    Route::resource('payment-requests', PaymentRequestController::class);
    Route::post('payment-requests/{payment_request}/approve', [PaymentRequestApprovalController::class, 'approve'])->name('payment-requests.approve');
    Route::post('payment-requests/{payment_request}/reject', [PaymentRequestApprovalController::class, 'reject'])->name('payment-requests.reject');
    Route::patch('payment-requests/{payment_request}/sap-folios', [PaymentRequestApprovalController::class, 'updateSapFolios'])->name('payment-requests.sap-folios');

    Route::resource('investment-requests', InvestmentRequestController::class);
    Route::post('investment-requests/{investment_request}/approve', [InvestmentRequestApprovalController::class, 'approve'])->name('investment-requests.approve');
    Route::post('investment-requests/{investment_request}/reject', [InvestmentRequestApprovalController::class, 'reject'])->name('investment-requests.reject');
    Route::patch('investment-requests/{investment_request}/sap-folios', [InvestmentRequestApprovalController::class, 'updateSapFolios'])->name('investment-requests.sap-folios');

    Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

require __DIR__.'/settings.php';
