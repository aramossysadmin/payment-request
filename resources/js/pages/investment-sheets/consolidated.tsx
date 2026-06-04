import { Head, router, usePage } from '@inertiajs/react';
import { Banknote, Building2, CheckIcon, ChevronDown, ChevronRight, ChevronsUpDownIcon, Clock, DollarSign, Download, Eye, FileText, Inbox, Pencil, Search, Send, Trash2, Upload, X, XCircle } from 'lucide-react';
import React, { useCallback, useEffect, useRef, useState, type FormEvent } from 'react';
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
    paid: string;
    pending: string;
    percent_paid: number;
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

type DraftPayment = {
    id: number;
    uuid: string;
    folio_number: number;
    provider: string;
    rfc: string | null;
    invoice_folio: string | null;
    concept_name: string;
    concept_folio: number | null;
    description: string | null;
    currency_prefix: string;
    currency_id: number;
    branch_id: number;
    investment_request_id: number;
    payment_provision_date: string | null;
    is_invoice: boolean;
    iva_rate: string | null;
    retention: boolean;
    subtotal: string;
    iva: string;
    total: string;
    advance_documents: string[];
    concept_effective_remaining: string | null;
};

type DraftBatch = {
    uuid: string;
    week_number: number;
    year: number;
    payments: DraftPayment[];
    total: string;
};

type AuthorizedPayment = {
    id: number;
    uuid: string;
    folio_number: number;
    provider: string;
    concept_name: string;
    description: string | null;
    currency_prefix: string;
    total: string;
    approved_amount: string;
    was_adjusted: boolean;
    status: string;
    has_documents: boolean;
};

type AuthorizedPaymentsGroup = {
    payments: AuthorizedPayment[];
};

type HistoryPayment = {
    id: number;
    uuid: string;
    folio_number: number;
    provider: string;
    rfc: string | null;
    concept_name: string;
    concept_folio: number | null;
    branch: string;
    payment_type: string;
    payment_provision_date: string | null;
    description: string | null;
    currency_prefix: string;
    subtotal: string;
    iva: string;
    total: string;
    approved_amount: string | null;
    was_adjusted: boolean;
    status: string;
    is_legacy: boolean;
    ceo_rejection_reason: string | null;
    pm_rejection_reason: string | null;
    final_rejection_reason: string | null;
    created_at: string | null;
    ceo_reviewed_at: string | null;
    pm_reviewed_at: string | null;
    final_reviewed_at: string | null;
    has_documents: boolean;
    documents: { name: string; url: string }[];
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
    userDepartmentName: string | null;
    currencies: Currency[];
    branches: Branch[];
    errors: Record<string, string>;
    draftBatch: DraftBatch | null;
    authorizedPayments: AuthorizedPaymentsGroup;
    userPaymentHistory: HistoryPayment[];
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
    { value: '0.21', label: 'IVA 21%' },
];

function formatCurrency(value: string | number): string {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(value));
}

type ConceptGroup = {
    key: string;
    conceptName: string;
    departmentName: string;
    providerLabel: string;
    groupBudget: string;
    groupRemaining: string;
    allCompleted: boolean;
    items: InvestmentRequest[];
};

function groupByConcept(items: InvestmentRequest[]): ConceptGroup[] {
    const map = new Map<string, InvestmentRequest[]>();

    for (const ir of items) {
        const conceptId = ir.investment_expense_concept?.id ?? 0;
        const deptId = ir.department?.id ?? 0;
        const key = `${conceptId}-${deptId}`;
        if (!map.has(key)) map.set(key, []);
        map.get(key)!.push(ir);
    }

    return Array.from(map.entries()).map(([key, groupItems]) => {
        const first = groupItems[0];
        const providers = new Set(groupItems.map((ir) => ir.provider));
        const providerLabel = providers.size === 1 ? first.provider : 'Proveedores varios';
        const allCompleted = groupItems.every((ir) => ir.status.name === 'completed');

        return {
            key,
            conceptName: first.investment_expense_concept?.name ?? '—',
            departmentName: first.department?.name ?? '—',
            providerLabel,
            groupBudget: first.group_budget ?? first.total,
            groupRemaining: first.group_remaining ?? first.remaining_balance,
            allCompleted,
            items: groupItems,
        };
    });
}

export default function Consolidated() {
    const showProvider = false;

    const {
        project, totals, departmentBreakdown, investmentRequests, filters,
        userDepartmentId, userDepartmentName, currencies, branches, errors, draftBatch, authorizedPayments, userPaymentHistory,
    } = usePage<PageProps>().props;

    const [search, setSearch] = useState(filters.search ?? '');
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const [modalOpen, setModalOpen] = useState(false);
    const [selectedIr, setSelectedIr] = useState<InvestmentRequest | null>(null);
    const [editingDraftPayment, setEditingDraftPayment] = useState<DraftPayment | null>(null);
    const [expandedGroups, setExpandedGroups] = useState<Set<string>>(new Set());

    const groups = groupByConcept(investmentRequests.data);

    const toggleGroup = (key: string) => {
        setExpandedGroups((prev) => {
            const next = new Set(prev);
            if (next.has(key)) next.delete(key);
            else next.add(key);
            return next;
        });
    };
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [drawerIr, setDrawerIr] = useState<InvestmentRequest | null>(null);
    const [payments, setPayments] = useState<InvestmentPayment[]>([]);
    const [paymentsSummary, setPaymentsSummary] = useState<PaymentsSummary | null>(null);
    const [loadingPayments, setLoadingPayments] = useState(false);

    const [selectedDraftIds, setSelectedDraftIds] = useState<Set<string>>(() =>
        new Set((draftBatch?.payments ?? []).map((p) => p.uuid)),
    );
    const [deleteConfirmUuid, setDeleteConfirmUuid] = useState<string | null>(null);

    useEffect(() => {
        setSelectedDraftIds(new Set((draftBatch?.payments ?? []).map((p) => p.uuid)));
    }, [draftBatch]);

    const toggleDraftSelection = (uuid: string) => {
        setSelectedDraftIds((prev) => {
            const next = new Set(prev);
            if (next.has(uuid)) next.delete(uuid);
            else next.add(uuid);
            return next;
        });
    };

    const handleDeleteDraftPayment = (uuid: string) => {
        router.delete(`/investment-payment-batches/payments/${uuid}`, {
            preserveScroll: true,
            onFinish: () => setDeleteConfirmUuid(null),
        });
    };

    const [submittingBatch, setSubmittingBatch] = useState(false);
    const handleSubmitBatch = () => {
        if (!draftBatch || selectedDraftIds.size === 0) return;
        setSubmittingBatch(true);
        router.post(
            `/investment-payment-batches/${draftBatch.uuid}/submit`,
            { payment_uuids: Array.from(selectedDraftIds) },
            {
                preserveScroll: true,
                onFinish: () => setSubmittingBatch(false),
            },
        );
    };

    const [uploadDialogUuid, setUploadDialogUuid] = useState<string | null>(null);
    const [uploadPdf, setUploadPdf] = useState<File | null>(null);
    const [uploadXml, setUploadXml] = useState<File | null>(null);
    const [uploading, setUploading] = useState(false);

    const handleUploadDocuments = () => {
        if (!uploadDialogUuid || !uploadPdf || !uploadXml) return;
        setUploading(true);

        const formData = new FormData();
        formData.append('pdf', uploadPdf);
        formData.append('xml', uploadXml);

        router.post(
            `/investment-payment-requests/${uploadDialogUuid}/upload-documents`,
            formData,
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    setUploadDialogUuid(null);
                    setUploadPdf(null);
                    setUploadXml(null);
                },
                onFinish: () => setUploading(false),
            },
        );
    };

    const closeUploadDialog = () => {
        setUploadDialogUuid(null);
        setUploadPdf(null);
        setUploadXml(null);
    };

    // Historial de pagos — estado de filtros + paginación + drawer
    const [historySearch, setHistorySearch] = useState('');
    const [historyStatus, setHistoryStatus] = useState<string>('all');
    const [historyDateFrom, setHistoryDateFrom] = useState('');
    const [historyDateTo, setHistoryDateTo] = useState('');
    const [historyQuickFilter, setHistoryQuickFilter] = useState<'all' | 'in_process' | 'completed' | 'rejected'>('all');
    const [historyPage, setHistoryPage] = useState(1);
    const [historyDetailUuid, setHistoryDetailUuid] = useState<string | null>(null);
    const HISTORY_PER_PAGE = 20;

    const historyStatusGroups: Record<'in_process' | 'completed' | 'rejected', string[]> = {
        in_process: ['submitted', 'ceo_approved', 'projectmanager_review', 'projectmanager_approved', 'final_pending', 'documents_pending', 'pending_approval'],
        completed: ['final_approved', 'completed', 'approved'],
        rejected: ['ceo_rejected', 'projectmanager_rejected', 'final_rejected', 'rejected'],
    };

    const historyCounts = {
        all: userPaymentHistory.length,
        in_process: userPaymentHistory.filter((p) => historyStatusGroups.in_process.includes(p.status)).length,
        completed: userPaymentHistory.filter((p) => historyStatusGroups.completed.includes(p.status)).length,
        rejected: userPaymentHistory.filter((p) => historyStatusGroups.rejected.includes(p.status)).length,
    };

    const filteredHistory = userPaymentHistory.filter((p) => {
        // Quick filter chip
        if (historyQuickFilter !== 'all' && ! historyStatusGroups[historyQuickFilter].includes(p.status)) {
            return false;
        }
        // Status filter dropdown
        if (historyStatus !== 'all' && p.status !== historyStatus) {
            return false;
        }
        // Date range
        if (historyDateFrom && p.created_at && p.created_at.slice(0, 10) < historyDateFrom) {
            return false;
        }
        if (historyDateTo && p.created_at && p.created_at.slice(0, 10) > historyDateTo) {
            return false;
        }
        // Free search (folio, provider, concept)
        if (historySearch) {
            const q = historySearch.toLowerCase();
            const folioStr = String(p.folio_number).padStart(5, '0');
            if (
                !folioStr.includes(q)
                && !p.provider.toLowerCase().includes(q)
                && !p.concept_name.toLowerCase().includes(q)
            ) {
                return false;
            }
        }
        return true;
    });

    const historyTotalPages = Math.max(1, Math.ceil(filteredHistory.length / HISTORY_PER_PAGE));
    const historyCurrentPage = Math.min(historyPage, historyTotalPages);
    const paginatedHistory = filteredHistory.slice(
        (historyCurrentPage - 1) * HISTORY_PER_PAGE,
        historyCurrentPage * HISTORY_PER_PAGE,
    );

    const clearHistoryFilters = () => {
        setHistorySearch('');
        setHistoryStatus('all');
        setHistoryDateFrom('');
        setHistoryDateTo('');
        setHistoryQuickFilter('all');
        setHistoryPage(1);
    };

    const hasActiveHistoryFilters = historySearch !== '' || historyStatus !== 'all' || historyDateFrom !== '' || historyDateTo !== '' || historyQuickFilter !== 'all';

    const selectedHistoryPayment = historyDetailUuid
        ? userPaymentHistory.find((p) => p.uuid === historyDetailUuid)
        : null;

    const historyStatusLabel = (status: string): string => {
        const labels: Record<string, string> = {
            draft: 'Borrador',
            submitted: 'Enviado',
            ceo_approved: 'CEO Aprobó',
            ceo_rejected: 'CEO Rechazó',
            projectmanager_review: 'En revisión PM',
            projectmanager_approved: 'PM Aprobó',
            projectmanager_rejected: 'PM Rechazó',
            final_pending: 'Pendiente Final',
            final_approved: 'Aprobado Final',
            final_rejected: 'Rechazado Final',
            documents_pending: 'Esperando docs',
            completed: 'Completado',
            approved: 'Aprobado',
            rejected: 'Rechazado',
            pending_approval: 'Pendiente',
        };
        return labels[status] ?? status;
    };

    const historyStatusColorClass = (status: string): string => {
        if (historyStatusGroups.completed.includes(status)) {
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
        }
        if (historyStatusGroups.rejected.includes(status)) {
            return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
        }
        if (historyStatusGroups.in_process.includes(status)) {
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
        }
        return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400';
    };

    const selectedDraftTotal = (draftBatch?.payments ?? [])
        .filter((p) => selectedDraftIds.has(p.uuid))
        .reduce((sum, p) => sum + Number(p.total), 0);

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
        setEditingDraftPayment(null);
        setModalOpen(true);
    };

    const openEditDraftPayment = (payment: DraftPayment) => {
        const stubIr = {
            id: payment.investment_request_id,
            folio_number: payment.concept_folio ?? 0,
            provider: payment.provider,
            rfc: payment.rfc,
            investment_expense_concept: { id: 0, name: payment.concept_name },
            currency: { id: payment.currency_id, name: '', prefix: payment.currency_prefix },
            branch: { id: payment.branch_id, name: '' },
            group_remaining: payment.concept_effective_remaining,
            remaining_balance: payment.concept_effective_remaining ?? '0',
        } as unknown as InvestmentRequest;

        setSelectedIr(stubIr);
        setEditingDraftPayment(payment);
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
        setEditingDraftPayment(null);
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
                                        className={`flex flex-col gap-2.5 rounded-lg border p-3 text-left transition-colors ${
                                            filters.department_id === String(dept.id)
                                                ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20'
                                                : 'hover:bg-gray-50 dark:hover:bg-gray-800'
                                        }`}
                                    >
                                        {/* Header: nombre+count a la izquierda, total a la derecha */}
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <p className="text-sm font-medium">{dept.name}</p>
                                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                                    {dept.count} {dept.count === 1 ? 'concepto' : 'conceptos'}
                                                </p>
                                            </div>
                                            <span className="whitespace-nowrap text-sm font-semibold">{formatCurrency(dept.total)}</span>
                                        </div>

                                        {/* Separador */}
                                        <div className="border-t border-gray-200 dark:border-gray-700" />

                                        {/* Barra de progreso + porcentaje */}
                                        <div className="flex items-center gap-2.5">
                                            <div className="relative h-2 flex-1 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                                <div
                                                    className="h-full rounded-full bg-emerald-500 transition-all"
                                                    style={{ width: `${Math.min(dept.percent_paid, 100)}%` }}
                                                />
                                            </div>
                                            <span className="whitespace-nowrap text-xs font-medium text-gray-500 dark:text-gray-400">
                                                {dept.percent_paid.toFixed(0)}% pagado
                                            </span>
                                        </div>

                                        {/* Pagado / Pendiente */}
                                        <div className="space-y-1">
                                            <div className="flex items-center justify-between text-xs">
                                                <span className="text-emerald-700 dark:text-emerald-400">Pagado</span>
                                                <span className="font-medium text-emerald-700 dark:text-emerald-400">{formatCurrency(dept.paid)}</span>
                                            </div>
                                            <div className="flex items-center justify-between text-xs">
                                                <span className="text-amber-700 dark:text-amber-400">Pendiente</span>
                                                <span className="font-medium text-amber-700 dark:text-amber-400">{formatCurrency(dept.pending)}</span>
                                            </div>
                                        </div>
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
                            <CardTitle>Detalle de Solicitudes De Inversión</CardTitle>
                            <div className="flex flex-wrap items-end gap-3">
                                <div className="space-y-1">
                                    <label className="text-xs font-medium text-gray-500 dark:text-gray-400">Buscar</label>
                                    <div className="relative">
                                        <Search className="absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                        <Input
                                            className="pl-8 w-64"
                                            placeholder="Concepto de Inversión, folio..."
                                            value={search}
                                            onChange={(e) => {
                                                const value = e.target.value;
                                                setSearch(value);
                                                if (debounceRef.current) clearTimeout(debounceRef.current);
                                                debounceRef.current = setTimeout(() => applyFilters({ search: value }), 300);
                                            }}
                                            onKeyDown={(e) => {
                                                if (e.key === 'Enter') {
                                                    if (debounceRef.current) clearTimeout(debounceRef.current);
                                                    applyFilters({ search });
                                                }
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
                                <div className="overflow-x-auto rounded-md border border-gray-200 dark:border-gray-700">
                                    <div className="max-h-[560px] overflow-y-auto">
                                    <table className="w-full text-sm">
                                        <thead className="sticky top-0 z-10 bg-gray-50 dark:bg-gray-800 shadow-sm">
                                            <tr className="border-b-2 border-gray-200 text-left text-gray-600 dark:border-gray-700 dark:text-gray-300">
                                                <th className="px-3 py-3 font-semibold whitespace-nowrap w-8"></th>
                                                <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700">Concepto de Inversión</th>
                                                <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700">Descripción</th>
                                                {showProvider && <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700">Proveedor</th>}
                                                <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700">Departamento</th>
                                                <th className="px-4 py-3 font-semibold whitespace-nowrap text-right border-r border-gray-200 dark:border-gray-700">Presupuesto</th>
                                                <th className="px-4 py-3 font-semibold whitespace-nowrap text-right border-r border-gray-200 dark:border-gray-700">Saldo</th>
                                                <th className="px-4 py-3 font-semibold whitespace-nowrap"></th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                            {groups.map((group) => {
                                                const isExpanded = expandedGroups.has(group.key);
                                                const isSingle = group.items.length === 1;
                                                const firstItem = group.items[0];
                                                const isUserDept = firstItem.department?.id === userDepartmentId;
                                                const hasBalance = Number(group.groupRemaining) > 0;

                                                return (
                                                    <React.Fragment key={group.key}>
                                                        {/* Group Header Row */}
                                                        <tr
                                                            className={cn(
                                                                'hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors',
                                                                !isSingle && 'cursor-pointer',
                                                            )}
                                                            onClick={() => { if (!isSingle) toggleGroup(group.key); }}
                                                        >
                                                            <td className="px-3 py-3 text-gray-400">
                                                                {!isSingle && (
                                                                    isExpanded
                                                                        ? <ChevronDown className="h-4 w-4" />
                                                                        : <ChevronRight className="h-4 w-4" />
                                                                )}
                                                            </td>
                                                            <td className="px-4 py-3 border-r border-gray-100 dark:border-gray-800">
                                                                <div className="font-medium text-foreground">{group.conceptName}</div>
                                                                {!isSingle && (
                                                                    <div className="text-xs text-gray-500">{group.items.length} conceptos</div>
                                                                )}
                                                                {isSingle && firstItem.is_addendum && (
                                                                    <Badge variant="outline" className="mt-0.5 border-amber-400 text-amber-600 text-[10px] dark:border-amber-600 dark:text-amber-400">
                                                                        Aditiva
                                                                    </Badge>
                                                                )}
                                                            </td>
                                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800 max-w-[200px] truncate uppercase" title={isSingle ? (firstItem.description ?? undefined) : undefined}>
                                                                {isSingle && firstItem.description ? firstItem.description : <span className="text-gray-400">—</span>}
                                                            </td>
                                                            {showProvider && (
                                                            <td className="px-4 py-3 border-r border-gray-100 dark:border-gray-800">
                                                                {isSingle ? (
                                                                    <div>
                                                                        <div className="font-medium">{firstItem.provider}</div>
                                                                        {firstItem.rfc && <div className="text-xs text-gray-500">{firstItem.rfc}</div>}
                                                                    </div>
                                                                ) : (
                                                                    <span className="text-gray-500 italic">{group.providerLabel}</span>
                                                                )}
                                                            </td>
                                                            )}
                                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800">
                                                                {group.departmentName}
                                                            </td>
                                                            <td className="px-4 py-3 text-right font-mono font-semibold border-r border-gray-100 dark:border-gray-800">
                                                                {formatCurrency(group.groupBudget)}
                                                            </td>
                                                            <td className="px-4 py-3 text-right font-mono border-r border-gray-100 dark:border-gray-800">
                                                                <span className={cn(
                                                                    Number(group.groupRemaining) <= 0 && 'text-red-500',
                                                                )}>
                                                                    {formatCurrency(group.groupRemaining)}
                                                                </span>
                                                            </td>
                                                            <td className="px-4 py-3">
                                                                {group.allCompleted && (
                                                                    <Button
                                                                        size="sm"
                                                                        variant="ghost"
                                                                        onClick={(e) => {
                                                                            e.stopPropagation();
                                                                            openDrawer(firstItem);
                                                                        }}
                                                                    >
                                                                        <Eye className="mr-1.5 h-3.5 w-3.5" />
                                                                        Pagos
                                                                    </Button>
                                                                )}
                                                            </td>
                                                        </tr>

                                                        {/* Expanded Sub-Rows */}
                                                        {isExpanded && group.items.map((ir) => (
                                                            <tr
                                                                key={ir.uuid}
                                                                className="bg-gray-50/50 dark:bg-gray-800/20 hover:bg-gray-100 dark:hover:bg-gray-800/40 cursor-pointer"
                                                                onClick={() => router.visit(`/investment-sheets/${ir.uuid}`)}
                                                            >
                                                                <td className="px-3 py-2.5"></td>
                                                                <td className="px-4 py-2.5 pl-6 border-r border-gray-100 dark:border-gray-800">
                                                                    <div className="flex items-center gap-2">
                                                                        <span className="font-mono text-xs text-gray-500">#{String(ir.folio_number).padStart(5, '0')}</span>
                                                                        {ir.is_addendum ? (
                                                                            <Badge variant="outline" className="border-amber-400 text-amber-600 text-[10px] dark:border-amber-600 dark:text-amber-400">
                                                                                Aditiva
                                                                            </Badge>
                                                                        ) : group.items.length > 1 && (
                                                                            <Badge variant="outline" className="border-blue-400 text-blue-600 text-[10px] dark:border-blue-600 dark:text-blue-400">
                                                                                Inicial
                                                                            </Badge>
                                                                        )}
                                                                        <Badge
                                                                            variant="secondary"
                                                                            className={cn('text-[10px]', statusColors[ir.status.color] ?? statusColors.gray)}
                                                                        >
                                                                            {ir.status.label}
                                                                        </Badge>
                                                                    </div>
                                                                </td>
                                                                <td className="px-4 py-2.5 text-xs text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800 max-w-[200px] truncate uppercase" title={ir.description ?? undefined}>
                                                                    {ir.description ? ir.description : <span className="text-gray-400">—</span>}
                                                                </td>
                                                                {showProvider && (
                                                                <td className="px-4 py-2.5 border-r border-gray-100 dark:border-gray-800">
                                                                    <div className="text-sm">{ir.provider}</div>
                                                                    {ir.rfc && <div className="text-xs text-gray-500">{ir.rfc}</div>}
                                                                </td>
                                                                )}
                                                                <td className="px-4 py-2.5 text-gray-500 text-xs border-r border-gray-100 dark:border-gray-800">
                                                                    {ir.department?.name}
                                                                </td>
                                                                <td className="px-4 py-2.5 text-right font-mono text-sm border-r border-gray-100 dark:border-gray-800">
                                                                    {formatCurrency(ir.total)}
                                                                </td>
                                                                <td className="px-4 py-2.5 border-r border-gray-100 dark:border-gray-800"></td>
                                                                <td className="px-4 py-2.5"></td>
                                                            </tr>
                                                        ))}
                                                    </React.Fragment>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                    </div>
                                </div>
                                <div className="mt-4">
                                    <Pagination links={investmentRequests.meta.links} />
                                </div>
                            </>
                        )}
                    </CardContent>
                </Card>

                {/* Draft Payments Card */}
                {draftBatch && draftBatch.payments.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Pagos en Borrador</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto rounded-md border border-gray-200 dark:border-gray-700">
                                <table className="w-full text-sm">
                                    <thead className="bg-gray-50 dark:bg-gray-800">
                                        <tr className="border-b-2 border-gray-200 text-left text-gray-600 dark:border-gray-700 dark:text-gray-300">
                                            <th className="px-3 py-3 font-semibold whitespace-nowrap w-10"></th>
                                            <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700">Folio</th>
                                            <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700">Concepto</th>
                                            <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700">Descripción</th>
                                            <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700">Proveedor</th>
                                            <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700">Fecha Programación Pago</th>
                                            <th className="px-4 py-3 font-semibold whitespace-nowrap text-right border-r border-gray-200 dark:border-gray-700">Monto</th>
                                            <th className="px-4 py-3 font-semibold whitespace-nowrap text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                        {draftBatch.payments.map((payment) => (
                                            <tr key={payment.uuid} className="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                                <td className="px-3 py-3">
                                                    <Checkbox
                                                        checked={selectedDraftIds.has(payment.uuid)}
                                                        onCheckedChange={() => toggleDraftSelection(payment.uuid)}
                                                    />
                                                </td>
                                                <td className="px-4 py-3 font-mono text-xs text-gray-500 border-r border-gray-100 dark:border-gray-800">
                                                    #{String(payment.folio_number).padStart(5, '0')}
                                                </td>
                                                <td className="px-4 py-3 font-medium border-r border-gray-100 dark:border-gray-800">{payment.concept_name}</td>
                                                <td className="px-4 py-3 text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800 max-w-[200px] truncate uppercase" title={payment.description ?? undefined}>
                                                    {payment.description ? payment.description : <span className="text-gray-400">—</span>}
                                                </td>
                                                <td className="px-4 py-3 text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800">{payment.provider}</td>
                                                <td className="px-4 py-3 text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800 whitespace-nowrap">
                                                    {payment.payment_provision_date ?? <span className="text-gray-400">—</span>}
                                                </td>
                                                <td className="px-4 py-3 text-right font-mono font-semibold border-r border-gray-100 dark:border-gray-800">
                                                    {formatCurrency(payment.total)}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            className="text-blue-600 hover:bg-blue-50 hover:text-blue-700 dark:text-blue-400 dark:hover:bg-blue-950"
                                                            onClick={() => openEditDraftPayment(payment)}
                                                            title="Editar pago"
                                                        >
                                                            <Pencil className="h-3.5 w-3.5" />
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            className="text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950"
                                                            onClick={() => setDeleteConfirmUuid(payment.uuid)}
                                                            title="Eliminar pago"
                                                        >
                                                            <Trash2 className="h-3.5 w-3.5" />
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot className="bg-gray-50 dark:bg-gray-800/50">
                                        <tr className="border-t-2 border-gray-200 dark:border-gray-700">
                                            <td colSpan={6} className="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400">
                                                Total seleccionado:
                                            </td>
                                            <td className="px-4 py-3 text-right font-mono font-bold">
                                                {formatCurrency(selectedDraftTotal)}
                                            </td>
                                            <td className="px-4 py-3"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div className="mt-4 flex justify-end">
                                <Button
                                    disabled={selectedDraftIds.size === 0 || submittingBatch}
                                    onClick={handleSubmitBatch}
                                >
                                    <Send className="mr-2 h-4 w-4" />
                                    {submittingBatch ? 'Enviando...' : 'Enviar a Autorización'}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Authorized Payments Card */}
                {authorizedPayments && authorizedPayments.payments.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Pagos Pendientes de Documentos</CardTitle>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Pagos que ya tienen aprobación final del CEO y requieren que subas los documentos PDF y XML para concluir el proceso.
                            </p>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto rounded-md border border-gray-200 dark:border-gray-700">
                                <table className="w-full text-sm">
                                    <thead className="bg-gray-50 dark:bg-gray-800">
                                        <tr className="border-b-2 border-gray-200 text-left text-gray-600 dark:border-gray-700 dark:text-gray-300">
                                            <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700">Folio</th>
                                            <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700">Concepto</th>
                                            <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700">Descripción</th>
                                            <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700">Proveedor</th>
                                            <th className="px-4 py-3 font-semibold whitespace-nowrap text-right border-r border-gray-200 dark:border-gray-700">Monto a pagar</th>
                                            <th className="px-4 py-3 font-semibold whitespace-nowrap text-right">Documentos</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                        {authorizedPayments.payments.map((payment) => (
                                            <tr key={payment.uuid} className="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                                <td className="px-4 py-3 font-mono text-xs text-gray-500 border-r border-gray-100 dark:border-gray-800">
                                                    #{String(payment.folio_number).padStart(5, '0')}
                                                </td>
                                                <td className="px-4 py-3 font-medium border-r border-gray-100 dark:border-gray-800">{payment.concept_name}</td>
                                                <td className="px-4 py-3 text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800 max-w-[200px] truncate uppercase" title={payment.description ?? undefined}>
                                                    {payment.description ? payment.description : <span className="text-gray-400">—</span>}
                                                </td>
                                                <td className="px-4 py-3 text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800">{payment.provider}</td>
                                                <td className="px-4 py-3 text-right font-mono font-semibold border-r border-gray-100 dark:border-gray-800">
                                                    {formatCurrency(payment.approved_amount)} <span className="text-xs text-gray-500">{payment.currency_prefix}</span>
                                                    {payment.was_adjusted && (
                                                        <div className="mt-0.5 text-[10px] uppercase tracking-wide text-amber-600 dark:text-amber-400">
                                                            ajustado por PM
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => setUploadDialogUuid(payment.uuid)}
                                                    >
                                                        <Upload className="mr-1.5 h-3.5 w-3.5" />
                                                        Subir documentos
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Payment History Card */}
                <Card>
                    <CardHeader>
                        <CardTitle>{userDepartmentName ? `Historial de Pagos — ${userDepartmentName}` : 'Historial de Pagos'}</CardTitle>
                        <p className="mt-1 text-xs text-muted-foreground">
                            Todos los pagos solicitados en {userDepartmentName ? <span className="font-medium">{userDepartmentName}</span> : 'tu departamento'} para este proyecto, en cualquier etapa del flujo. Los borradores aparecen en la tarjeta "Pagos en Borrador".
                        </p>
                    </CardHeader>
                    <CardContent>
                        {/* Filtros */}
                        <div className="flex flex-wrap items-end gap-3 mb-4">
                            <div className="space-y-1">
                                <label className="text-xs font-medium text-gray-500 dark:text-gray-400">Buscar</label>
                                <div className="relative">
                                    <Search className="absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                    <Input
                                        className="pl-8 w-64"
                                        placeholder="Folio, proveedor o concepto..."
                                        value={historySearch}
                                        onChange={(e) => { setHistorySearch(e.target.value); setHistoryPage(1); }}
                                    />
                                </div>
                            </div>
                            <div className="space-y-1">
                                <label className="text-xs font-medium text-gray-500 dark:text-gray-400">Status</label>
                                <Select value={historyStatus} onValueChange={(v) => { setHistoryStatus(v); setHistoryPage(1); }}>
                                    <SelectTrigger className="w-48">
                                        <SelectValue placeholder="Todos" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos</SelectItem>
                                        <SelectItem value="submitted">Enviado</SelectItem>
                                        <SelectItem value="ceo_approved">CEO Aprobó (1ra)</SelectItem>
                                        <SelectItem value="ceo_rejected">CEO Rechazó (1ra)</SelectItem>
                                        <SelectItem value="projectmanager_approved">PM Aprobó</SelectItem>
                                        <SelectItem value="projectmanager_rejected">PM Rechazó</SelectItem>
                                        <SelectItem value="final_approved">Aprobado Final</SelectItem>
                                        <SelectItem value="final_rejected">Rechazado Final</SelectItem>
                                        <SelectItem value="completed">Completado</SelectItem>
                                        <SelectItem value="approved">Aprobado (Legacy)</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1">
                                <label className="text-xs font-medium text-gray-500 dark:text-gray-400">Desde</label>
                                <Input
                                    type="date"
                                    className="w-40"
                                    value={historyDateFrom}
                                    onChange={(e) => { setHistoryDateFrom(e.target.value); setHistoryPage(1); }}
                                />
                            </div>
                            <div className="space-y-1">
                                <label className="text-xs font-medium text-gray-500 dark:text-gray-400">Hasta</label>
                                <Input
                                    type="date"
                                    className="w-40"
                                    value={historyDateTo}
                                    onChange={(e) => { setHistoryDateTo(e.target.value); setHistoryPage(1); }}
                                />
                            </div>
                            {hasActiveHistoryFilters && (
                                <Button variant="ghost" size="icon" onClick={clearHistoryFilters} className="shrink-0" title="Limpiar filtros">
                                    <X className="h-4 w-4" />
                                </Button>
                            )}
                        </div>

                        {/* Quick filter chips */}
                        <div className="flex flex-wrap gap-2 mb-4">
                            {([
                                { key: 'all', label: 'Todos', count: historyCounts.all },
                                { key: 'in_process', label: 'En proceso', count: historyCounts.in_process },
                                { key: 'completed', label: 'Completados', count: historyCounts.completed },
                                { key: 'rejected', label: 'Rechazados', count: historyCounts.rejected },
                            ] as const).map((chip) => (
                                <button
                                    key={chip.key}
                                    type="button"
                                    onClick={() => { setHistoryQuickFilter(chip.key); setHistoryPage(1); }}
                                    className={cn(
                                        'px-3 py-1 rounded-full text-xs font-medium transition-colors',
                                        historyQuickFilter === chip.key
                                            ? 'bg-primary text-primary-foreground'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700',
                                    )}
                                >
                                    {chip.label} ({chip.count})
                                </button>
                            ))}
                        </div>

                        {/* Tabla */}
                        {filteredHistory.length === 0 ? (
                            <div className="rounded-md border border-dashed py-12 text-center">
                                <Inbox className="mx-auto mb-2 h-10 w-10 text-gray-300 dark:text-gray-600" />
                                <p className="text-sm text-muted-foreground">
                                    {hasActiveHistoryFilters
                                        ? 'No hay pagos que coincidan con los filtros aplicados.'
                                        : userPaymentHistory.length === 0
                                            ? 'Aún no hay pagos registrados en tu departamento para este proyecto.'
                                            : 'Sin resultados.'}
                                </p>
                                {hasActiveHistoryFilters && (
                                    <Button variant="link" onClick={clearHistoryFilters} className="mt-2 text-xs">
                                        Limpiar filtros
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <>
                                <div className="overflow-x-auto rounded-md border border-gray-200 dark:border-gray-700">
                                    <table className="w-full text-sm">
                                        <thead className="bg-gray-50 dark:bg-gray-800">
                                            <tr className="border-b-2 border-gray-200 text-left text-gray-600 dark:border-gray-700 dark:text-gray-300">
                                                <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700 align-middle">Folio</th>
                                                <th className="px-4 py-3 font-semibold border-r border-gray-200 dark:border-gray-700 align-middle leading-tight">Fecha<br />Solicitud</th>
                                                <th className="px-4 py-3 font-semibold border-r border-gray-200 dark:border-gray-700 align-middle leading-tight">Fecha Programación<br />Pago</th>
                                                <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700 align-middle">Concepto</th>
                                                <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700 align-middle">Descripción</th>
                                                <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700 align-middle">Proveedor</th>
                                                <th className="px-4 py-3 font-semibold whitespace-nowrap text-right border-r border-gray-200 dark:border-gray-700 align-middle">Solicitado</th>
                                                <th className="px-4 py-3 font-semibold whitespace-nowrap text-right border-r border-gray-200 dark:border-gray-700 align-middle">Aprobado</th>
                                                <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700 align-middle">Status</th>
                                                <th className="px-4 py-3 font-semibold whitespace-nowrap text-right align-middle">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                            {paginatedHistory.map((payment) => (
                                                <tr key={payment.uuid} className="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                                    <td className="px-4 py-3 font-mono text-xs text-gray-500 border-r border-gray-100 dark:border-gray-800">
                                                        #{String(payment.folio_number).padStart(5, '0')}
                                                    </td>
                                                    <td className="px-4 py-3 text-xs text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800 whitespace-nowrap">
                                                        {payment.created_at ? payment.created_at.slice(0, 10) : '—'}
                                                    </td>
                                                    <td className="px-4 py-3 text-xs text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800 whitespace-nowrap">
                                                        {payment.payment_provision_date ?? <span className="text-gray-400">—</span>}
                                                    </td>
                                                    <td className="px-4 py-3 font-medium border-r border-gray-100 dark:border-gray-800 max-w-[200px] truncate" title={payment.concept_name}>
                                                        {payment.concept_name}
                                                    </td>
                                                    <td className="px-4 py-3 text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800 max-w-[200px] truncate uppercase" title={payment.description ?? undefined}>
                                                        {payment.description ? payment.description : <span className="text-gray-400">—</span>}
                                                    </td>
                                                    <td className="px-4 py-3 text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800 max-w-[180px] truncate" title={payment.provider}>
                                                        {payment.provider}
                                                    </td>
                                                    <td className="px-4 py-3 text-right font-mono text-sm border-r border-gray-100 dark:border-gray-800">
                                                        {formatCurrency(payment.total)}
                                                    </td>
                                                    <td className="px-4 py-3 text-right font-mono text-sm border-r border-gray-100 dark:border-gray-800">
                                                        {payment.approved_amount !== null ? (
                                                            <>
                                                                {formatCurrency(payment.approved_amount)}
                                                                {payment.was_adjusted && (
                                                                    <div className="text-[10px] uppercase tracking-wide text-amber-600 dark:text-amber-400">
                                                                        ajustado
                                                                    </div>
                                                                )}
                                                            </>
                                                        ) : (
                                                            <span className="text-gray-400">—</span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 border-r border-gray-100 dark:border-gray-800">
                                                        <span className={cn('inline-flex px-2 py-0.5 rounded-full text-[10px] font-medium', historyStatusColorClass(payment.status))}>
                                                            {historyStatusLabel(payment.status)}
                                                        </span>
                                                        {payment.is_legacy && (
                                                            <div className="mt-0.5 text-[10px] text-gray-400">Flujo anterior</div>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 text-right">
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            onClick={() => setHistoryDetailUuid(payment.uuid)}
                                                        >
                                                            <Eye className="mr-1 h-3.5 w-3.5" />
                                                            Ver
                                                        </Button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                {/* Paginación */}
                                <div className="mt-4 flex items-center justify-between">
                                    <div className="text-xs text-muted-foreground">
                                        Mostrando {(historyCurrentPage - 1) * HISTORY_PER_PAGE + 1}-{Math.min(historyCurrentPage * HISTORY_PER_PAGE, filteredHistory.length)} de {filteredHistory.length} pagos
                                    </div>
                                    {historyTotalPages > 1 && (
                                        <div className="flex items-center gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                disabled={historyCurrentPage === 1}
                                                onClick={() => setHistoryPage(historyCurrentPage - 1)}
                                            >
                                                «
                                            </Button>
                                            <span className="text-xs text-muted-foreground">
                                                Página {historyCurrentPage} de {historyTotalPages}
                                            </span>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                disabled={historyCurrentPage === historyTotalPages}
                                                onClick={() => setHistoryPage(historyCurrentPage + 1)}
                                            >
                                                »
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* History payment detail drawer */}
            <Sheet open={historyDetailUuid !== null} onOpenChange={(v) => { if (!v) setHistoryDetailUuid(null); }}>
                <SheetContent side="right" className="sm:max-w-lg w-full overflow-y-auto">
                    {selectedHistoryPayment && (
                        <>
                            <SheetHeader>
                                <SheetTitle>
                                    Pago #{String(selectedHistoryPayment.folio_number).padStart(5, '0')}
                                </SheetTitle>
                                <SheetDescription>
                                    {selectedHistoryPayment.concept_name}
                                </SheetDescription>
                            </SheetHeader>

                            <div className="space-y-5 px-4 pb-6">
                                {/* Status banner */}
                                <div className={cn('rounded-md px-3 py-2 text-center font-semibold text-sm', historyStatusColorClass(selectedHistoryPayment.status))}>
                                    {historyStatusLabel(selectedHistoryPayment.status)}
                                    {selectedHistoryPayment.is_legacy && (
                                        <span className="ml-1 text-xs font-normal opacity-75">(Flujo anterior)</span>
                                    )}
                                </div>

                                {/* Información del pago */}
                                <div className="space-y-3 rounded-lg border p-4">
                                    <h3 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Información del Pago</h3>
                                    <dl className="space-y-2 text-sm">
                                        <div className="flex justify-between">
                                            <dt className="text-muted-foreground">Proveedor</dt>
                                            <dd className="font-medium text-right">{selectedHistoryPayment.provider}</dd>
                                        </div>
                                        {selectedHistoryPayment.rfc && (
                                            <div className="flex justify-between">
                                                <dt className="text-muted-foreground">RFC</dt>
                                                <dd className="font-mono text-xs">{selectedHistoryPayment.rfc}</dd>
                                            </div>
                                        )}
                                        <div className="flex justify-between">
                                            <dt className="text-muted-foreground">Sucursal</dt>
                                            <dd className="font-medium">{selectedHistoryPayment.branch}</dd>
                                        </div>
                                        <div className="flex justify-between">
                                            <dt className="text-muted-foreground">Tipo</dt>
                                            <dd className="font-medium capitalize">{selectedHistoryPayment.payment_type}</dd>
                                        </div>
                                        {selectedHistoryPayment.description && (
                                            <div>
                                                <dt className="text-muted-foreground mb-1">Descripción</dt>
                                                <dd className="text-sm uppercase">{selectedHistoryPayment.description}</dd>
                                            </div>
                                        )}
                                    </dl>
                                </div>

                                {/* Montos */}
                                <div className="space-y-3 rounded-lg border p-4">
                                    <h3 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Montos</h3>
                                    <dl className="space-y-2 text-sm">
                                        <div className="flex justify-between">
                                            <dt className="text-muted-foreground">Subtotal</dt>
                                            <dd className="font-mono">{formatCurrency(selectedHistoryPayment.subtotal)}</dd>
                                        </div>
                                        <div className="flex justify-between">
                                            <dt className="text-muted-foreground">IVA</dt>
                                            <dd className="font-mono">{formatCurrency(selectedHistoryPayment.iva)}</dd>
                                        </div>
                                        <div className="flex justify-between border-t pt-2">
                                            <dt className="font-semibold">Total solicitado</dt>
                                            <dd className="font-mono font-semibold">{formatCurrency(selectedHistoryPayment.total)} {selectedHistoryPayment.currency_prefix}</dd>
                                        </div>
                                        {selectedHistoryPayment.approved_amount !== null && (
                                            <div className="flex justify-between">
                                                <dt className="font-semibold text-amber-700 dark:text-amber-400">
                                                    Monto aprobado por PM
                                                </dt>
                                                <dd className="font-mono font-semibold text-amber-700 dark:text-amber-400">
                                                    {formatCurrency(selectedHistoryPayment.approved_amount)}
                                                    {selectedHistoryPayment.was_adjusted && (
                                                        <div className="text-[10px] font-normal text-amber-600 dark:text-amber-500">
                                                            ajustado desde {formatCurrency(selectedHistoryPayment.total)}
                                                        </div>
                                                    )}
                                                </dd>
                                            </div>
                                        )}
                                    </dl>
                                </div>

                                {/* Timeline */}
                                {!selectedHistoryPayment.is_legacy && (
                                    <div className="space-y-3 rounded-lg border p-4">
                                        <h3 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Timeline del Flujo</h3>
                                        <ul className="space-y-2 text-sm">
                                            <li className="flex items-center gap-2">
                                                <CheckIcon className="h-4 w-4 text-green-500 flex-shrink-0" />
                                                <span className="text-muted-foreground">Capturado:</span>
                                                <span className="ml-auto text-xs">
                                                    {selectedHistoryPayment.created_at
                                                        ? new Date(selectedHistoryPayment.created_at).toLocaleString('es-MX')
                                                        : '—'}
                                                </span>
                                            </li>
                                            {selectedHistoryPayment.ceo_reviewed_at && (
                                                <li className="flex items-center gap-2">
                                                    <CheckIcon className="h-4 w-4 text-green-500 flex-shrink-0" />
                                                    <span className="text-muted-foreground">CEO revisó:</span>
                                                    <span className="ml-auto text-xs">
                                                        {new Date(selectedHistoryPayment.ceo_reviewed_at).toLocaleString('es-MX')}
                                                    </span>
                                                </li>
                                            )}
                                            {selectedHistoryPayment.pm_reviewed_at && (
                                                <li className="flex items-center gap-2">
                                                    <CheckIcon className="h-4 w-4 text-green-500 flex-shrink-0" />
                                                    <span className="text-muted-foreground">PM revisó:</span>
                                                    <span className="ml-auto text-xs">
                                                        {new Date(selectedHistoryPayment.pm_reviewed_at).toLocaleString('es-MX')}
                                                    </span>
                                                </li>
                                            )}
                                            {selectedHistoryPayment.final_reviewed_at && (
                                                <li className="flex items-center gap-2">
                                                    <CheckIcon className="h-4 w-4 text-green-500 flex-shrink-0" />
                                                    <span className="text-muted-foreground">CEO aprobación final:</span>
                                                    <span className="ml-auto text-xs">
                                                        {new Date(selectedHistoryPayment.final_reviewed_at).toLocaleString('es-MX')}
                                                    </span>
                                                </li>
                                            )}
                                        </ul>
                                    </div>
                                )}

                                {/* Motivo de rechazo */}
                                {(selectedHistoryPayment.ceo_rejection_reason
                                    || selectedHistoryPayment.pm_rejection_reason
                                    || selectedHistoryPayment.final_rejection_reason) && (
                                    <div className="space-y-3 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900/50 dark:bg-red-950/30">
                                        <h3 className="text-xs font-semibold uppercase tracking-wide text-red-700 dark:text-red-400">Motivo del Rechazo</h3>
                                        {selectedHistoryPayment.ceo_rejection_reason && (
                                            <div>
                                                <p className="text-xs font-semibold text-red-700 dark:text-red-400">CEO Primera revisión</p>
                                                <p className="text-sm text-red-900 dark:text-red-300">{selectedHistoryPayment.ceo_rejection_reason}</p>
                                            </div>
                                        )}
                                        {selectedHistoryPayment.pm_rejection_reason && (
                                            <div>
                                                <p className="text-xs font-semibold text-red-700 dark:text-red-400">Project Manager</p>
                                                <p className="text-sm text-red-900 dark:text-red-300">{selectedHistoryPayment.pm_rejection_reason}</p>
                                            </div>
                                        )}
                                        {selectedHistoryPayment.final_rejection_reason && (
                                            <div>
                                                <p className="text-xs font-semibold text-red-700 dark:text-red-400">CEO Aprobación Final</p>
                                                <p className="text-sm text-red-900 dark:text-red-300">{selectedHistoryPayment.final_rejection_reason}</p>
                                            </div>
                                        )}
                                    </div>
                                )}

                                {/* Documentos */}
                                {selectedHistoryPayment.has_documents && selectedHistoryPayment.documents.length > 0 && (
                                    <div className="space-y-3 rounded-lg border p-4">
                                        <h3 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Documentos</h3>
                                        <ul className="space-y-2">
                                            {selectedHistoryPayment.documents.map((doc, i) => {
                                                const ext = doc.name.split('.').pop()?.toUpperCase() ?? 'DOC';
                                                return (
                                                    <li key={i} className="flex items-center gap-2 rounded-md border px-3 py-2">
                                                        <Download className="h-4 w-4 shrink-0 text-muted-foreground" />
                                                        <a
                                                            href={doc.url}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="flex-1 text-sm font-medium text-primary hover:underline truncate"
                                                        >
                                                            Descargar {ext}
                                                        </a>
                                                    </li>
                                                );
                                            })}
                                        </ul>
                                    </div>
                                )}
                            </div>
                        </>
                    )}
                </SheetContent>
            </Sheet>

            {/* Upload documents dialog */}
            <Dialog open={uploadDialogUuid !== null} onOpenChange={(open) => { if (!open) closeUploadDialog(); }}>
                <DialogContent className="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Subir documentos del pago</DialogTitle>
                        <DialogDescription>
                            Adjunta el PDF y el XML del pago. Ambos archivos son obligatorios y máximo 10 MB cada uno.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-4 pt-2 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label>
                                Factura PDF <span className="text-red-500">*</span>
                            </Label>
                            <FileUpload
                                files={uploadPdf ? [uploadPdf] : []}
                                onChange={(f) => setUploadPdf(f[0] ?? null)}
                                maxFiles={1}
                                accept=".pdf"
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>
                                Factura XML <span className="text-red-500">*</span>
                            </Label>
                            <FileUpload
                                files={uploadXml ? [uploadXml] : []}
                                onChange={(f) => setUploadXml(f[0] ?? null)}
                                maxFiles={1}
                                accept=".xml"
                            />
                        </div>
                    </div>
                    <div className="flex justify-end gap-2 pt-4">
                        <Button variant="outline" onClick={closeUploadDialog} disabled={uploading}>
                            Cancelar
                        </Button>
                        <Button
                            onClick={handleUploadDocuments}
                            disabled={!uploadPdf || !uploadXml || uploading}
                        >
                            {uploading ? 'Subiendo...' : 'Subir documentos'}
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>

            {/* Delete draft payment confirmation */}
            <Dialog open={deleteConfirmUuid !== null} onOpenChange={(open) => { if (!open) setDeleteConfirmUuid(null); }}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Eliminar pago del borrador</DialogTitle>
                        <DialogDescription>
                            ¿Estás seguro de eliminar este pago? Esta acción no se puede deshacer y liberará el monto del presupuesto.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="flex justify-end gap-2 pt-2">
                        <Button variant="outline" onClick={() => setDeleteConfirmUuid(null)}>
                            Cancelar
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() => deleteConfirmUuid && handleDeleteDraftPayment(deleteConfirmUuid)}
                        >
                            Eliminar
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>

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
                    editingPayment={editingDraftPayment}
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
    const groupRemaining = Number(ir.group_remaining ?? ir.remaining_balance);
    const canRequestPayment = isUserDept && groupRemaining > 0;

    const groupBudget = Number(ir.group_budget ?? (summary?.total_concept ?? ir.total));
    const groupPaid = Number(ir.group_paid ?? (summary?.total_paid ?? 0));
    const progressPercent = groupBudget > 0 ? Math.min(100, (groupPaid / groupBudget) * 100) : 0;

    return (
        <Sheet open={open} onOpenChange={(v) => { if (!v) onClose(); }}>
            <SheetContent side="right" className="sm:max-w-lg w-full overflow-y-auto">
                <SheetHeader>
                    <SheetTitle className="flex items-center gap-2">
                        Concepto #{String(ir.folio_number).padStart(5, '0')}
                    </SheetTitle>
                    {ir.investment_expense_concept?.name && (
                        <SheetDescription>{ir.investment_expense_concept.name}</SheetDescription>
                    )}
                </SheetHeader>

                <div className="space-y-5 px-4 pb-6">
                    {/* Summary */}
                    {ir.is_addendum && (
                        <div className="flex items-center gap-2 rounded-md border border-amber-300 bg-amber-50 px-3 py-2 dark:border-amber-700 dark:bg-amber-900/20">
                            <span className="text-amber-600 dark:text-amber-400">&#9888;</span>
                            <span className="text-xs font-semibold text-amber-700 dark:text-amber-300">ADITIVA AL PRESUPUESTO</span>
                        </div>
                    )}
                    <div className="space-y-3 rounded-lg border p-4">
                        <div className="flex justify-between text-sm">
                            <span className="text-muted-foreground">Este concepto</span>
                            <span className="font-mono font-semibold">{formatCurrency(ir.total)}</span>
                        </div>
                        {ir.group_budget && ir.group_budget !== String(ir.total) && (
                            <div className="flex justify-between text-sm">
                                <span className="text-muted-foreground">Presupuesto total (base + aditivas)</span>
                                <span className="font-mono font-semibold text-purple-600 dark:text-purple-400">{formatCurrency(ir.group_budget)}</span>
                            </div>
                        )}
                        {summary && (
                            <>
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Total pagado / solicitado</span>
                                    <span className="font-mono font-medium text-blue-600 dark:text-blue-400">{formatCurrency(summary.total_paid)}</span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Saldo disponible</span>
                                    <span className={cn('font-mono font-semibold', Number(ir.group_remaining ?? summary.remaining) > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400')}>
                                        {formatCurrency(ir.group_remaining ?? summary.remaining)}
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
    editingPayment?: DraftPayment | null;
};

function PaymentRequestModal({
    open, onClose, investmentRequest: ir,
    currencies, branches, errors,
    editingPayment,
}: PaymentRequestModalProps) {
    const isEditMode = !!editingPayment;

    const [values, setValues] = useState(() => {
        if (editingPayment) {
            return {
                provider: editingPayment.provider,
                rfc: editingPayment.rfc ?? '',
                invoice_folio: editingPayment.invoice_folio ?? '',
                payment_provision_date: editingPayment.payment_provision_date ?? '',
                currency_id: String(editingPayment.currency_id),
                branch_id: String(editingPayment.branch_id),
                is_invoice: editingPayment.is_invoice,
                description: editingPayment.description ?? '',
                subtotal: editingPayment.subtotal,
                iva_rate: editingPayment.iva_rate ?? '',
                iva: editingPayment.iva,
                retention: editingPayment.retention,
                total: editingPayment.total,
            };
        }
        return {
            provider: ir.provider ?? '',
            rfc: ir.rfc ?? '',
            invoice_folio: '',
            payment_provision_date: '',
            currency_id: ir.currency?.id ? String(ir.currency.id) : '',
            branch_id: ir.branch?.id ? String(ir.branch.id) : '',
            is_invoice: false,
            description: '',
            subtotal: '',
            iva_rate: '',
            iva: '',
            retention: false,
            total: '',
        };
    });

    const [files, setFiles] = useState<File[]>([]);
    const [invoicePdf, setInvoicePdf] = useState<File | null>(null);
    const [invoiceXml, setInvoiceXml] = useState<File | null>(null);
    const [processing, setProcessing] = useState(false);
    const [existingDocuments, setExistingDocuments] = useState<string[]>(
        () => editingPayment ? [...editingPayment.advance_documents] : []
    );

    const remainingBalance = Number(ir.group_remaining ?? ir.remaining_balance);

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

        if (!isEditMode) {
            formData.append('investment_request_id', String(ir.id));
        }

        Object.entries(values).forEach(([key, val]) => {
            formData.append(key, typeof val === 'boolean' ? (val ? '1' : '0') : String(val));
        });

        if (values.is_invoice) {
            if (invoicePdf) formData.append('invoice_documents[]', invoicePdf);
            if (invoiceXml) formData.append('invoice_documents[]', invoiceXml);
        } else {
            files.forEach((file) => formData.append('advance_documents[]', file));
        }

        if (isEditMode && editingPayment) {
            existingDocuments.forEach((path) => formData.append('keep_documents[]', path));
            formData.append('_method', 'PATCH');

            router.post(`/investment-payment-requests/${editingPayment.uuid}`, formData, {
                forceFormData: true,
                onSuccess: () => onClose(),
                onFinish: () => setProcessing(false),
            });
            return;
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
                    <DialogTitle>{isEditMode ? 'Editar Pago en Borrador' : 'Solicitar Pago de Inversión'}</DialogTitle>
                    <DialogDescription>
                        {isEditMode
                            ? <>Concepto: <span className="font-semibold">{ir.investment_expense_concept?.name}</span>{' · '}Folio del pago: <span className="font-mono">#{String(editingPayment!.folio_number).padStart(5, '0')}</span>{' · '}Saldo disponible: <span className="font-semibold">{formatCurrency(remainingBalance)}</span></>
                            : <>Concepto #{String(ir.folio_number).padStart(5, '0')} — {ir.provider}{' · '}Saldo disponible: <span className="font-semibold">{formatCurrency(remainingBalance)}</span></>
                        }
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
                                        <p className="text-xs font-medium text-blue-600 dark:text-blue-400">Concepto de Inversión</p>
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
                                    <Label htmlFor="modal_invoice_folio">Folio de Factura o Cotización <span className="text-gray-400">(opcional)</span></Label>
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
                                    <Input
                                        value={branches.find((b) => String(b.id) === values.branch_id)?.name ?? ''}
                                        readOnly
                                        disabled
                                        className="bg-gray-50 dark:bg-gray-800"
                                    />
                                    <InputError message={errors.branch_id} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="modal_description">Descripción <span className="text-gray-400">(opcional)</span></Label>
                                    <textarea
                                        id="modal_description"
                                        className="border-input focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border bg-transparent px-3 py-2 text-sm uppercase shadow-xs focus-visible:ring-[3px] focus-visible:outline-none"
                                        rows={3}
                                        value={values.description}
                                        onChange={(e) => handleChange('description', e.target.value.toUpperCase())}
                                        placeholder="Ej: Pago del 30% de anticipo por concepto de..."
                                    />
                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                        Si el pago corresponde a un porcentaje de avance, ind&iacute;calo aqu&iacute; (ej. &quot;Pago del 50% de avance&quot;).
                                    </p>
                                    <InputError message={errors.description} />
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Row 2: Montos + Documentos */}
                    <div className="grid gap-6 lg:grid-cols-2">
                        {/* Información de Pago */}
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base">Información de Pago</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="modal_payment_provision_date">Fecha Programación de Pago</Label>
                                    <Input
                                        id="modal_payment_provision_date"
                                        type="date"
                                        value={values.payment_provision_date}
                                        onChange={(e) => handleChange('payment_provision_date', e.target.value)}
                                    />
                                    <InputError message={errors.payment_provision_date} />
                                </div>
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

                                {isEditMode && existingDocuments.length > 0 && (
                                    <div className="space-y-2 rounded-md border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800/40">
                                        <p className="text-xs font-medium text-gray-700 dark:text-gray-300">
                                            Documentos actuales ({existingDocuments.length})
                                        </p>
                                        <ul className="space-y-1">
                                            {existingDocuments.map((path) => (
                                                <li key={path} className="flex items-center justify-between gap-2 rounded border border-gray-200 bg-white px-2 py-1 text-xs dark:border-gray-700 dark:bg-gray-900">
                                                    <span className="truncate" title={path}>{path.split('/').pop()}</span>
                                                    <button
                                                        type="button"
                                                        onClick={() => setExistingDocuments((prev) => prev.filter((p) => p !== path))}
                                                        className="rounded p-1 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950"
                                                        title="Quitar este documento"
                                                    >
                                                        <X className="h-3 w-3" />
                                                    </button>
                                                </li>
                                            ))}
                                        </ul>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                            Los documentos que quites se eliminarán definitivamente al guardar. Abajo puedes subir nuevos para reemplazarlos.
                                        </p>
                                    </div>
                                )}

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
                            {processing ? (isEditMode ? 'Guardando...' : 'Enviando...') : (isEditMode ? 'Guardar Cambios' : 'Solicitar Pago')}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
