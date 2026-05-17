<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvestmentPaymentRequest;
use App\Models\InvestmentPaymentBatch;
use App\Models\InvestmentPaymentRequest;
use App\Models\InvestmentRequest;
use App\States\InvestmentRequest\Completed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class InvestmentPaymentRequestController extends Controller
{
    public function index(int $investmentRequestId): JsonResponse
    {
        $investmentRequest = InvestmentRequest::findOrFail($investmentRequestId);

        $groupIds = collect([$investmentRequest->id]);

        if ($investmentRequest->investment_expense_concept_id && $investmentRequest->project_id) {
            $groupIds = InvestmentRequest::query()
                ->where('project_id', $investmentRequest->project_id)
                ->where('investment_expense_concept_id', $investmentRequest->investment_expense_concept_id)
                ->whereState('status', Completed::class)
                ->pluck('id');
        }

        $payments = InvestmentPaymentRequest::query()
            ->whereIn('investment_request_id', $groupIds)
            ->with(['user', 'currency', 'approvals.user', 'investmentRequest'])
            ->latest()
            ->get()
            ->map(fn (InvestmentPaymentRequest $p) => [
                'id' => $p->id,
                'uuid' => $p->uuid,
                'folio_number' => $p->folio_number,
                'provider' => $p->provider,
                'rfc' => $p->rfc,
                'payment_type' => $p->payment_type,
                'currency_prefix' => $p->currency?->prefix ?? 'MXN',
                'subtotal' => (string) $p->subtotal,
                'iva' => (string) $p->iva,
                'total' => (string) $p->total,
                'status' => $p->status,
                'user_name' => $p->user?->name,
                'created_at' => $p->created_at?->toISOString(),
                'approval_status' => $p->approvals->first()?->status ?? 'pending',
                'concept_folio' => $p->investmentRequest?->folio_number,
            ]);

        $groupBudget = (float) InvestmentRequest::whereIn('id', $groupIds)->sum('total');
        $groupPaid = (float) $payments
            ->whereNotIn('status', ['rejected', 'ceo_rejected', 'projectmanager_rejected', 'final_rejected'])
            ->sum(fn ($p) => (float) $p['total']);

        return response()->json([
            'payments' => $payments,
            'summary' => [
                'total_concept' => number_format($groupBudget, 2, '.', ''),
                'total_paid' => $groupPaid,
                'remaining' => number_format($groupBudget - $groupPaid, 2, '.', ''),
                'count' => $payments->count(),
            ],
        ]);
    }

    public function store(StoreInvestmentPaymentRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $investmentRequest = InvestmentRequest::findOrFail($validated['investment_request_id']);

        $batch = $this->findOrCreateActiveBatch($user, $investmentRequest);

        $paymentRequest = new InvestmentPaymentRequest($validated);
        $paymentRequest->user_id = $user->id;
        $paymentRequest->department_id = $user->department_id;
        $paymentRequest->batch_id = $batch->id;
        $paymentRequest->status = 'draft';
        $paymentRequest->payment_type = $request->boolean('is_invoice') ? 'factura' : 'anticipo';
        $paymentRequest->payment_week_number = Carbon::parse($validated['payment_provision_date'])->isoWeek;
        $paymentRequest->save();

        $directory = 'investment-payment-documents/'.now()->format('Y/m').'/'.$paymentRequest->folio_number;
        $allDocuments = [];

        if ($request->hasFile('invoice_documents')) {
            foreach ($request->file('invoice_documents') as $file) {
                $allDocuments[] = $file->storeAs($directory, Str::uuid().'.'.$file->getClientOriginalExtension(), 'local');
            }
        }

        if ($request->hasFile('advance_documents')) {
            foreach ($request->file('advance_documents') as $file) {
                $allDocuments[] = $file->storeAs($directory, Str::uuid().'.'.$file->getClientOriginalExtension(), 'local');
            }
        }

        if (! empty($allDocuments)) {
            $paymentRequest->update(['advance_documents' => $allDocuments]);
        }

        return back()->with('success', 'Pago agregado al borrador de la semana.');
    }

    private function findOrCreateActiveBatch($user, InvestmentRequest $investmentRequest): InvestmentPaymentBatch
    {
        $now = Carbon::now();
        $weekNumber = $now->isoWeek;
        $year = $now->isoWeekYear;

        return InvestmentPaymentBatch::query()
            ->where('department_id', $user->department_id)
            ->where('project_id', $investmentRequest->project_id)
            ->where('week_number', $weekNumber)
            ->where('year', $year)
            ->where('status', 'draft')
            ->firstOrCreate([
                'department_id' => $user->department_id,
                'project_id' => $investmentRequest->project_id,
                'week_number' => $weekNumber,
                'year' => $year,
                'status' => 'draft',
            ], [
                'user_id' => $user->id,
            ]);
    }
}
