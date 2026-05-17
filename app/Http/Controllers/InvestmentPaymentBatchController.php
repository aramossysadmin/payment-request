<?php

namespace App\Http\Controllers;

use App\Models\InvestmentPaymentRequest;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class InvestmentPaymentBatchController extends Controller
{
    public function destroyPayment(InvestmentPaymentRequest $payment): RedirectResponse
    {
        abort_unless($payment->user_id === auth()->id(), Response::HTTP_FORBIDDEN);
        abort_unless($payment->status === 'draft', Response::HTTP_FORBIDDEN);

        $payment->delete();

        return back()->with('success', 'Pago eliminado del borrador.');
    }
}
