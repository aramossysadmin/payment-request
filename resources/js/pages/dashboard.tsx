import { MonthlyChart } from '@/components/monthly-chart';
import { StatCard } from '@/components/stat-card';
import { StatusBadge } from '@/components/status-badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { create as createRoute } from '@/routes/payment-requests';
import type { BreadcrumbItem, DashboardPageProps, PaymentRequest } from '@/types';
import { Deferred, Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

function formatCurrency(value: string | number): string {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
    }).format(Number(value));
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('es-MX', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

function StatsSkeleton({ isAuthorizer }: { isAuthorizer: boolean }) {
    return (
        <div className={`grid gap-4 ${isAuthorizer ? 'md:grid-cols-3' : 'md:grid-cols-2'}`}>
            {Array.from({ length: isAuthorizer ? 3 : 2 }).map((_, i) => (
                <Card key={i}>
                    <CardHeader className="pb-2">
                        <Skeleton className="h-4 w-24" />
                    </CardHeader>
                    <CardContent>
                        <Skeleton className="h-8 w-16" />
                        <Skeleton className="mt-2 h-3 w-32" />
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}

function ChartSkeleton() {
    return (
        <Card className="flex-1">
            <CardHeader>
                <Skeleton className="h-5 w-40" />
            </CardHeader>
            <CardContent>
                <Skeleton className="h-[280px] w-full" />
            </CardContent>
        </Card>
    );
}

function PendingApprovalsSkeleton() {
    return (
        <Card className="w-full lg:w-80">
            <CardHeader>
                <Skeleton className="h-5 w-44" />
            </CardHeader>
            <CardContent className="flex flex-col gap-3">
                {Array.from({ length: 3 }).map((_, i) => (
                    <Skeleton key={i} className="h-14 w-full" />
                ))}
            </CardContent>
        </Card>
    );
}

function RecentTableSkeleton() {
    return (
        <Card>
            <CardHeader>
                <Skeleton className="h-5 w-44" />
            </CardHeader>
            <CardContent>
                <div className="flex flex-col gap-3">
                    {Array.from({ length: 5 }).map((_, i) => (
                        <Skeleton key={i} className="h-10 w-full" />
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}

function RecentRequestsTable({ requests }: { requests: PaymentRequest[] }) {
    if (requests.length === 0) {
        return <p className="text-muted-foreground py-8 text-center text-sm">No hay solicitudes recientes.</p>;
    }

    return (
        <div className="-mx-6 overflow-x-auto">
            <table className="w-full min-w-[600px] text-sm">
                <thead>
                    <tr className="border-b">
                        <th className="text-muted-foreground px-6 py-3 text-left text-xs font-medium tracking-wider uppercase">Folio</th>
                        <th className="text-muted-foreground px-6 py-3 text-left text-xs font-medium tracking-wider uppercase">Razón Social</th>
                        <th className="text-muted-foreground px-6 py-3 text-right text-xs font-medium tracking-wider uppercase">Monto</th>
                        <th className="text-muted-foreground px-6 py-3 text-left text-xs font-medium tracking-wider uppercase">Fecha</th>
                        <th className="text-muted-foreground px-6 py-3 text-right text-xs font-medium tracking-wider uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody className="divide-y">
                    {requests.map((request) => (
                        <tr key={request.uuid} className="hover:bg-muted/50 transition-colors">
                            <td className="whitespace-nowrap px-6 py-3">
                                <Link
                                    href={`/payment-requests/${request.uuid}`}
                                    className="text-primary font-mono text-xs font-semibold hover:underline"
                                >
                                    #{String(request.folio_number).padStart(5, '0')}
                                </Link>
                            </td>
                            <td className="px-6 py-3">
                                <span className="text-foreground block max-w-[200px] truncate font-medium">{request.provider}</span>
                            </td>
                            <td className="text-foreground whitespace-nowrap px-6 py-3 text-right font-mono font-medium">
                                {formatCurrency(request.total)}
                            </td>
                            <td className="text-muted-foreground whitespace-nowrap px-6 py-3 text-sm">
                                {formatDate(request.created_at)}
                            </td>
                            <td className="whitespace-nowrap px-6 py-3 text-right">
                                <StatusBadge status={request.status} size="sm" />
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function PendingApprovalsList({ approvals }: { approvals: PaymentRequest[] }) {
    if (approvals.length === 0) {
        return <p className="text-muted-foreground py-4 text-center text-sm">Sin aprobaciones pendientes.</p>;
    }

    return (
        <div className="flex flex-col gap-2">
            {approvals.map((request) => (
                <Link
                    key={request.uuid}
                    href={`/payment-requests/${request.uuid}`}
                    className="hover:bg-muted/50 flex items-center justify-between rounded-lg border p-3 transition-colors"
                >
                    <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-medium">#{request.folio_number}</p>
                        <p className="text-muted-foreground truncate text-xs">{request.provider}</p>
                    </div>
                    <span className="ml-2 text-sm font-medium">{formatCurrency(request.total)}</span>
                </Link>
            ))}
        </div>
    );
}

export default function Dashboard({ isAuthorizer, stats, recentRequests, pendingApprovals, chartData }: DashboardPageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                {/* Stats */}
                <Deferred data="stats" fallback={<StatsSkeleton isAuthorizer={isAuthorizer} />}>
                    <div className={`grid gap-4 ${isAuthorizer ? 'md:grid-cols-3' : 'md:grid-cols-2'}`}>
                        <StatCard
                            title="Solicitudes Pendientes"
                            value={stats?.pendingCount ?? 0}
                            description={
                                stats
                                    ? `Depto: ${stats.pendingByStage.department} | Admin: ${stats.pendingByStage.administration} | Teso: ${stats.pendingByStage.treasury}`
                                    : undefined
                            }
                        />
                        {isAuthorizer && (
                            <StatCard title="Solicitudes por Aprobar" value={stats?.pendingApprovalCount ?? 0} description="Pendientes de tu aprobación" />
                        )}
                        <StatCard
                            title="Monto Total del Mes"
                            value={stats ? formatCurrency(stats.monthlyTotal) : '$0.00'}
                            description="Solicitudes creadas este mes"
                        />
                    </div>
                </Deferred>

                {/* Quick action */}
                <div className="flex justify-end">
                    <Link
                        href={createRoute().url}
                        className="bg-primary text-primary-foreground hover:bg-primary/90 inline-flex items-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-colors"
                    >
                        + Nueva Solicitud de Pago
                    </Link>
                </div>

                {/* Chart + Pending Approvals */}
                <div className={`flex flex-col gap-4 ${isAuthorizer ? 'lg:flex-row' : ''}`}>
                    <Deferred data="chartData" fallback={<ChartSkeleton />}>
                        <Card className="flex-1">
                            <CardHeader>
                                <CardTitle>Solicitudes por Mes</CardTitle>
                            </CardHeader>
                            <CardContent>{chartData && <MonthlyChart data={chartData} />}</CardContent>
                        </Card>
                    </Deferred>

                    {isAuthorizer && (
                        <Deferred data="pendingApprovals" fallback={<PendingApprovalsSkeleton />}>
                            <Card className="w-full lg:w-80">
                                <CardHeader>
                                    <CardTitle>Pendientes de Aprobación</CardTitle>
                                </CardHeader>
                                <CardContent>{pendingApprovals && <PendingApprovalsList approvals={pendingApprovals} />}</CardContent>
                            </Card>
                        </Deferred>
                    )}
                </div>

                {/* Recent Requests */}
                <Deferred data="recentRequests" fallback={<RecentTableSkeleton />}>
                    <Card>
                        <CardHeader>
                            <CardTitle>Solicitudes Recientes</CardTitle>
                        </CardHeader>
                        <CardContent>{recentRequests && <RecentRequestsTable requests={recentRequests} />}</CardContent>
                    </Card>
                </Deferred>
            </div>
        </AppLayout>
    );
}
