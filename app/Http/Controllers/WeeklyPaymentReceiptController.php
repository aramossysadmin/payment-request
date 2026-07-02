<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadPaymentReceiptRequest;
use App\Models\InvestmentPaymentRequest;
use App\Models\InvestmentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class WeeklyPaymentReceiptController extends Controller
{
    /**
     * Estados desde los que se puede adjuntar comprobante: pagos listos/programados
     * (con o sin programación autorizada) y receipt_attached para agregar más archivos.
     *
     * @var array<int, string>
     */
    private const UPLOADABLE_STATUSES = ['approved', 'completed', 'scheduled_for_bank', 'receipt_attached'];

    public function store(UploadPaymentReceiptRequest $request, InvestmentPaymentRequest $payment): RedirectResponse
    {
        $user = $request->user();

        $projectId = $payment->investmentRequest?->project_id;
        $visible = $projectId !== null && InvestmentRequest::query()
            ->visibleTo($user)
            ->where('project_id', $projectId)
            ->exists();

        abort_unless($visible, Response::HTTP_FORBIDDEN, 'No tienes acceso al proyecto de este pago.');

        abort_unless(
            in_array($payment->status, self::UPLOADABLE_STATUSES, true),
            Response::HTTP_CONFLICT,
            'Este pago no está en un estado que permita adjuntar comprobante.',
        );

        $directory = 'investment-payment-receipts/'.now()->format('Y/m').'/'.$payment->folio_number;

        $stored = [];
        foreach ($request->file('receipt_documents', []) as $file) {
            $ext = strtolower($file->getClientOriginalExtension());
            $stored[] = $file->storeAs($directory, Str::uuid().'.'.$ext, 'local');
        }

        // La carga REEMPLAZA el comprobante anterior (los archivos viejos se conservan
        // en storage por auditoría; solo se sustituye la referencia del pago).
        $payment->update([
            'payment_receipt_documents' => $stored,
            'receipt_uploaded_at' => now(),
            'receipt_uploaded_by' => $user->id,
            'status' => 'receipt_attached',
        ]);

        return back()->with('success', 'Comprobante adjuntado. El pago quedó marcado como "Comprobante adjunto".');
    }
}
