<?php

namespace App\Http\Controllers;

use App\Models\InvestmentRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class InvestmentRequestPdfController extends Controller
{
    public function __invoke(InvestmentRequest $investmentRequest): Response
    {
        Gate::authorize('view', $investmentRequest);

        abort_unless($investmentRequest->status::$name === 'completed', 403);

        $investmentRequest->load(['user', 'department', 'currency', 'branch', 'paymentType', 'expenseConcept', 'approvals.user']);

        $pdf = Pdf::loadView('pdf.payment-request', [
            'request' => $investmentRequest,
            'title' => 'Solicitud de Inversión',
        ]);

        return $pdf->download('solicitud-inversion-'.$investmentRequest->folio_number.'.pdf');
    }
}
