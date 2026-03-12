<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentRequestResource;
use App\Models\PaymentRequest;
use App\States\PaymentRequest\PendingAdministration;
use App\States\PaymentRequest\PendingDepartment;
use App\States\PaymentRequest\PendingTreasury;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $isAuthorizer = $user->authorizedDepartments()->exists();
        $isSuperAdmin = $user->hasRole('super_admin');

        return Inertia::render('dashboard', [
            'isAuthorizer' => $isAuthorizer,
            'isSuperAdmin' => $isSuperAdmin,
            'stats' => Inertia::defer(fn () => $this->getStats($user)),
            'recentRequests' => Inertia::defer(fn () => $this->getRecentRequests($user)),
            'pendingApprovals' => Inertia::defer(fn () => $this->getPendingApprovals($user, $isAuthorizer)),
            'chartData' => Inertia::defer(fn () => $this->getChartData($user), 'chart'),
        ]);
    }

    /**
     * @return array{pendingCount: int, pendingByStage: array<string, int>, pendingApprovalCount: int, monthlyTotal: string}
     */
    private function getStats(mixed $user): array
    {
        $baseQuery = PaymentRequest::query()->visibleTo($user);

        $pendingCount = (clone $baseQuery)->whereIn('status', [
            PendingDepartment::$name,
            PendingAdministration::$name,
            PendingTreasury::$name,
        ])->count();

        $pendingByStage = [
            'department' => (clone $baseQuery)->whereState('status', PendingDepartment::$name)->count(),
            'administration' => (clone $baseQuery)->whereState('status', PendingAdministration::$name)->count(),
            'treasury' => (clone $baseQuery)->whereState('status', PendingTreasury::$name)->count(),
        ];

        $pendingApprovalCount = $user->authorizedDepartments()->exists()
            ? PaymentRequest::query()
                ->visibleTo($user)
                ->whereHas('approvals', fn ($q) => $q->where('user_id', $user->id)->where('status', 'pending'))
                ->count()
            : 0;

        $monthlyTotal = (clone $baseQuery)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');

        return [
            'pendingCount' => $pendingCount,
            'pendingByStage' => $pendingByStage,
            'pendingApprovalCount' => $pendingApprovalCount,
            'monthlyTotal' => number_format((float) $monthlyTotal, 2, '.', ''),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function getRecentRequests(mixed $user): array
    {
        $requests = PaymentRequest::query()
            ->visibleTo($user)
            ->with(['user', 'department', 'currency', 'branch', 'expenseConcept', 'approvals.user'])
            ->latest()
            ->limit(10)
            ->get();

        return PaymentRequestResource::collection($requests)->resolve();
    }

    /**
     * @return array<int, mixed>
     */
    private function getPendingApprovals(mixed $user, bool $isAuthorizer): array
    {
        if (! $isAuthorizer) {
            return [];
        }

        $requests = PaymentRequest::query()
            ->visibleTo($user)
            ->whereHas('approvals', fn ($q) => $q->where('user_id', $user->id)->where('status', 'pending'))
            ->with(['user', 'department', 'currency', 'branch', 'expenseConcept', 'approvals.user'])
            ->latest()
            ->limit(5)
            ->get();

        return PaymentRequestResource::collection($requests)->resolve();
    }

    /**
     * @return array<int, array{month: string, count: int}>
     */
    private function getChartData(mixed $user): array
    {
        $months = collect();
        $monthNames = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        ];

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $count = PaymentRequest::query()
                ->visibleTo($user)
                ->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();

            $months->push([
                'month' => $monthNames[$date->month],
                'count' => $count,
            ]);
        }

        return $months->all();
    }
}
