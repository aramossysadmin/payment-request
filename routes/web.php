<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentViewController;
use App\Http\Controllers\EmailApprovalController;
use App\Http\Controllers\InvestmentPaymentRequestController;
use App\Http\Controllers\InvestmentRequestApprovalController;
use App\Http\Controllers\InvestmentRequestController;
use App\Http\Controllers\InvestmentRequestPdfController;
use App\Http\Controllers\InvestmentSheetConsolidatedController;
use App\Http\Controllers\InvestmentSheetConsolidatedIndexController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentRequestApprovalController;
use App\Http\Controllers\PaymentRequestController;
use App\Http\Controllers\PaymentRequestPdfController;
use App\Http\Controllers\ProviderSearchController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('/documents/view', DocumentViewController::class)->name('documents.view');

Route::middleware('throttle:10,1')->group(function () {
    Route::get('/approval/{token}', [EmailApprovalController::class, 'show'])->name('approval.show');
    Route::post('/approval/{token}/approve', [EmailApprovalController::class, 'approve'])->name('approval.approve');
    Route::post('/approval/{token}/reject', [EmailApprovalController::class, 'reject'])->name('approval.reject');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('providers/search', ProviderSearchController::class)->middleware('throttle:30,1')->name('providers.search');
    Route::resource('payment-requests', PaymentRequestController::class);
    Route::post('payment-requests/{payment_request}/approve', [PaymentRequestApprovalController::class, 'approve'])->middleware('throttle:10,1')->name('payment-requests.approve');
    Route::post('payment-requests/{payment_request}/reject', [PaymentRequestApprovalController::class, 'reject'])->middleware('throttle:10,1')->name('payment-requests.reject');
    Route::patch('payment-requests/{payment_request}/sap-folios', [PaymentRequestApprovalController::class, 'updateSapFolios'])->name('payment-requests.sap-folios');
    Route::get('payment-requests/{payment_request}/pdf', PaymentRequestPdfController::class)->name('payment-requests.pdf');

    Route::get('investment-sheets/consolidated', InvestmentSheetConsolidatedIndexController::class)->name('investment-sheets.consolidated.index');
    Route::get('investment-sheets/consolidated/{project}', InvestmentSheetConsolidatedController::class)->name('investment-sheets.consolidated');
    Route::get('investment-payment-requests/{investmentRequestId}', [InvestmentPaymentRequestController::class, 'index'])->name('investment-payment-requests.index');
    Route::post('investment-payment-requests', [InvestmentPaymentRequestController::class, 'store'])->name('investment-payment-requests.store');
    Route::resource('investment-sheets', InvestmentRequestController::class)->parameters(['investment-sheets' => 'investment_request']);
    Route::post('investment-sheets/{investment_request}/approve', [InvestmentRequestApprovalController::class, 'approve'])->middleware('throttle:10,1')->name('investment-sheets.approve');
    Route::post('investment-sheets/{investment_request}/reject', [InvestmentRequestApprovalController::class, 'reject'])->middleware('throttle:10,1')->name('investment-sheets.reject');
    Route::get('investment-sheets/{investment_request}/pdf', InvestmentRequestPdfController::class)->name('investment-sheets.pdf');

    Route::get('guide', fn () => Inertia::render('guide/index'))->name('guide');

    Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

require __DIR__.'/settings.php';
