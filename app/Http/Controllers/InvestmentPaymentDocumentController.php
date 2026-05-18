<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadInvestmentPaymentDocumentsRequest;
use App\Models\InvestmentPaymentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class InvestmentPaymentDocumentController extends Controller
{
    public function upload(UploadInvestmentPaymentDocumentsRequest $request, InvestmentPaymentRequest $payment): RedirectResponse
    {
        abort_unless($payment->user_id === auth()->id(), Response::HTTP_FORBIDDEN, 'Solo el solicitante puede subir documentos.');
        abort_unless($payment->status === 'final_approved', Response::HTTP_CONFLICT, 'Este pago no está en estado de carga de documentos.');

        $directory = 'investment-payment-documents/'.now()->format('Y/m').'/'.$payment->folio_number;

        $pdfPath = $request->file('pdf')->storeAs(
            $directory,
            Str::uuid().'.pdf',
            'local',
        );

        $xmlPath = $request->file('xml')->storeAs(
            $directory,
            Str::uuid().'.xml',
            'local',
        );

        $payment->update([
            'advance_documents' => [$pdfPath, $xmlPath],
            'status' => 'completed',
        ]);

        return back()->with('success', 'Documentos cargados exitosamente. El pago está completado.');
    }
}
