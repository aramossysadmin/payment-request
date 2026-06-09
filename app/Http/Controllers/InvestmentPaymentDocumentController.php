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
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('super_admin');

        abort_unless($isSuperAdmin || $payment->user_id === $user->id, Response::HTTP_FORBIDDEN, 'Solo el solicitante puede subir documentos.');

        $allowedStatuses = $isSuperAdmin
            ? ['final_approved', 'approved', 'completed']
            : ['final_approved'];

        abort_unless(in_array($payment->status, $allowedStatuses, true), Response::HTTP_CONFLICT, 'Este pago no está en estado de carga de documentos.');

        $paymentType = $request->input('payment_type');
        $directory = 'investment-payment-documents/'.now()->format('Y/m').'/'.$payment->folio_number;

        if ($paymentType === 'factura') {
            $pdfPath = $request->file('pdf')->storeAs($directory, Str::uuid().'.pdf', 'local');
            $xmlPath = $request->file('xml')->storeAs($directory, Str::uuid().'.xml', 'local');
            $documents = [$pdfPath, $xmlPath];
        } else {
            $document = $request->file('document');
            $ext = strtolower($document->getClientOriginalExtension());
            $documents = [$document->storeAs($directory, Str::uuid().'.'.$ext, 'local')];
        }

        $payment->update([
            'advance_documents' => $documents,
            'payment_type' => $paymentType,
            'status' => 'completed',
        ]);

        return back()->with('success', 'Documentos cargados exitosamente. El pago está completado.');
    }
}
