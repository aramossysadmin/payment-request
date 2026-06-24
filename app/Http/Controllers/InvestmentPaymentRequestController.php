<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvestmentPaymentRequest;
use App\Http\Requests\UpdateInvestmentPaymentRequest;
use App\Models\InvestmentPaymentBatch;
use App\Models\InvestmentPaymentRequest;
use App\Models\InvestmentRequest;
use App\States\InvestmentRequest\Completed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
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
                ->where('department_id', $investmentRequest->department_id)
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
            ->whereNotIn('status', ['rejected', 'ceo_rejected', 'projectmanager_rejected', 'final_rejected', 'auto_cancelled'])
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

        $paymentRequest = new InvestmentPaymentRequest(Arr::except($validated, ['invoice_documents', 'advance_documents']));
        $paymentRequest->user_id = $user->id;
        $paymentRequest->department_id = $investmentRequest->department_id;
        $paymentRequest->batch_id = $batch->id;
        $paymentRequest->status = 'draft';
        $paymentRequest->payment_type = $validated['payment_type'];
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

    public function update(UpdateInvestmentPaymentRequest $request, InvestmentPaymentRequest $payment): RedirectResponse
    {
        $validated = $request->validated();

        $keepDocuments = collect($validated['keep_documents'] ?? [])
            ->filter(fn ($d) => is_string($d) && $d !== '')
            ->values()
            ->all();

        $currentDocuments = array_values(array_filter(
            $payment->advance_documents ?? [],
            fn ($d) => is_string($d) && $d !== '',
        ));

        $documentsToDelete = array_diff($currentDocuments, $keepDocuments);
        foreach ($documentsToDelete as $path) {
            if (Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }
        }

        $newDocuments = $keepDocuments;
        $directory = 'investment-payment-documents/'.now()->format('Y/m').'/'.$payment->folio_number;

        if ($request->hasFile('invoice_documents')) {
            foreach ($request->file('invoice_documents') as $file) {
                $newDocuments[] = $file->storeAs($directory, Str::uuid().'.'.$file->getClientOriginalExtension(), 'local');
            }
        }

        if ($request->hasFile('advance_documents')) {
            foreach ($request->file('advance_documents') as $file) {
                $newDocuments[] = $file->storeAs($directory, Str::uuid().'.'.$file->getClientOriginalExtension(), 'local');
            }
        }

        $payment->fill([
            'provider' => $validated['provider'],
            'rfc' => $validated['rfc'] ?? null,
            'invoice_folio' => $validated['invoice_folio'] ?? null,
            'payment_provision_date' => $validated['payment_provision_date'],
            'currency_id' => $validated['currency_id'],
            'branch_id' => $validated['branch_id'],
            'description' => $validated['description'] ?? null,
            'iva_rate' => $validated['iva_rate'],
            'subtotal' => $validated['subtotal'],
            'iva' => $validated['iva'],
            'retention' => $request->boolean('retention'),
            'total' => $validated['total'],
            'payment_type' => $validated['payment_type'],
            'payment_week_number' => Carbon::parse($validated['payment_provision_date'])->isoWeek,
            'advance_documents' => $newDocuments,
        ]);

        $payment->save();

        return back()->with('success', 'Pago actualizado.');
    }

    /**
     * Corrección administrativa (Super Admin): mueve la fecha de programación de un
     * pago y recalcula la semana (payment_week_number) sin pasar por la validación
     * de ventana de captura. No toca el batch.
     */
    public function correctProvisionDate(Request $request, InvestmentPaymentRequest $payment): RedirectResponse
    {
        abort_unless($request->user()->hasRole('super_admin'), 403);

        $validated = $request->validate([
            'payment_provision_date' => ['required', 'date'],
        ]);

        $payment->update([
            'payment_provision_date' => $validated['payment_provision_date'],
            'payment_week_number' => Carbon::parse($validated['payment_provision_date'])->isoWeek,
        ]);

        return back()->with('success', 'Fecha de programación actualizada; la semana se recalculó.');
    }

    private function findOrCreateActiveBatch($user, InvestmentRequest $investmentRequest): InvestmentPaymentBatch
    {
        $now = Carbon::now();
        $weekNumber = $now->isoWeek;
        $year = $now->isoWeekYear;

        // El batch hereda el department del InvestmentRequest, no del user.
        // Esto soporta users multi-dpto y corrige el bug de super_admins/multi-rol.
        return InvestmentPaymentBatch::query()
            ->where('department_id', $investmentRequest->department_id)
            ->where('project_id', $investmentRequest->project_id)
            ->where('week_number', $weekNumber)
            ->where('year', $year)
            ->where('status', 'draft')
            ->firstOrCreate([
                'department_id' => $investmentRequest->department_id,
                'project_id' => $investmentRequest->project_id,
                'week_number' => $weekNumber,
                'year' => $year,
                'status' => 'draft',
            ], [
                'user_id' => $user->id,
            ]);
    }
}
