<?php

namespace App\Http\Controllers;

use App\Models\InvestmentPaymentRequest;
use App\Models\Project;
use App\Services\StrategyPaymentHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class StrategyHistoryController extends Controller
{
    public function __invoke(Request $request, StrategyPaymentHistory $history): Response|RedirectResponse
    {
        if (! $request->user()->hasPortalPermission('view_portal::strategies')) {
            return redirect()->route('investment-sheets.consolidated.index');
        }

        $projects = Project::query()
            ->orderBy('name')
            ->get(['id', 'name', 'is_active', 'closed_at'])
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'is_active' => (bool) $project->is_active,
                'closed_at' => $project->closed_at?->toISOString(),
            ])
            ->values();

        $allProjects = $request->input('project_id') === StrategyPaymentHistory::ALL_PROJECTS;
        $selectedProjectId = $allProjects
            ? StrategyPaymentHistory::ALL_PROJECTS
            : ($projects->firstWhere('id', $request->integer('project_id'))['id'] ?? null);

        $payments = $selectedProjectId !== null
            ? $history->payments($allProjects ? null : $selectedProjectId)
                ->map(fn (InvestmentPaymentRequest $payment) => [
                    ...$history->row($payment),
                    'documents' => $this->signedDocuments($payment->advance_documents),
                    'receipt_documents' => $this->signedDocuments($payment->payment_receipt_documents),
                ])
                ->values()
            : [];

        return Inertia::render('strategies/index', [
            'projects' => $projects,
            'selectedProjectId' => $selectedProjectId,
            'payments' => $payments,
        ]);
    }

    /**
     * @return array<int, array{name: string, url: string}>
     */
    private function signedDocuments(?array $paths): array
    {
        return collect($paths ?? [])
            ->filter(fn ($document) => is_string($document) && $document !== '')
            ->map(fn (string $document) => [
                'name' => basename($document),
                'url' => URL::temporarySignedRoute('documents.view', now()->addHours(48), ['path' => $document]),
            ])
            ->values()
            ->all();
    }
}
