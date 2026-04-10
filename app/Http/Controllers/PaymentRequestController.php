<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequestRequest;
use App\Http\Requests\UpdatePaymentRequestRequest;
use App\Http\Resources\PaymentRequestResource;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\ExpenseConcept;
use App\Models\PaymentRequest;
use App\Models\PaymentType;
use App\Services\ApprovalService;
use App\States\PaymentRequest\Completed;
use App\States\PaymentRequest\PendingAdministration;
use App\States\PaymentRequest\PendingDepartment;
use App\States\PaymentRequest\PendingTreasury;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PaymentRequestController extends Controller
{
    public function __construct(private ApprovalService $approvalService) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = PaymentRequest::query()
            ->with(['user', 'department', 'currency', 'branch', 'paymentType', 'expenseConcept', 'approvals.user'])
            ->visibleTo($user);

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('provider', 'like', "%{$search}%")
                    ->orWhere('invoice_folio', 'like', "%{$search}%")
                    ->orWhere('folio_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->whereState('status', $request->string('status')->toString());
        }

        $group = $request->string('status_group')->toString() ?: 'pending';

        if ($group === 'pending') {
            $query->whereIn('status', [
                PendingDepartment::$name,
                PendingAdministration::$name,
                PendingTreasury::$name,
            ]);
        } elseif ($group === 'completed') {
            $query->whereState('status', Completed::$name);
        }

        $paymentRequests = $query->latest()->paginate(15)->withQueryString();

        $canApproveIds = [];
        $approvalStages = [];
        $canEditPurchaseInvoicesIds = [];
        $canEditVendorPaymentsIds = [];

        foreach ($paymentRequests->items() as $pr) {
            $pendingApproval = $pr->approvals
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->first();

            if ($pendingApproval) {
                $canApproveIds[] = $pr->uuid;
                $approvalStages[$pr->uuid] = $pendingApproval->stage;
            }

            if ($pr->approvals->where('user_id', $user->id)->where('stage', 'administration')->where('status', 'approved')->isNotEmpty()) {
                $canEditPurchaseInvoicesIds[] = $pr->uuid;
            }

            if ($pr->approvals->where('user_id', $user->id)->where('stage', 'treasury')->where('status', 'approved')->isNotEmpty()) {
                $canEditVendorPaymentsIds[] = $pr->uuid;
            }
        }

        return Inertia::render('payment-requests/index', [
            'paymentRequests' => PaymentRequestResource::collection($paymentRequests),
            'canApproveIds' => $canApproveIds,
            'approvalStages' => $approvalStages,
            'canEditPurchaseInvoicesIds' => $canEditPurchaseInvoicesIds,
            'canEditVendorPaymentsIds' => $canEditVendorPaymentsIds,
            'filters' => [
                ...$request->only(['search', 'status']),
                'status_group' => $request->string('status_group')->toString() ?: 'pending',
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('payment-requests/create', [
            'currencies' => Currency::all(['id', 'name', 'prefix']),
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'expenseConcepts' => ExpenseConcept::active()->get(['id', 'name']),
            'paymentTypes' => PaymentType::active()->forPayments()->get(['id', 'name', 'slug', 'invoice_documents_mode', 'additional_documents_mode']),
        ]);
    }

    public function store(StorePaymentRequestRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $allDocuments = [];
        if ($request->hasFile('invoice_documents')) {
            foreach ($request->file('invoice_documents') as $file) {
                $allDocuments[] = $file->store('advance-documents', 'public');
            }
        }
        if ($request->hasFile('advance_documents')) {
            foreach ($request->file('advance_documents') as $file) {
                $allDocuments[] = $file->store('advance-documents', 'public');
            }
        }

        $paymentRequest = PaymentRequest::create([
            ...$validated,
            'user_id' => $user->id,
            'department_id' => $user->department_id,
            'advance_documents' => $allDocuments ?: null,
        ]);

        $this->approvalService->createApprovals($paymentRequest);

        return redirect()->route('payment-requests.index')
            ->with('success', 'Solicitud de pago creada exitosamente.');
    }

    public function show(Request $request, PaymentRequest $paymentRequest): Response
    {
        Gate::authorize('view', $paymentRequest);

        $paymentRequest->load(['user', 'department', 'currency', 'branch', 'paymentType', 'expenseConcept', 'approvals.user']);

        $user = $request->user();
        $canApprove = $paymentRequest->approvals
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->isNotEmpty();

        $canEditPurchaseInvoices = $paymentRequest->approvals
            ->where('user_id', $user->id)
            ->where('stage', 'administration')
            ->where('status', 'approved')
            ->isNotEmpty();

        $canEditVendorPayments = $paymentRequest->approvals
            ->where('user_id', $user->id)
            ->where('stage', 'treasury')
            ->where('status', 'approved')
            ->isNotEmpty();

        $pendingApproval = $paymentRequest->approvals
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        return Inertia::render('payment-requests/show', [
            'paymentRequest' => new PaymentRequestResource($paymentRequest),
            'canApprove' => $canApprove,
            'approvalStage' => $pendingApproval?->stage,
            'canEditPurchaseInvoices' => $canEditPurchaseInvoices,
            'canEditVendorPayments' => $canEditVendorPayments,
        ]);
    }

    public function edit(PaymentRequest $paymentRequest): Response
    {
        Gate::authorize('update', $paymentRequest);

        abort_unless($paymentRequest->status->equals(PendingDepartment::class), 403);

        $paymentRequest->load(['currency', 'branch', 'paymentType', 'expenseConcept']);

        return Inertia::render('payment-requests/edit', [
            'paymentRequest' => new PaymentRequestResource($paymentRequest),
            'currencies' => Currency::all(['id', 'name', 'prefix']),
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'expenseConcepts' => ExpenseConcept::active()->get(['id', 'name']),
            'paymentTypes' => PaymentType::active()->forPayments()->get(['id', 'name', 'slug', 'invoice_documents_mode', 'additional_documents_mode']),
        ]);
    }

    public function update(UpdatePaymentRequestRequest $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        Gate::authorize('update', $paymentRequest);

        abort_unless($paymentRequest->status->equals(PendingDepartment::class), 403);

        $validated = $request->validated();

        $hasNewFiles = $request->hasFile('invoice_documents') || $request->hasFile('advance_documents');
        $allDocuments = $paymentRequest->advance_documents ?? [];

        if ($hasNewFiles) {
            foreach ($allDocuments as $oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
            $allDocuments = [];
            if ($request->hasFile('invoice_documents')) {
                foreach ($request->file('invoice_documents') as $file) {
                    $allDocuments[] = $file->store('advance-documents', 'public');
                }
            }
            if ($request->hasFile('advance_documents')) {
                foreach ($request->file('advance_documents') as $file) {
                    $allDocuments[] = $file->store('advance-documents', 'public');
                }
            }
        }

        $paymentRequest->update([
            ...$validated,
            'advance_documents' => $allDocuments ?: null,
        ]);

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', 'Solicitud de pago actualizada exitosamente.');
    }

    public function destroy(PaymentRequest $paymentRequest): RedirectResponse
    {
        Gate::authorize('delete', $paymentRequest);

        $paymentRequest->delete();

        return redirect()->route('payment-requests.index')
            ->with('success', 'Solicitud de pago eliminada exitosamente.');
    }
}
