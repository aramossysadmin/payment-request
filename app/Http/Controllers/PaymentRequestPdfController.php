<?php

namespace App\Http\Controllers;

use App\Models\PaymentRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PaymentRequestPdfController extends Controller
{
    public function __invoke(PaymentRequest $paymentRequest): Response
    {
        Gate::authorize('view', $paymentRequest);

        abort_unless($paymentRequest->status::$name === 'completed', 403);

        $paymentRequest->load(['user', 'department', 'currency', 'branch', 'paymentType', 'expenseConcept', 'approvals.user']);

        $pdf = Pdf::loadView('pdf.payment-request', [
            'request' => $paymentRequest,
            'title' => 'Solicitud de Pago',
        ]);

        return $pdf->download('solicitud-pago-'.$paymentRequest->folio_number.'.pdf');
    }
}
