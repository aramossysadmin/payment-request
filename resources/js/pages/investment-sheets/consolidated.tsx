import { Head, router, usePage } from '@inertiajs/react';
import { Banknote, Building2, CheckIcon, ChevronsUpDownIcon, Clock, DollarSign, Eye, FileText, Search, X, XCircle } from 'lucide-react';
import { useCallback, useEffect, useState, type FormEvent } from 'react';
import { FileUpload } from '@/components/file-upload';
import InputError from '@/components/input-error';
import { Pagination } from '@/components/pagination';
import { ProviderAutocomplete } from '@/components/provider-autocomplete';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Branch, Currency, PaginatedData } from '@/types';
import type { InvestmentRequest } from '@/types/investment-request';

type DepartmentBreakdown = {
    id: number;
    name: string;
    total: string;
    count: number;
};

type InvestmentPayment = {
    id: number;
    uuid: string;
    folio_number: number;
    provider: string;
    rfc: string | null;
    payment_type: 'factura' | 'anticipo';
    currency_prefix: string;
    subtotal: string;
    iva: string;
    total: string;
    status: string;
    user_name: string;
    created_at: string;
    approval_status: 'pending' | 'approved' | 'rejected';
};

type PaymentsSummary = {
    total_concept: string;
    total_paid: number;
    remaining: string;
    count: number;
};

type PageProps = {
    project: {
        id: number;
        name: string;
        branch: string | null;
    };
    totals: {
        subtotal: string;
        total: string;
        authorized: string;
        pending: string;
        count: number;
    };
    departmentBreakdown: DepartmentBreakdown[];
    investmentRequests: PaginatedData<InvestmentRequest>;
    filters: { search?: string; status?: string; department_id?: string };
    userDepartmentId: number;
    currencies: Currency[];
    branches: Branch[];
    errors: Record<string, string>;
};

const statusColors: Record<string, string> = {
    warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    info: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    success: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    danger: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    gray: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400',
    purple: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    orange: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
};

const ivaRateOptions = [
    { value: '0.00', label: 'IVA 0%' },
    { value: '0.08', label: 'IVA 8%' },
    { value: '0.16', label: 'IVA 16%' },
];

function formatCurrency(value: string | number): string {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(value));
}

export default function Consolidated() {
    const {
        project, totals, departmentBreakdown, investmentRequests, filters,
        userDepartmentId, currencies, branches, errors,
    } = usePage<PageProps>().props;

    const [search, setSearch] = useState(filters.search ?? '');
    const [modalOpen, setModalOpen] = useState(false);
    const [selectedIr, setSelectedIr] = useState<InvestmentRequest | null>(null);
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [drawerIr, setDrawerIr] = useState<InvestmentRequest | null>(null);
    const [payments, setPayments] = useState<InvestmentPayment[]>([]);
    const [paymentsSummary, setPaymentsSummary] = useState<PaymentsSummary | null>(null);
    const [loadingPayments, setLoadingPayments] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Hojas de Inversión', href: '/investment-sheets/consolidated' },
        { title: project.name, href: `/investment-sheets/consolidated/${project.id}` },
    ];

    const applyFilters = useCallback(
        (params: Record<string, string>) => {
            const merged = { ...filters, ...params };
            const cleaned: Record<string, string> = {};
            for (const [key, value] of Object.entries(merged)) {
                if (value) cleaned[key] = value;
            }
            router.get(`/investment-sheets/consolidated/${project.id}`, cleaned, {
                preserveState: true,
                preserveScroll: true,
            });
        },
        [filters, project.id],
    );

    const clearFilters = () => {
        setSearch('');
        router.get(`/investment-sheets/consolidated/${project.id}`, {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const hasActiveFilters = filters.search || filters.status || filters.department_id;

    const openPaymentModal = (ir: InvestmentRequest) => {
        setSelectedIr(ir);
        setModalOpen(true);
    };

    const openDrawer = (ir: InvestmentRequest) => {
        setDrawerIr(ir);
        setDrawerOpen(true);
        fetchPayments(ir.id);
    };

    const fetchPayments = (investmentRequestId: number) => {
        setLoadingPayments(true);
        fetch(`/investment-payment-requests/${investmentRequestId}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((res) => res.json())
            .then((data) => {
                setPayments(data.payments ?? []);
                setPaymentsSummary(data.summary ?? null);
            })
            .finally(() => setLoadingPayments(false));
    };

    const handlePaymentModalClose = () => {
        setModalOpen(false);
        setSelectedIr(null);
        if (drawerIr) {
            fetchPayments(drawerIr.id);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Hoja de Inversión — ${project.name}`} />

            <div className="p-4 md:p-6 space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        {project.name}
                    </h1>
                    {project.branch && (
                        <p className="mt-1 flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                            <Building2 className="h-4 w-4" />
                            {project.branch}
                        </p>
                    )}
                </div>

                {/* Summary Cards */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <div className="rounded-lg bg-blue-50 p-2 dark:bg-blue-900/20">
                                    <FileText className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">Total de Conceptos</p>
                                    <p className="text-2xl font-bold">{totals.count}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <div className="rounded-lg bg-green-50 p-2 dark:bg-green-900/20">
                                    <DollarSign className="h-5 w-5 text-green-600 dark:text-green-400" />
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">Monto Total</p>
                                    <p className="text-2xl font-bold">{formatCurrency(totals.total)}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <div className="rounded-lg bg-emerald-50 p-2 dark:bg-emerald-900/20">
                                    <DollarSign className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">Monto Autorizado</p>
                                    <p className="text-2xl font-bold">{formatCurrency(totals.authorized)}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <div className="rounded-lg bg-amber-50 p-2 dark:bg-amber-900/20">
                                    <DollarSign className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">Monto Pendiente</p>
                                    <p className="text-2xl font-bold">{formatCurrency(totals.pending)}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Department Breakdown */}
                {departmentBreakdown.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Inversión por Departamento</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                {departmentBreakdown.map((dept) => (
                                    <button
                                        key={dept.id}
                                        type="button"
                                        onClick={() => applyFilters({ department_id: filters.department_id === String(dept.id) ? '' : String(dept.id) })}
                                        className={`flex items-center justify-between rounded-lg border p-3 text-left transition-colors ${
                                            filters.department_id === String(dept.id)
                                                ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20'
                                                : 'hover:bg-gray-50 dark:hover:bg-gray-800'
                                        }`}
                                    >
                                        <div>
                                            <p className="text-sm font-medium">{dept.name}</p>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                                {dept.count} {dept.count === 1 ? 'concepto' : 'conceptos'}
                                            </p>
                                        </div>
                                        <span className="text-sm font-semibold">{formatCurrency(dept.total)}</span>
                                    </button>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Filters & Table */}
                <Card>
                    <CardHeader>
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <CardTitle>Detalle de Conceptos de Inversión</CardTitle>
                            <div className="flex flex-wrap items-end gap-3">
                                <div className="space-y-1">
                                    <label className="text-xs font-medium text-gray-500 dark:text-gray-400">Buscar</label>
                                    <div className="relative">
                                        <Search className="absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                        <Input
                                            className="pl-8 w-64"
                                            placeholder="Proveedor, folio..."
                                            value={search}
                                            onChange={(e) => setSearch(e.target.value)}
                                            onKeyDown={(e) => {
                                                if (e.key === 'Enter') applyFilters({ search });
                                            }}
                                        />
                                    </div>
                                </div>
                                <div className="space-y-1">
                                    <label className="text-xs font-medium text-gray-500 dark:text-gray-400">Departamento</label>
                                    <Select
                                        value={filters.department_id ?? 'all'}
                                        onValueChange={(v) => applyFilters({ department_id: v === 'all' ? '' : v })}
                                    >
                                        <SelectTrigger className="w-52">
                                            <SelectValue placeholder="Todos" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Todos</SelectItem>
                                            {departmentBreakdown.map((dept) => (
                                                <SelectItem key={dept.id} value={String(dept.id)}>
                                                    {dept.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-1">
                                    <label className="text-xs font-medium text-gray-500 dark:text-gray-400">Estado</label>
                                    <Select
                                        value={filters.status ?? 'all'}
                                        onValueChange={(v) => applyFilters({ status: v === 'all' ? '' : v })}
                                    >
                                        <SelectTrigger className="w-44">
                                            <SelectValue placeholder="Todos" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Todos</SelectItem>
                                            <SelectItem value="pending_department">Pendiente</SelectItem>
                                            <SelectItem value="completed">Completada</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                {hasActiveFilters && (
                                    <Button variant="ghost" size="icon" onClick={clearFilters} className="shrink-0">
                                        <X className="h-4 w-4" />
                                    </Button>
                                )}
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {investmentRequests.data.length === 0 ? (
                            <div className="py-12 text-center text-gray-500 dark:text-gray-400">
                                No se encontraron conceptos de inversión para este proyecto.
                            </div>
                        ) : (
                            <>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b text-left text-gray-500 dark:text-gray-400">
                                                <th className="pb-3 pr-4 font-medium">Folio</th>
                                                <th className="pb-3 pr-4 font-medium">Proveedor</th>
                                                <th className="pb-3 pr-4 font-medium">Departamento</th>
                                                <th className="pb-3 pr-4 font-medium">Gasto de Inversión</th>
                                                <th className="pb-3 pr-4 font-medium text-right">Total</th>
                                                <th className="pb-3 pr-4 font-medium text-right">Saldo</th>
                                                <th className="pb-3 pr-4 font-medium">Estado</th>
                                                <th className="pb-3 font-medium"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {investmentRequests.data.map((ir) => {
                                                const isCompleted = ir.status.name === 'completed';
                                                const isUserDept = ir.department?.id === userDepartmentId;
                                                const hasBalance = Number(ir.remaining_balance) > 0;
                                                const canRequestPayment = isCompleted && isUserDept && hasBalance;

                                                return (
                                                    <tr
                                                        key={ir.uuid}
                                                        className="border-b last:border-0 hover:bg-gray-50 dark:hover:bg-gray-800/50"
                                                    >
                                                        <td
                                                            className="py-3 pr-4 font-mono text-xs cursor-pointer"
                                                            onClick={() => router.visit(`/investment-sheets/${ir.uuid}`)}
                                                        >
                                                            #{String(ir.folio_number).padStart(5, '0')}
                                                        </td>
                                                        <td
                                                            className="py-3 pr-4 cursor-pointer"
                                                            onClick={() => router.visit(`/investment-sheets/${ir.uuid}`)}
                                                        >
                                                            <div className="font-medium">{ir.provider}</div>
                                                            {ir.rfc && (
                                                                <div className="text-xs text-gray-500">{ir.rfc}</div>
                                                            )}
                                                        </td>
                                                        <td className="py-3 pr-4 text-gray-600 dark:text-gray-400">
                                                            {ir.department?.name}
                                                        </td>
                                                        <td className="py-3 pr-4 text-gray-600 dark:text-gray-400">
                                                            {ir.investment_expense_concept?.name}
                                                        </td>
                                                        <td className="py-3 pr-4 text-right font-mono font-semibold">
                                                            {formatCurrency(ir.total)}
                                                        </td>
                                                        <td className="py-3 pr-4 text-right font-mono">
                                                            {formatCurrency(ir.remaining_balance)}
                                                        </td>
                                                        <td className="py-3 pr-4">
                                                            <Badge
                                                                variant="secondary"
                                                                className={statusColors[ir.status.color] ?? statusColors.gray}
                                                            >
                                                                {ir.status.label}
                                                            </Badge>
                                                        </td>
                                                        <td className="py-3">
                                                            {isCompleted && (
                                                                <Button
                                                                    size="sm"
                                                                    variant="ghost"
                                                                    onClick={(e) => {
                                                                        e.stopPropagation();
                                                                        openDrawer(ir);
                                                                    }}
                                                                >
                                                                    <Eye className="mr-1.5 h-3.5 w-3.5" />
                                                                    Pagos
                                                                </Button>
                                                            )}
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                                <div className="mt-4">
                                    <Pagination links={investmentRequests.meta.links} />
                                </div>
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Payments Drawer */}
            <PaymentsDrawer
                open={drawerOpen}
                onClose={() => { setDrawerOpen(false); setDrawerIr(null); }}
                investmentRequest={drawerIr}
                payments={payments}
                summary={paymentsSummary}
                loading={loadingPayments}
                userDepartmentId={userDepartmentId}
                onRequestPayment={(ir) => openPaymentModal(ir)}
            />

            {/* Payment Request Modal */}
            {selectedIr && (
                <PaymentRequestModal
                    open={modalOpen}
                    onClose={handlePaymentModalClose}
                    investmentRequest={selectedIr}
                    currencies={currencies}
                    branches={branches}
                    errors={errors}
                />
            )}
        </AppLayout>
    );
}

/* ─── Payments Drawer ─── */

const paymentStatusConfig: Record<string, { label: string; icon: typeof CheckIcon; color: string }> = {
    pending: { label: 'Pendiente', icon: Clock, color: 'text-yellow-600 dark:text-yellow-400' },
    approved: { label: 'Aprobado', icon: CheckIcon, color: 'text-green-600 dark:text-green-400' },
    rejected: { label: 'Rechazado', icon: XCircle, color: 'text-red-600 dark:text-red-400' },
    pending_approval: { label: 'Pendiente', icon: Clock, color: 'text-yellow-600 dark:text-yellow-400' },
};

type PaymentsDrawerProps = {
    open: boolean;
    onClose: () => void;
    investmentRequest: InvestmentRequest | null;
    payments: InvestmentPayment[];
    summary: PaymentsSummary | null;
    loading: boolean;
    userDepartmentId: number;
    onRequestPayment: (ir: InvestmentRequest) => void;
};

function PaymentsDrawer({
    open, onClose, investmentRequest: ir, payments, summary, loading, userDepartmentId, onRequestPayment,
}: PaymentsDrawerProps) {
    if (!ir) return null;

    const isUserDept = ir.department?.id === userDepartmentId;
    const hasBalance = Number(ir.remaining_balance) > 0;
    const canRequestPayment = isUserDept && hasBalance;

    const progressPercent = summary
        ? Math.min(100, (summary.total_paid / Number(summary.total_concept)) * 100)
        : 0;

    return (
        <Sheet open={open} onOpenChange={(v) => { if (!v) onClose(); }}>
            <SheetContent side="right" className="sm:max-w-lg w-full overflow-y-auto">
                <SheetHeader>
                    <SheetTitle className="flex items-center gap-2">
                        Concepto #{String(ir.folio_number).padStart(5, '0')}
                    </SheetTitle>
                    <SheetDescription>{ir.provider}{ir.rfc ? ` · ${ir.rfc}` : ''}</SheetDescription>
                </SheetHeader>

                <div className="space-y-5 px-4 pb-6">
                    {/* Summary */}
                    <div className="space-y-3 rounded-lg border p-4">
                        <div className="flex justify-between text-sm">
                            <span className="text-muted-foreground">Total del concepto</span>
                            <span className="font-mono font-semibold">{formatCurrency(ir.total)}</span>
                        </div>
                        {summary && (
                            <>
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Total pagado / solicitado</span>
                                    <span className="font-mono font-medium text-blue-600 dark:text-blue-400">{formatCurrency(summary.total_paid)}</span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Saldo disponible</span>
                                    <span className={cn('font-mono font-semibold', Number(summary.remaining) > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400')}>
                                        {formatCurrency(summary.remaining)}
                                    </span>
                                </div>
                                {/* Progress bar */}
                                <div className="space-y-1">
                                    <div className="h-2.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                        <div
                                            className={cn(
                                                'h-full rounded-full transition-all',
                                                progressPercent >= 100
                                                    ? 'bg-red-500'
                                                    : progressPercent >= 75
                                                      ? 'bg-yellow-500'
                                                      : 'bg-green-500',
                                            )}
                                            style={{ width: `${progressPercent}%` }}
                                        />
                                    </div>
                                    <p className="text-right text-xs text-muted-foreground">
                                        {progressPercent.toFixed(0)}% consumido · {summary.count} {summary.count === 1 ? 'pago' : 'pagos'}
                                    </p>
                                </div>
                            </>
                        )}
                    </div>

                    {/* Action button */}
                    {canRequestPayment && (
                        <Button className="w-full" onClick={() => onRequestPayment(ir)}>
                            <Banknote className="mr-2 h-4 w-4" />
                            Solicitar Pago
                        </Button>
                    )}

                    {/* Payments List */}
                    <div>
                        <h3 className="mb-3 text-sm font-semibold text-foreground">Pagos registrados</h3>

                        {loading ? (
                            <div className="space-y-3">
                                {[1, 2].map((i) => (
                                    <div key={i} className="animate-pulse rounded-lg border p-4">
                                        <div className="h-4 w-2/3 rounded bg-gray-200 dark:bg-gray-700" />
                                        <div className="mt-2 h-3 w-1/2 rounded bg-gray-200 dark:bg-gray-700" />
                                        <div className="mt-2 h-3 w-1/3 rounded bg-gray-200 dark:bg-gray-700" />
                                    </div>
                                ))}
                            </div>
                        ) : payments.length === 0 ? (
                            <div className="rounded-lg border border-dashed py-8 text-center">
                                <Banknote className="mx-auto mb-2 h-8 w-8 text-gray-300 dark:text-gray-600" />
                                <p className="text-sm text-muted-foreground">No hay pagos registrados</p>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {payments.map((payment) => {
                                    const statusConf = paymentStatusConfig[payment.approval_status] ?? paymentStatusConfig.pending;
                                    const StatusIcon = statusConf.icon;

                                    return (
                                        <div key={payment.id} className="rounded-lg border p-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                            <div className="flex items-start justify-between">
                                                <div className="min-w-0 flex-1">
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-mono text-xs font-medium">
                                                            #{String(payment.folio_number).padStart(5, '0')}
                                                        </span>
                                                        <Badge variant="outline" className="text-xs">
                                                            {payment.payment_type === 'factura' ? 'Factura' : 'Anticipo'}
                                                        </Badge>
                                                    </div>
                                                    <p className="mt-1 text-sm font-medium">{payment.provider}</p>
                                                    {payment.rfc && (
                                                        <p className="text-xs text-muted-foreground">{payment.rfc}</p>
                                                    )}
                                                    <p className="mt-1 text-xs text-muted-foreground">
                                                        {payment.user_name} · {new Date(payment.created_at).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' })}
                                                    </p>
                                                </div>
                                                <div className="text-right">
                                                    <p className="font-mono text-sm font-semibold">{formatCurrency(payment.total)}</p>
                                                    <div className={cn('mt-1 flex items-center justify-end gap-1 text-xs', statusConf.color)}>
                                                        <StatusIcon className="h-3.5 w-3.5" />
                                                        {statusConf.label}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    );
}

/* ─── Payment Request Modal ─── */

type PaymentRequestModalProps = {
    open: boolean;
    onClose: () => void;
    investmentRequest: InvestmentRequest;
    currencies: Currency[];
    branches: Branch[];
    errors: Record<string, string>;
};

function PaymentRequestModal({
    open, onClose, investmentRequest: ir,
    currencies, branches, errors,
}: PaymentRequestModalProps) {
    const [values, setValues] = useState({
        provider: ir.provider ?? '',
        rfc: ir.rfc ?? '',
        invoice_folio: '',
        currency_id: ir.currency?.id ? String(ir.currency.id) : '',
        branch_id: ir.branch?.id ? String(ir.branch.id) : '',
        is_invoice: false,
        description: '',
        subtotal: '',
        iva_rate: '',
        iva: '',
        retention: false,
        total: '',
    });

    const [files, setFiles] = useState<File[]>([]);
    const [invoicePdf, setInvoicePdf] = useState<File | null>(null);
    const [invoiceXml, setInvoiceXml] = useState<File | null>(null);
    const [processing, setProcessing] = useState(false);
    const [branchOpen, setBranchOpen] = useState(false);

    const remainingBalance = Number(ir.remaining_balance);

    const recalculate = (subtotal: number, ivaRate: number) => {
        const iva = Math.round(subtotal * ivaRate * 100) / 100;
        const total = Math.round((subtotal + iva) * 100) / 100;
        return { iva: iva.toFixed(2), total: total.toFixed(2) };
    };

    const handleChange = (field: string, value: string) => {
        if (field === 'subtotal') {
            const subtotal = parseFloat(value) || 0;
            const { iva, total } = recalculate(subtotal, parseFloat(values.iva_rate) || 0);
            setValues((prev) => ({ ...prev, subtotal: value, iva, total }));
            return;
        }
        if (field === 'iva_rate') {
            const subtotal = parseFloat(values.subtotal) || 0;
            const { iva, total } = recalculate(subtotal, parseFloat(value) || 0);
            setValues((prev) => ({ ...prev, iva_rate: value, iva, total }));
            return;
        }
        setValues((prev) => ({ ...prev, [field]: value }));
    };

    const toggleIsInvoice = (checked: boolean) => {
        setValues((prev) => ({ ...prev, is_invoice: checked }));
        setFiles([]);
        setInvoicePdf(null);
        setInvoiceXml(null);
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        const formData = new FormData();
        formData.append('investment_request_id', String(ir.id));
        Object.entries(values).forEach(([key, val]) => {
            formData.append(key, typeof val === 'boolean' ? (val ? '1' : '0') : String(val));
        });

        if (values.is_invoice) {
            if (invoicePdf) formData.append('invoice_documents[]', invoicePdf);
            if (invoiceXml) formData.append('invoice_documents[]', invoiceXml);
        } else {
            files.forEach((file) => formData.append('advance_documents[]', file));
        }

        router.post('/investment-payment-requests', formData, {
            forceFormData: true,
            onSuccess: () => onClose(),
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={(v) => { if (!v) onClose(); }}>
            <DialogContent className="sm:max-w-4xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Solicitar Pago de Inversión</DialogTitle>
                    <DialogDescription>
                        Concepto #{String(ir.folio_number).padStart(5, '0')} — {ir.provider}
                        {' · '}Saldo disponible: <span className="font-semibold">{formatCurrency(remainingBalance)}</span>
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Row 1: Información del Proveedor + Datos Generales */}
                    <div className="grid gap-6 lg:grid-cols-2">
                        {/* Información del Proveedor */}
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base">Información del Proveedor</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {ir.investment_expense_concept?.name && (
                                    <div className="rounded-md border border-blue-200 bg-blue-50 px-3 py-2 dark:border-blue-800 dark:bg-blue-900/20">
                                        <p className="text-xs font-medium text-blue-600 dark:text-blue-400">Gasto de Inversión</p>
                                        <p className="text-sm font-semibold text-blue-900 dark:text-blue-100">{ir.investment_expense_concept.name}</p>
                                    </div>
                                )}
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="modal_provider">Razón Social</Label>
                                        <ProviderAutocomplete
                                            id="modal_provider"
                                            value={values.provider}
                                            field="provider"
                                            placeholder="Razón Social"
                                            onChange={(v) => handleChange('provider', v)}
                                            onSelect={(s) => setValues((prev) => ({ ...prev, provider: s.provider, rfc: s.rfc ?? prev.rfc }))}
                                        />
                                        <InputError message={errors.provider} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="modal_rfc">RFC</Label>
                                        <ProviderAutocomplete
                                            id="modal_rfc"
                                            value={values.rfc}
                                            field="rfc"
                                            placeholder="RFC"
                                            maxLength={13}
                                            onChange={(v) => handleChange('rfc', v)}
                                            onSelect={(s) => setValues((prev) => ({ ...prev, provider: s.provider, rfc: s.rfc ?? prev.rfc }))}
                                        />
                                        <InputError message={errors.rfc} />
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="modal_invoice_folio">Folio de Factura <span className="text-gray-400">(opcional)</span></Label>
                                    <Input
                                        id="modal_invoice_folio"
                                        value={values.invoice_folio}
                                        onChange={(e) => handleChange('invoice_folio', e.target.value)}
                                        placeholder="FAC-0001 / COT-0001"
                                    />
                                    <InputError message={errors.invoice_folio} />
                                </div>
                            </CardContent>
                        </Card>

                        {/* Datos Generales */}
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base">Datos Generales</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label>Sucursal</Label>
                                    <Popover open={branchOpen} onOpenChange={setBranchOpen}>
                                        <PopoverTrigger asChild>
                                            <Button variant="outline" role="combobox" aria-expanded={branchOpen} className="w-full justify-between font-normal">
                                                {branches.find((b) => String(b.id) === values.branch_id)?.name ?? 'Seleccionar'}
                                                <ChevronsUpDownIcon className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent className="w-[--radix-popover-trigger-width] p-0" align="start">
                                            <Command>
                                                <CommandInput placeholder="Buscar sucursal..." />
                                                <CommandList>
                                                    <CommandEmpty>Sin resultados.</CommandEmpty>
                                                    <CommandGroup>
                                                        {branches.map((b) => (
                                                            <CommandItem key={b.id} value={b.name} onSelect={() => { handleChange('branch_id', String(b.id)); setBranchOpen(false); }}>
                                                                <CheckIcon className={cn('mr-2 h-4 w-4', values.branch_id === String(b.id) ? 'opacity-100' : 'opacity-0')} />
                                                                {b.name}
                                                            </CommandItem>
                                                        ))}
                                                    </CommandGroup>
                                                </CommandList>
                                            </Command>
                                        </PopoverContent>
                                    </Popover>
                                    <InputError message={errors.branch_id} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="modal_description">Descripción <span className="text-gray-400">(opcional)</span></Label>
                                    <textarea
                                        id="modal_description"
                                        className="border-input focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:outline-none"
                                        rows={3}
                                        value={values.description}
                                        onChange={(e) => handleChange('description', e.target.value)}
                                        placeholder="Notas adicionales..."
                                    />
                                    <InputError message={errors.description} />
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Row 2: Montos + Documentos */}
                    <div className="grid gap-6 lg:grid-cols-2">
                        {/* Montos */}
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base">Montos</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label>Moneda</Label>
                                    <Select value={values.currency_id} onValueChange={(v) => handleChange('currency_id', v)}>
                                        <SelectTrigger><SelectValue placeholder="Seleccionar" /></SelectTrigger>
                                        <SelectContent>
                                            {currencies.map((c) => (
                                                <SelectItem key={c.id} value={String(c.id)}>{c.prefix}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.currency_id} />
                                </div>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="modal_subtotal">Subtotal</Label>
                                        <div className="relative">
                                            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">$</span>
                                            <Input
                                                id="modal_subtotal"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                className="pl-7"
                                                value={values.subtotal}
                                                onChange={(e) => handleChange('subtotal', e.target.value)}
                                                placeholder="0.00"
                                            />
                                        </div>
                                        <InputError message={errors.subtotal} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Tasa de IVA</Label>
                                        <Select value={values.iva_rate} onValueChange={(v) => handleChange('iva_rate', v)}>
                                            <SelectTrigger><SelectValue placeholder="Seleccionar" /></SelectTrigger>
                                            <SelectContent>
                                                {ivaRateOptions.map((opt) => (
                                                    <SelectItem key={opt.value} value={opt.value}>{opt.label}</SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.iva_rate} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="modal_iva">{ivaRateOptions.find((o) => o.value === values.iva_rate)?.label ?? 'IVA'}</Label>
                                        <div className="relative">
                                            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">$</span>
                                            <Input
                                                id="modal_iva"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                className="bg-gray-50 pl-7 dark:bg-gray-800"
                                                value={values.iva}
                                                readOnly
                                                tabIndex={-1}
                                                placeholder="0.00"
                                            />
                                        </div>
                                        <InputError message={errors.iva} />
                                    </div>
                                    <div className="flex items-center gap-2 self-end pb-2">
                                        <Checkbox
                                            id="modal_retention"
                                            checked={values.retention as boolean}
                                            onCheckedChange={(checked) => setValues((prev) => ({ ...prev, retention: checked === true }))}
                                        />
                                        <Label htmlFor="modal_retention" className="cursor-pointer">Aplica retención</Label>
                                        <InputError message={errors.retention} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="modal_total">Total</Label>
                                        <div className="relative">
                                            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">$</span>
                                            <Input
                                                id="modal_total"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                className="bg-gray-50 pl-7 dark:bg-gray-800"
                                                value={values.total}
                                                readOnly
                                                tabIndex={-1}
                                                placeholder="0.00"
                                            />
                                        </div>
                                        {Number(values.total) > remainingBalance && (
                                            <div className="flex items-start gap-2 rounded-md border border-red-300 bg-red-50 px-3 py-2 dark:border-red-800 dark:bg-red-900/20">
                                                <span className="mt-0.5 text-red-500">&#9888;</span>
                                                <div className="text-xs text-red-700 dark:text-red-300">
                                                    <p className="font-semibold">El total excede el saldo disponible</p>
                                                    <p>Saldo: {formatCurrency(remainingBalance)} · Excedente: {formatCurrency(Number(values.total) - remainingBalance)}</p>
                                                </div>
                                            </div>
                                        )}
                                        <InputError message={errors.total} />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Documentos */}
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base">Documentos Adjuntos</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center gap-2">
                                    <Checkbox
                                        id="modal_is_invoice"
                                        checked={values.is_invoice}
                                        onCheckedChange={(checked) => toggleIsInvoice(checked === true)}
                                    />
                                    <Label htmlFor="modal_is_invoice" className="cursor-pointer">
                                        Factura
                                    </Label>
                                </div>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    {values.is_invoice
                                        ? 'Adjunta el PDF y XML de la factura.'
                                        : 'Adjunta los documentos de soporte para el anticipo.'}
                                </p>

                                {values.is_invoice ? (
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label>Factura PDF <span className="text-red-500">*</span></Label>
                                            <FileUpload
                                                files={invoicePdf ? [invoicePdf] : []}
                                                onChange={(f) => setInvoicePdf(f[0] ?? null)}
                                                maxFiles={1}
                                                accept=".pdf"
                                                error={errors['invoice_documents'] || errors['invoice_documents.0']}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Factura XML <span className="text-red-500">*</span></Label>
                                            <FileUpload
                                                files={invoiceXml ? [invoiceXml] : []}
                                                onChange={(f) => setInvoiceXml(f[0] ?? null)}
                                                maxFiles={1}
                                                accept=".xml"
                                                error={errors['invoice_documents.1']}
                                            />
                                        </div>
                                    </div>
                                ) : (
                                    <FileUpload
                                        files={files}
                                        onChange={setFiles}
                                        maxFiles={10}
                                        error={errors.advance_documents || errors['advance_documents.0']}
                                    />
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Actions */}
                    <div className="flex justify-end gap-3">
                        <Button type="button" variant="outline" onClick={onClose}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={processing || Number(values.total) > remainingBalance}>
                            {processing ? 'Enviando...' : 'Solicitar Pago'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
