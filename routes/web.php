<?php

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentRequestApprovalController;
use App\Http\Controllers\PaymentRequestController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    }
    )->name('dashboard');

    Route::resource('payment-requests', PaymentRequestController::class);
    Route::post('payment-requests/{payment_request}/approve', [PaymentRequestApprovalController::class, 'approve'])->name('payment-requests.approve');
    Route::post('payment-requests/{payment_request}/reject', [PaymentRequestApprovalController::class, 'reject'])->name('payment-requests.reject');

    Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

require __DIR__.'/settings.php';
