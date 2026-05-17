<?php

namespace App\Http\Controllers;

use App\Models\InvestmentPaymentBatch;
use App\Models\InvestmentPaymentRequest;
use App\Models\User;
use App\Notifications\InvestmentBatchSubmittedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    public function submit(Request $request, InvestmentPaymentBatch $batch): RedirectResponse
    {
        $user = $request->user();

        abort_unless($batch->user_id === $user->id || $batch->department_id === $user->department_id, Response::HTTP_FORBIDDEN);
        abort_unless($batch->status === 'draft', Response::HTTP_CONFLICT, 'El lote ya fue enviado.');

        $validated = $request->validate([
            'payment_uuids' => ['required', 'array', 'min:1'],
            'payment_uuids.*' => ['required', 'string', 'uuid'],
        ]);

        $selectedUuids = collect($validated['payment_uuids']);

        $allPayments = InvestmentPaymentRequest::query()
            ->where('batch_id', $batch->id)
            ->where('status', 'draft')
            ->get();

        $selectedPayments = $allPayments->whereIn('uuid', $selectedUuids);
        $unselectedPayments = $allPayments->whereNotIn('uuid', $selectedUuids);

        if ($selectedPayments->isEmpty()) {
            return back()->withErrors(['payment_uuids' => 'Debes seleccionar al menos un pago para enviar.']);
        }

        DB::transaction(function () use ($batch, $selectedPayments, $unselectedPayments, $user) {
            // Mark selected payments as submitted (stay in current batch)
            foreach ($selectedPayments as $payment) {
                $payment->update(['status' => 'submitted']);
            }

            // Move unselected to a new draft batch
            if ($unselectedPayments->isNotEmpty()) {
                $newDraft = InvestmentPaymentBatch::create([
                    'department_id' => $batch->department_id,
                    'user_id' => $user->id,
                    'project_id' => $batch->project_id,
                    'week_number' => $batch->week_number,
                    'year' => $batch->year,
                    'status' => 'draft',
                ]);

                foreach ($unselectedPayments as $payment) {
                    $payment->update(['batch_id' => $newDraft->id]);
                }
            }

            // Submit current batch
            $batch->update([
                'status' => 'submitted',
                'submitted_at' => now(),
                'ceo_approval_token' => (string) Str::uuid(),
                'ceo_approval_token_expires_at' => Carbon::now()->addHours(48),
            ]);
        });

        $batch->refresh();

        // Notify CEO
        $ceoEmail = config('investment-requests.authorizer_email');
        $ceo = User::where('email', $ceoEmail)->first();

        if ($ceo) {
            $ceo->notify(new InvestmentBatchSubmittedNotification($batch));
        }

        return back()->with('success', 'Lote enviado a autorización del CEO.');
    }
}
