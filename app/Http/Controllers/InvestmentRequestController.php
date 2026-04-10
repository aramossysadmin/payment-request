<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvestmentRequestRequest;
use App\Http\Requests\UpdateInvestmentRequestRequest;
use App\Http\Resources\InvestmentRequestResource;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\ExpenseConcept;
use App\Models\InvestmentRequest;
use App\Models\PaymentType;
use App\Services\InvestmentApprovalService;
use App\States\InvestmentRequest\Completed;
use App\States\InvestmentRequest\PendingAdministration;
use App\States\InvestmentRequest\PendingDepartment;
use App\States\InvestmentRequest\PendingTreasury;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class InvestmentRequestController extends Controller
{
    public function __construct(private InvestmentApprovalService $approvalService) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = InvestmentRequest::query()
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

        $investmentRequests = $query->latest()->paginate(15)->withQueryString();

        $canApproveIds = [];
        $approvalStages = [];
        $canEditPurchaseInvoicesIds = [];
        $canEditVendorPaymentsIds = [];

        foreach ($investmentRequests->items() as $ir) {
            $pendingApproval = $ir->approvals
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->first();

            if ($pendingApproval) {
                $canApproveIds[] = $ir->uuid;
                $approvalStages[$ir->uuid] = $pendingApproval->stage;
            }

            if ($ir->approvals->where('user_id', $user->id)->where('stage', 'administration')->where('status', 'approved')->isNotEmpty()) {
                $canEditPurchaseInvoicesIds[] = $ir->uuid;
            }

            if ($ir->approvals->where('user_id', $user->id)->where('stage', 'treasury')->where('status', 'approved')->isNotEmpty()) {
                $canEditVendorPaymentsIds[] = $ir->uuid;
            }
        }

        return Inertia::render('investment-requests/index', [
            'investmentRequests' => InvestmentRequestResource::collection($investmentRequests),
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
        return Inertia::render('investment-requests/create', [
            'currencies' => Currency::all(['id', 'name', 'prefix']),
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'expenseConcepts' => ExpenseConcept::active()->get(['id', 'name']),
            'paymentTypes' => PaymentType::active()->get(['id', 'name', 'slug', 'requires_invoice_documents']),
        ]);
    }

    public function store(StoreInvestmentRequestRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $advanceDocuments = [];
        if ($request->hasFile('advance_documents')) {
            foreach ($request->file('advance_documents') as $file) {
                $advanceDocuments[] = $file->store('investment-advance-documents', 'public');
            }
        }

        $investmentRequest = InvestmentRequest::create([
            ...$validated,
            'user_id' => $user->id,
            'department_id' => $user->department_id,
            'advance_documents' => $advanceDocuments ?: null,
        ]);

        $this->approvalService->createApprovals($investmentRequest);

        return redirect()->route('investment-requests.index')
            ->with('success', 'Solicitud de inversión creada exitosamente.');
    }

    public function show(Request $request, InvestmentRequest $investmentRequest): Response
    {
        Gate::authorize('view', $investmentRequest);

        $investmentRequest->load(['user', 'department', 'currency', 'branch', 'paymentType', 'expenseConcept', 'approvals.user']);

        $user = $request->user();
        $canApprove = $investmentRequest->approvals
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->isNotEmpty();

        $canEditPurchaseInvoices = $investmentRequest->approvals
            ->where('user_id', $user->id)
            ->where('stage', 'administration')
            ->where('status', 'approved')
            ->isNotEmpty();

        $canEditVendorPayments = $investmentRequest->approvals
            ->where('user_id', $user->id)
            ->where('stage', 'treasury')
            ->where('status', 'approved')
            ->isNotEmpty();

        $pendingApproval = $investmentRequest->approvals
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        return Inertia::render('investment-requests/show', [
            'investmentRequest' => new InvestmentRequestResource($investmentRequest),
            'canApprove' => $canApprove,
            'approvalStage' => $pendingApproval?->stage,
            'canEditPurchaseInvoices' => $canEditPurchaseInvoices,
            'canEditVendorPayments' => $canEditVendorPayments,
        ]);
    }

    public function edit(InvestmentRequest $investmentRequest): Response
    {
        Gate::authorize('update', $investmentRequest);

        abort_unless($investmentRequest->status->equals(PendingDepartment::class), 403);

        $investmentRequest->load(['currency', 'branch', 'paymentType', 'expenseConcept']);

        return Inertia::render('investment-requests/edit', [
            'investmentRequest' => new InvestmentRequestResource($investmentRequest),
            'currencies' => Currency::all(['id', 'name', 'prefix']),
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'expenseConcepts' => ExpenseConcept::active()->get(['id', 'name']),
            'paymentTypes' => PaymentType::active()->get(['id', 'name', 'slug', 'requires_invoice_documents']),
        ]);
    }

    public function update(UpdateInvestmentRequestRequest $request, InvestmentRequest $investmentRequest): RedirectResponse
    {
        Gate::authorize('update', $investmentRequest);

        abort_unless($investmentRequest->status->equals(PendingDepartment::class), 403);

        $validated = $request->validated();

        $advanceDocuments = $investmentRequest->advance_documents ?? [];
        if ($request->hasFile('advance_documents')) {
            foreach ($advanceDocuments as $oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
            $advanceDocuments = [];
            foreach ($request->file('advance_documents') as $file) {
                $advanceDocuments[] = $file->store('investment-advance-documents', 'public');
            }
        }

        $investmentRequest->update([
            ...$validated,
            'advance_documents' => $advanceDocuments ?: null,
        ]);

        return redirect()->route('investment-requests.show', $investmentRequest)
            ->with('success', 'Solicitud de inversión actualizada exitosamente.');
    }

    public function destroy(InvestmentRequest $investmentRequest): RedirectResponse
    {
        Gate::authorize('delete', $investmentRequest);

        $investmentRequest->delete();

        return redirect()->route('investment-requests.index')
            ->with('success', 'Solicitud de inversión eliminada exitosamente.');
    }
}
