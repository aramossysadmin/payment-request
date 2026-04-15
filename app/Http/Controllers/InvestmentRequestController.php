<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvestmentRequestRequest;
use App\Http\Requests\UpdateInvestmentRequestRequest;
use App\Http\Resources\InvestmentRequestResource;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\InvestmentExpenseConcept;
use App\Models\InvestmentRequest;
use App\Models\Project;
use App\Services\InvestmentApprovalService;
use App\States\InvestmentRequest\Completed;
use App\States\InvestmentRequest\PendingDepartment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InvestmentRequestController extends Controller
{
    public function __construct(private InvestmentApprovalService $approvalService) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = InvestmentRequest::query()
            ->with(['user', 'department', 'currency', 'branch', 'project', 'expenseConcept', 'approvals.user'])
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
            $query->whereState('status', PendingDepartment::$name);
        } elseif ($group === 'completed') {
            $query->whereState('status', Completed::$name);
        }

        $investmentRequests = $query->latest()->paginate(15)->withQueryString();

        $canApproveIds = [];
        $approvalStages = [];

        foreach ($investmentRequests->items() as $ir) {
            $pendingApproval = $ir->approvals
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->first();

            if ($pendingApproval) {
                $canApproveIds[] = $ir->uuid;
                $approvalStages[$ir->uuid] = $pendingApproval->stage;
            }
        }

        return Inertia::render('investment-sheets/index', [
            'investmentRequests' => InvestmentRequestResource::collection($investmentRequests),
            'canApproveIds' => $canApproveIds,
            'approvalStages' => $approvalStages,
            'canEditPurchaseInvoicesIds' => [],
            'canEditVendorPaymentsIds' => [],
            'filters' => [
                ...$request->only(['search', 'status']),
                'status_group' => $request->string('status_group')->toString() ?: 'pending',
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('investment-sheets/create', [
            'currencies' => Currency::all(['id', 'name', 'prefix']),
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'investmentExpenseConcepts' => InvestmentExpenseConcept::active()->orderBy('name')->get(['id', 'name']),
            'projects' => Project::active()->orderBy('name')->get(['id', 'name', 'branch_id']),
        ]);
    }

    public function store(StoreInvestmentRequestRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $investmentRequest = new InvestmentRequest($validated);
        $investmentRequest->user_id = $user->id;
        $investmentRequest->department_id = $user->department_id;
        $investmentRequest->project_id = $request->input('project_id') ?: null;
        $investmentRequest->save();

        $directory = 'investment-advance-documents/'.now()->format('Y/m').'/'.$investmentRequest->folio_number;
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
            $investmentRequest->update(['advance_documents' => $allDocuments]);
        }

        $this->approvalService->createApprovals($investmentRequest);

        return redirect()->route('investment-sheets.index')
            ->with('success', 'Hoja de inversión creada exitosamente.');
    }

    public function show(Request $request, InvestmentRequest $investmentRequest): Response
    {
        Gate::authorize('view', $investmentRequest);

        $investmentRequest->load(['user', 'department', 'currency', 'branch', 'project', 'expenseConcept', 'approvals.user']);

        $user = $request->user();
        $pendingApproval = $investmentRequest->approvals
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        return Inertia::render('investment-sheets/show', [
            'investmentRequest' => new InvestmentRequestResource($investmentRequest),
            'canApprove' => $pendingApproval !== null,
            'approvalStage' => $pendingApproval?->stage,
            'canEditPurchaseInvoices' => false,
            'canEditVendorPayments' => false,
        ]);
    }

    public function edit(InvestmentRequest $investmentRequest): Response
    {
        Gate::authorize('update', $investmentRequest);

        abort_unless($investmentRequest->status->equals(PendingDepartment::class), 403);

        $investmentRequest->load(['currency', 'branch', 'expenseConcept']);

        return Inertia::render('investment-sheets/edit', [
            'investmentRequest' => new InvestmentRequestResource($investmentRequest),
            'currencies' => Currency::all(['id', 'name', 'prefix']),
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'investmentExpenseConcepts' => InvestmentExpenseConcept::active()->orderBy('name')->get(['id', 'name']),
            'projects' => Project::active()->orderBy('name')->get(['id', 'name', 'branch_id']),
        ]);
    }

    public function update(UpdateInvestmentRequestRequest $request, InvestmentRequest $investmentRequest): RedirectResponse
    {
        Gate::authorize('update', $investmentRequest);

        abort_unless($investmentRequest->status->equals(PendingDepartment::class), 403);

        $validated = $request->validated();

        $hasNewFiles = $request->hasFile('invoice_documents') || $request->hasFile('advance_documents');
        $allDocuments = $investmentRequest->advance_documents ?? [];

        if ($hasNewFiles) {
            foreach ($allDocuments as $oldPath) {
                Storage::disk('local')->delete($oldPath);
            }
            $directory = 'investment-advance-documents/'.now()->format('Y/m').'/'.$investmentRequest->folio_number;
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
        }

        $investmentRequest->update([
            ...$validated,
            'advance_documents' => $allDocuments ?: null,
        ]);

        return redirect()->route('investment-sheets.show', $investmentRequest)
            ->with('success', 'Hoja de inversión actualizada exitosamente.');
    }

    public function destroy(InvestmentRequest $investmentRequest): RedirectResponse
    {
        Gate::authorize('delete', $investmentRequest);

        $investmentRequest->delete();

        return redirect()->route('investment-sheets.index')
            ->with('success', 'Hoja de inversión eliminada exitosamente.');
    }
}
