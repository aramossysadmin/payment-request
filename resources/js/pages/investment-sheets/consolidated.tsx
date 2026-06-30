import { Head, router, usePage } from '@inertiajs/react';
import { ArrowDownRight, ArrowUpRight, Banknote, Building2, Calendar, CalendarRange, CheckIcon, ChevronDown, ChevronRight, ChevronsUpDownIcon, Clock, Download, Eye, FileDown, FileText, Inbox, Info, Pencil, Search, Send, Trash2, Upload, Wallet, X, XCircle } from 'lucide-react';
import React, { useCallback, useEffect, useRef, useState, type FormEvent } from 'react';
import { DocumentPreview } from '@/components/document-preview';
import { FileUpload } from '@/components/file-upload';
import InputError from '@/components/input-error';
import { Pagination } from '@/components/pagination';
import { ProviderAutocomplete } from '@/components/provider-autocomplete';
import { WeekNavigator } from '@/components/week-navigator';
import { useCurrencyFormatters, useDisplayCurrency } from '@/contexts/display-currency';
import { useTimeRemaining } from '@/hooks/use-payment-policy';
import { formatPolicyTime } from '@/lib/format-policy-time';
import { investmentPaymentTypeLabel } from '@/lib/payment-type-labels';
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
    DialogFooter,
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
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { ColumnFilterPopover } from '@/components/column-filter-popover';
import { NumberedPagination } from '@/components/numbered-pagination';
import { SortableHeader } from '@/components/sortable-header';
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
    committed: string;
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
    payment_type: string;
    currency_prefix: string;
    currency_id: number;
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
    committed: number;
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
    payment_type: string;
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
    category_name: string;
    department_name: string;
    user_name: string;
    description: string | null;
    currency_prefix: string;
    currency_id: number;
    total: string;
    approved_amount: string;
    was_adjusted: boolean;
    status: string;
    payment_type: string;
    is_legacy: boolean;
    has_documents: boolean;
    documents: { name: string; url: string }[];
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
    invoice_folio: string | null;
    concept_name: string;
    concept_folio: number | null;
    category_name: string;
    user_name: string;
    branch: string;
    department_id: number;
    department_name: string;
    payment_type: string;
    payment_provision_date: string | null;
    week_number: number | null;
    week_year: number | null;
    description: string | null;
    currency_prefix: string;
    currency_id: number;
    subtotal: string;
    iva: string;
    total: string;
    documents_count: number;
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
    paymentPolicy: import('@/types/payment-policy').PaymentPolicyPayload;
    project: {
        id: number;
        name: string;
        branch: string | null;
        currency_id: number | null;
        currency_prefix: string;
        start_date: string | null;
        opening_date: string | null;
        authorized_budget: string | null;
    };
    totals: {
        subtotal: string;
        total: string;
        authorized: string;
        pending: string;
        count: number;
    };
    projectDashboard: {
        current_week: number;
        current_year: number;
        today_date: string;
        original_budget: string;
        additional_budget: string;
        deductive_budget: string;
        pending_additional: string;
        pending_deductive: string;
        updated_budget: string;
        committed_total: string;
        paid_total: string;
        remaining_budget: string;
    };
    departmentBreakdown: DepartmentBreakdown[];
    investmentRequests: PaginatedData<InvestmentRequest>;
    filters: { search?: string; status?: string; department_id?: string };
    userDepartmentId: number;
    userDepartmentName: string | null;
    userDepartments: { id: number; name: string }[];
    currencies: Currency[];
    branches: Branch[];
    availableConcepts: { id: number; name: string; investment_expense_category_id: number; category: { id: number; name: string } | null }[];
    conceptDepartmentMap: Record<number, number[]>;
    canEditRequestConcept: boolean;
    errors: Record<string, string>;
    draftBatch: DraftBatch | null;
    authorizedPayments: AuthorizedPaymentsGroup;
    userPaymentHistory: HistoryPayment[];
    isSuperAdmin: boolean;
    canSeeAllDepartments: boolean;
    departments: { id: number; name: string }[];
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
    { value: '0.04', label: 'IVA 4%' },
    { value: '0.08', label: 'IVA 8%' },
    { value: '0.16', label: 'IVA 16%' },
    { value: '0.21', label: 'IVA 21%' },
];

function formatDateEs(dateStr: string | null): string {
    if (!dateStr) return '';
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('es-MX', { day: '2-digit', month: 'long', year: 'numeric' });
}

const historyStatusGroups: Record<'in_process' | 'completed' | 'rejected', string[]> = {
    in_process: ['submitted', 'ceo_approved', 'projectmanager_review', 'projectmanager_approved', 'final_pending', 'documents_pending', 'pending_approval'],
    completed: ['final_approved', 'completed', 'approved'],
    rejected: ['ceo_rejected', 'projectmanager_rejected', 'final_rejected', 'rejected'],
};

function historyStatusLabel(status: string): string {
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
}

function historyStatusColorClass(status: string): string {
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
}

function formatDateShort(dateStr: string | null): string {
    if (!dateStr) return '';
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatDayMonth(dateStr: string | null): string {
    if (!dateStr) return '';
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('es-MX', { day: '2-digit', month: 'long' });
}

function formatWeekday(dateStr: string | null): string {
    if (!dateStr) return '';
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('es-MX', { weekday: 'long' });
}

/** Semana ISO 8601 (lunes inicio; semana 1 = la del primer jueves) y su año ISO. Igual que Carbon::isoWeek. */
function isoWeekOf(dateStr: string): { week: number; year: number } | null {
    if (!dateStr) return null;
    const d = new Date(dateStr + 'T00:00:00');
    if (Number.isNaN(d.getTime())) return null;
    const target = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
    const dayNr = (target.getUTCDay() + 6) % 7;
    target.setUTCDate(target.getUTCDate() - dayNr + 3);
    const year = target.getUTCFullYear();
    const firstThursday = new Date(Date.UTC(year, 0, 4));
    const firstDayNr = (firstThursday.getUTCDay() + 6) % 7;
    firstThursday.setUTCDate(firstThursday.getUTCDate() - firstDayNr + 3);
    const week = 1 + Math.round((target.getTime() - firstThursday.getTime()) / (7 * 86_400_000));
    return { week, year };
}

type ConceptGroup = {
    key: string;
    conceptName: string;
    categoryName: string | null;
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
            categoryName: first.investment_expense_concept?.category?.name ?? null,
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
        paymentPolicy,
        project, projectDashboard, departmentBreakdown, investmentRequests, filters,
        userDepartmentId, userDepartmentName, userDepartments, currencies, branches, availableConcepts, conceptDepartmentMap, canEditRequestConcept, errors, draftBatch, authorizedPayments, userPaymentHistory, isSuperAdmin, canSeeAllDepartments, departments,
    } = usePage<PageProps>().props;

    // Moneda de visualización: los agregados llegan en MXN (normalizados en backend) → formatMxn;
    // los importes por pago se convierten desde su moneda nativa con formatNative.
    const displayCurrency = useDisplayCurrency();
    const { formatCurrency, formatCurrencyPlain, formatNative, nativeNoteOf } = useCurrencyFormatters();

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

    // Estado del modal de edición inline (concepto + descripción) en la tabla de detalle
    type EditDescState = {
        uuid: string;
        folio: number;
        departmentId: number | null;
        departmentName: string | null;
        currentConceptSnapshot: { id: number; name: string; categoryName: string | null } | null;
    };
    const [editDescState, setEditDescState] = useState<EditDescState | null>(null);
    const [editDescValue, setEditDescValue] = useState('');
    const [editConceptId, setEditConceptId] = useState<string>('');
    const [editConceptOpen, setEditConceptOpen] = useState(false);
    const [editDescSaving, setEditDescSaving] = useState(false);

    const openEditDescription = (ir: { uuid: string; folio_number: number; description: string | null; investment_expense_concept_id?: number | null; investment_expense_concept?: { id?: number; name: string; category?: { id: number; name: string } | null } | null; expense_concept?: { name: string } | null; department?: { id?: number; name?: string } | null }) => {
        const snapshot = (ir.investment_expense_concept && ir.investment_expense_concept.id)
            ? {
                id: ir.investment_expense_concept.id,
                name: ir.investment_expense_concept.name,
                categoryName: ir.investment_expense_concept.category?.name ?? null,
            }
            : null;
        setEditDescState({
            uuid: ir.uuid,
            folio: ir.folio_number,
            departmentId: ir.department?.id ?? null,
            departmentName: ir.department?.name ?? null,
            currentConceptSnapshot: snapshot,
        });
        setEditDescValue(ir.description ?? '');
        const conceptId = ir.investment_expense_concept_id ?? ir.investment_expense_concept?.id ?? null;
        setEditConceptId(conceptId !== null ? String(conceptId) : '');
    };

    const closeEditDescription = () => {
        setEditDescState(null);
        setEditDescValue('');
        setEditConceptId('');
        setEditConceptOpen(false);
        setEditDescSaving(false);
    };

    const handleSaveDescription = () => {
        if (!editDescState) return;
        setEditDescSaving(true);
        const payload: { description: string; investment_expense_concept_id?: number } = {
            description: editDescValue,
        };
        if (editConceptId !== '') {
            payload.investment_expense_concept_id = Number(editConceptId);
        }
        router.patch(
            `/investment-sheets/${editDescState.uuid}/description`,
            payload,
            {
                preserveScroll: true,
                onSuccess: () => closeEditDescription(),
                onFinish: () => setEditDescSaving(false),
            },
        );
    };

    type UploadType = 'factura' | 'reembolso' | 'estrategia' | 'anticipo' | 'cotizacion' | 'pagare' | 'domiciliado';
    const UPLOAD_TYPES: UploadType[] = ['factura', 'reembolso', 'estrategia', 'anticipo', 'cotizacion', 'pagare', 'domiciliado'];
    const isValidUploadType = (value: string | undefined | null): value is UploadType =>
        UPLOAD_TYPES.includes(value as UploadType);

    const [uploadDialogUuid, setUploadDialogUuid] = useState<string | null>(null);
    const [uploadType, setUploadType] = useState<UploadType>('factura');
    const [uploadPdf, setUploadPdf] = useState<File | null>(null);
    const [uploadXml, setUploadXml] = useState<File | null>(null);
    const [uploadDocument, setUploadDocument] = useState<File | null>(null);
    const [uploading, setUploading] = useState(false);
    const [uploadErrors, setUploadErrors] = useState<Record<string, string>>({});

    const openUploadDialog = (uuid: string, currentPaymentType?: string | null) => {
        setUploadType(isValidUploadType(currentPaymentType) ? currentPaymentType : 'factura');
        setUploadPdf(null);
        setUploadXml(null);
        setUploadDocument(null);
        setUploadErrors({});
        setUploadDialogUuid(uuid);
    };

    const changeUploadType = (type: UploadType) => {
        setUploadType(type);
        setUploadPdf(null);
        setUploadXml(null);
        setUploadDocument(null);
        setUploadErrors({});
    };

    const uploadCanSubmit = uploadType === 'factura'
        ? Boolean(uploadPdf && uploadXml)
        : Boolean(uploadDocument);

    const handleUploadDocuments = () => {
        if (!uploadDialogUuid || !uploadCanSubmit) return;
        setUploading(true);
        setUploadErrors({});

        const formData = new FormData();
        formData.append('payment_type', uploadType);
        if (uploadType === 'factura') {
            if (uploadPdf) formData.append('pdf', uploadPdf);
            if (uploadXml) formData.append('xml', uploadXml);
        } else if (uploadDocument) {
            formData.append('document', uploadDocument);
        }

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
                    setUploadDocument(null);
                    setUploadErrors({});
                },
                onError: (errors) => setUploadErrors(errors as Record<string, string>),
                onFinish: () => setUploading(false),
            },
        );
    };

    const closeUploadDialog = () => {
        setUploadDialogUuid(null);
        setUploadPdf(null);
        setUploadXml(null);
        setUploadDocument(null);
        setUploadErrors({});
    };

    // Historial de pagos — estado de filtros + paginación + drawer
    const [historySearch, setHistorySearch] = useState('');
    const [historyStatus, setHistoryStatus] = useState<string>('all');
    const [historyColumnFilters, setHistoryColumnFilters] = useState<Record<string, string>>({});
    const [historySortBy, setHistorySortBy] = useState<{ column: string; direction: 'asc' | 'desc' } | null>(null);
    const handleHistorySort = useCallback((column: string) => {
        setHistorySortBy((prev) => {
            if (!prev || prev.column !== column) return { column, direction: 'asc' };
            if (prev.direction === 'asc') return { column, direction: 'desc' };
            return null;
        });
    }, []);
    const setColumnFilter = useCallback((column: string, value: string) => {
        setHistoryColumnFilters((prev) => {
            const next = { ...prev };
            if (value === '') delete next[column];
            else next[column] = value;
            return next;
        });
    }, []);
    const [selectedWeek, setSelectedWeek] = useState(projectDashboard.current_week);
    const [selectedYear, setSelectedYear] = useState(projectDashboard.current_year);
    // 'mine' = mi(s) departamento(s); 'all' = todos los del sistema (privilegiados);
    // string numérico = ID de un departamento específico
    const [historyDepartmentFilter, setHistoryDepartmentFilter] = useState<string>('mine');
    const [weekFilterEnabled, setWeekFilterEnabled] = useState<boolean>(true);
    const [historyQuickFilter, setHistoryQuickFilter] = useState<'all' | 'in_process' | 'completed' | 'rejected'>('all');
    const [historyPage, setHistoryPage] = useState(1);
    const [historyDetailUuid, setHistoryDetailUuid] = useState<string | null>(null);
    const [editDateState, setEditDateState] = useState<{ uuid: string; folio: number; date: string } | null>(null);
    const [editDateSaving, setEditDateSaving] = useState(false);
    const [historyPerPage, setHistoryPerPage] = useState(10);

    // "Pagos Pendientes de Documentos" — paginación, ordenamiento y filtros (mismo patrón que Historial)
    const [authorizedPage, setAuthorizedPage] = useState(1);
    const [authorizedSortBy, setAuthorizedSortBy] = useState<{ column: string; direction: 'asc' | 'desc' } | null>(null);
    const [authorizedColumnFilters, setAuthorizedColumnFilters] = useState<Record<string, string>>({});
    const [authorizedPerPage, setAuthorizedPerPage] = useState(10);

    const handleAuthorizedSort = (column: string) => {
        setAuthorizedPage(1);
        setAuthorizedSortBy((prev) => {
            if (!prev || prev.column !== column) return { column, direction: 'asc' };
            if (prev.direction === 'asc') return { column, direction: 'desc' };
            return null;
        });
    };

    const setAuthorizedColumnFilter = (column: string, value: string) => {
        setAuthorizedPage(1);
        setAuthorizedColumnFilters((prev) => ({ ...prev, [column]: value }));
    };

    const navigateWeek = (direction: number) => {
        let newWeek = selectedWeek + direction;
        let newYear = selectedYear;
        if (newWeek > 52) {
            newWeek = 1;
            newYear += 1;
        } else if (newWeek < 1) {
            newWeek = 52;
            newYear -= 1;
        }
        setSelectedWeek(newWeek);
        setSelectedYear(newYear);
        setHistoryPage(1);
    };

    const historyCounts = {
        all: userPaymentHistory.length,
        in_process: userPaymentHistory.filter((p) => historyStatusGroups.in_process.includes(p.status)).length,
        completed: userPaymentHistory.filter((p) => historyStatusGroups.completed.includes(p.status)).length,
        rejected: userPaymentHistory.filter((p) => historyStatusGroups.rejected.includes(p.status)).length,
    };

    const userDepartmentIds = (userDepartments ?? []).map((d) => d.id);

    const filteredHistory = userPaymentHistory.filter((p) => {
        // Filtro de departamento (3 variantes):
        // - 'all': muestra todo lo que el backend trae (privilegiados: todo el sistema; otros: ya filtrado a sus dptos)
        // - 'mine': para privilegiados = solo su principal. Para multi-dpto no privilegiado = todos sus dptos (ya filtrado por backend, no aplica filtro adicional).
        // - string ID: filtra a ese dpto específico (válido para privilegiados o multi-dpto).
        if (historyDepartmentFilter === 'mine') {
            // Privilegiados restringen a su principal; multi-dpto ya ve sus dptos por backend.
            if (canSeeAllDepartments && p.department_id !== userDepartmentId) {
                return false;
            }
        } else if (historyDepartmentFilter !== 'all') {
            // ID específico de departamento
            const targetId = parseInt(historyDepartmentFilter, 10);
            if (!Number.isNaN(targetId) && p.department_id !== targetId) {
                return false;
            }
        }
        // Quick filter chip
        if (historyQuickFilter !== 'all' && ! historyStatusGroups[historyQuickFilter].includes(p.status)) {
            return false;
        }
        // Status filter dropdown
        if (historyStatus !== 'all' && p.status !== historyStatus) {
            return false;
        }
        // Semana de provisión (solo si el toggle de filtro semanal está activo)
        if (weekFilterEnabled && (p.week_number !== selectedWeek || p.week_year !== selectedYear)) {
            return false;
        }
        // Búsqueda global expandida (folio, proveedor, concepto, categoría, descripción, RFC, folio factura, solicitante)
        if (historySearch) {
            const q = historySearch.toLowerCase();
            const folioStr = String(p.folio_number).padStart(5, '0');
            if (
                !folioStr.includes(q)
                && !p.provider.toLowerCase().includes(q)
                && !p.concept_name.toLowerCase().includes(q)
                && !p.category_name.toLowerCase().includes(q)
                && !(p.description ?? '').toLowerCase().includes(q)
                && !(p.rfc ?? '').toLowerCase().includes(q)
                && !(p.invoice_folio ?? '').toLowerCase().includes(q)
                && !p.user_name.toLowerCase().includes(q)
            ) {
                return false;
            }
        }
        // Filtros por columna (popover)
        for (const [column, value] of Object.entries(historyColumnFilters)) {
            if (!value) continue;
            const q = value.toLowerCase();
            const field = (() => {
                switch (column) {
                    case 'folio': return String(p.folio_number).padStart(5, '0');
                    case 'concept': return p.concept_name.toLowerCase();
                    case 'category': return p.category_name.toLowerCase();
                    case 'provider': return p.provider.toLowerCase();
                    case 'user': return p.user_name.toLowerCase();
                    case 'description': return (p.description ?? '').toLowerCase();
                    case 'department': return p.department_name;
                    case 'payment_provision_date': return p.payment_provision_date ?? '';
                    case 'created_at': return p.created_at?.slice(0, 10) ?? '';
                    case 'status': return p.status;
                    default: return '';
                }
            })();
            // Exact match para selects y fechas; substring para texto libre
            if (column === 'status' || column === 'department' || column === 'payment_provision_date' || column === 'created_at') {
                if (field !== value) return false;
            } else {
                if (!field.includes(q)) return false;
            }
        }
        return true;
    }).slice().sort((a, b) => {
        if (!historySortBy) return 0;
        const dir = historySortBy.direction === 'asc' ? 1 : -1;
        const get = (p: HistoryPayment): string | number => {
            switch (historySortBy.column) {
                case 'folio': return p.folio_number;
                case 'status': return p.status;
                case 'payment_provision_date': return p.payment_provision_date ?? '';
                case 'concept': return p.concept_name;
                case 'category': return p.category_name;
                case 'provider': return p.provider;
                case 'total': return parseFloat(p.total);
                case 'approved_amount': return p.approved_amount ? parseFloat(p.approved_amount) : -1;
                case 'user': return p.user_name;
                case 'created_at': return p.created_at ?? '';
                default: return 0;
            }
        };
        const va = get(a);
        const vb = get(b);
        if (typeof va === 'number' && typeof vb === 'number') return (va - vb) * dir;
        return String(va).localeCompare(String(vb)) * dir;
    });

    const historyTotalPages = Math.max(1, Math.ceil(filteredHistory.length / historyPerPage));
    const historyCurrentPage = Math.min(historyPage, historyTotalPages);
    const paginatedHistory = filteredHistory.slice(
        (historyCurrentPage - 1) * historyPerPage,
        historyCurrentPage * historyPerPage,
    );

    // "Pagos Pendientes de Documentos" — filtrado + ordenamiento + paginación
    const filteredAuthorized = (authorizedPayments?.payments ?? []).filter((p) => {
        for (const [column, value] of Object.entries(authorizedColumnFilters)) {
            if (!value) continue;
            const q = value.toLowerCase();
            const field = (() => {
                switch (column) {
                    case 'folio': return String(p.folio_number).padStart(5, '0');
                    case 'concept': return p.concept_name.toLowerCase();
                    case 'description': return (p.description ?? '').toLowerCase();
                    case 'category': return p.category_name.toLowerCase();
                    case 'provider': return p.provider.toLowerCase();
                    case 'department': return p.department_name;
                    case 'user': return p.user_name.toLowerCase();
                    default: return '';
                }
            })();
            if (column === 'department') {
                if (field !== value) return false;
            } else {
                if (!field.includes(q)) return false;
            }
        }
        return true;
    }).slice().sort((a, b) => {
        if (!authorizedSortBy) return 0;
        const dir = authorizedSortBy.direction === 'asc' ? 1 : -1;
        const get = (p: AuthorizedPayment): string | number => {
            switch (authorizedSortBy.column) {
                case 'folio': return p.folio_number;
                case 'concept': return p.concept_name;
                case 'category': return p.category_name;
                case 'provider': return p.provider;
                case 'department': return p.department_name;
                case 'user': return p.user_name;
                case 'approved_amount': return parseFloat(p.approved_amount);
                default: return 0;
            }
        };
        const va = get(a);
        const vb = get(b);
        if (typeof va === 'number' && typeof vb === 'number') return (va - vb) * dir;
        return String(va).localeCompare(String(vb)) * dir;
    });

    const authorizedTotalPages = Math.max(1, Math.ceil(filteredAuthorized.length / authorizedPerPage));
    const authorizedCurrentPage = Math.min(authorizedPage, authorizedTotalPages);
    const paginatedAuthorized = filteredAuthorized.slice(
        (authorizedCurrentPage - 1) * authorizedPerPage,
        authorizedCurrentPage * authorizedPerPage,
    );

    const clearAuthorizedFilters = () => {
        setAuthorizedColumnFilters({});
        setAuthorizedSortBy(null);
        setAuthorizedPage(1);
    };

    const authorizedDepartmentOptions = Array.from(
        new Set((authorizedPayments?.payments ?? []).map((p) => p.department_name).filter((d) => d && d !== '—'))
    ).sort().map((d) => ({ value: d, label: d }));

    const clearHistoryFilters = () => {
        setHistorySearch('');
        setHistoryStatus('all');
        setSelectedWeek(projectDashboard.current_week);
        setSelectedYear(projectDashboard.current_year);
        setHistoryQuickFilter('all');
        setHistoryDepartmentFilter('mine');
        setWeekFilterEnabled(true);
        setHistoryColumnFilters({});
        setHistorySortBy(null);
        setHistoryPage(1);
    };

    const hasActiveHistoryFilters = historySearch !== '' || historyStatus !== 'all' || selectedWeek !== projectDashboard.current_week || selectedYear !== projectDashboard.current_year || historyQuickFilter !== 'all' || historyDepartmentFilter !== 'mine' || ! weekFilterEnabled || Object.keys(historyColumnFilters).length > 0;

    // URL para el PDF del historial respetando todos los filtros.
    const buildPaymentHistoryPdfUrl = (): string => {
        const params = new URLSearchParams();
        if (canSeeAllDepartments && historyDepartmentFilter === 'all') {
            params.set('department_id', 'all');
        }
        if (historyStatus !== 'all') {
            params.set('status', historyStatus);
        }
        if (historyQuickFilter !== 'all') {
            params.set('quick_filter', historyQuickFilter);
        }
        if (historySearch !== '') {
            params.set('search', historySearch);
        }
        if (weekFilterEnabled) {
            params.set('week_number', String(selectedWeek));
            params.set('week_year', String(selectedYear));
        }
        const qs = params.toString();
        return `/investment-sheets/consolidated/${project.id}/payment-history-pdf${qs ? '?' + qs : ''}`;
    };

    const selectedHistoryPayment = historyDetailUuid
        ? userPaymentHistory.find((p) => p.uuid === historyDetailUuid)
        : null;

    // Normalizado a MXN (cada borrador × su tipo de cambio) para sumar monedas mezcladas.
    const selectedDraftTotal = (draftBatch?.payments ?? [])
        .filter((p) => selectedDraftIds.has(p.uuid))
        .reduce((sum, p) => sum + Number(p.total) * displayCurrency.rateOf(p.currency_id), 0);

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

                {/* Project Dashboard — 2 columnas: izquierda apila Calendario+Fechas, derecha Presupuesto */}
                {(() => {
                    const original = Number(projectDashboard.original_budget);
                    const aditivas = Number(projectDashboard.additional_budget);
                    const updated = Number(projectDashboard.updated_budget);
                    const committed = Number(projectDashboard.committed_total);
                    const paid = Number(projectDashboard.paid_total);
                    const remaining = Number(projectDashboard.remaining_budget);

                    const origAditBase = original + aditivas;
                    const origPct = origAditBase > 0 ? (original / origAditBase) * 100 : 0;
                    const aditPct = origAditBase > 0 ? (aditivas / origAditBase) * 100 : 0;
                    const changePct = original > 0 ? (updated / original - 1) * 100 : 0;
                    const paidPct = updated > 0 ? Math.min(100, (paid / updated) * 100) : 0;
                    const committedPct = updated > 0 ? Math.min(100 - paidPct, (committed / updated) * 100) : 0;

                    const durationDays = project.start_date && project.opening_date
                        ? Math.round(
                            (new Date(project.opening_date + 'T00:00:00').getTime() -
                                new Date(project.start_date + 'T00:00:00').getTime()) / 86_400_000,
                          )
                        : null;

                    const updatedFull = formatCurrencyPlain(projectDashboard.updated_budget);
                    const updatedDot = updatedFull.lastIndexOf('.');

                    return (
                        <div className="grid gap-4 lg:grid-cols-2">
                            {/* Columna izquierda: Calendario (arriba) + Fechas del Proyecto (abajo) */}
                            <div className="flex flex-col gap-4">
                                {/* Tarjeta 1 — Calendario */}
                                <Card className="flex flex-1 flex-col">
                                    <CardHeader className="pb-3">
                                        <CardTitle className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                            <Calendar className="h-4 w-4" />
                                            Calendario
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent className="flex flex-1 items-center">
                                        <div className="grid w-full grid-cols-2 gap-3">
                                            <div className="rounded-xl bg-muted/40 p-4">
                                                <p className="text-xs text-muted-foreground">Semana</p>
                                                <p className="text-3xl font-bold tracking-tight">{projectDashboard.current_week}</p>
                                                <p className="text-xs text-muted-foreground">año {projectDashboard.current_year}</p>
                                            </div>
                                            <div className="rounded-xl bg-muted/40 p-4">
                                                <p className="text-xs text-muted-foreground">Fecha</p>
                                                <p className="text-xl font-bold tracking-tight">{formatDayMonth(projectDashboard.today_date)}</p>
                                                <p className="text-xs capitalize text-muted-foreground">
                                                    de {projectDashboard.current_year} · {formatWeekday(projectDashboard.today_date)}
                                                </p>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                {/* Tarjeta 2 — Fechas del Proyecto */}
                                <Card className="flex flex-1 flex-col">
                                    <CardHeader className="pb-3">
                                        <div className="flex items-center justify-between">
                                            <CardTitle className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                                <CalendarRange className="h-4 w-4" />
                                                Fechas del Proyecto
                                            </CardTitle>
                                            {durationDays !== null && <Badge variant="secondary">{durationDays} días</Badge>}
                                        </div>
                                    </CardHeader>
                                    <CardContent className="flex flex-1 items-center">
                                        {project.start_date && project.opening_date ? (
                                            <div className="w-full">
                                                <div className="relative mb-3">
                                                    <div className="h-1.5 rounded-full bg-gradient-to-r from-primary to-emerald-500" />
                                                    <span className="absolute left-0 top-1/2 h-3 w-3 -translate-y-1/2 rounded-full border-2 border-background bg-primary" />
                                                    <span className="absolute right-0 top-1/2 h-3 w-3 -translate-y-1/2 rounded-full border-2 border-background bg-emerald-500" />
                                                </div>
                                                <div className="flex items-end justify-between">
                                                    <div>
                                                        <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Inicio</p>
                                                        <p className="text-sm font-semibold">{formatDateShort(project.start_date)}</p>
                                                    </div>
                                                    <div className="text-right">
                                                        <p className="text-[10px] uppercase tracking-wide text-muted-foreground">Apertura</p>
                                                        <p className="text-sm font-semibold">{formatDateShort(project.opening_date)}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="w-full space-y-2">
                                                <div className="flex items-baseline justify-between text-sm">
                                                    <span className="text-muted-foreground">Fecha Inicio</span>
                                                    {project.start_date ? (
                                                        <span className="font-medium">{formatDateEs(project.start_date)}</span>
                                                    ) : (
                                                        <span className="font-medium italic text-muted-foreground">Sin definir</span>
                                                    )}
                                                </div>
                                                <div className="flex items-baseline justify-between text-sm">
                                                    <span className="text-muted-foreground">Fecha Apertura</span>
                                                    {project.opening_date ? (
                                                        <span className="font-medium">{formatDateEs(project.opening_date)}</span>
                                                    ) : (
                                                        <span className="font-medium italic text-muted-foreground">Sin definir</span>
                                                    )}
                                                </div>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Columna derecha — Tarjeta 3 Presupuesto */}
                            <Card>
                                <CardHeader className="pb-3">
                                    <div className="flex items-center justify-between gap-2">
                                        <CardTitle className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                            <Wallet className="h-4 w-4" />
                                            Presupuesto
                                        </CardTitle>
                                        {project.authorized_budget !== null ? (
                                            <Badge variant="outline">Autorizado: {formatCurrencyPlain(project.authorized_budget)}</Badge>
                                        ) : (
                                            <Badge variant="outline" className="border-amber-300 text-amber-700 dark:border-amber-800 dark:text-amber-400">
                                                Autorizado: sin definir
                                            </Badge>
                                        )}
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    {/* Hero oscuro — Presupuesto actualizado */}
                                    <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 to-slate-800 p-5 text-white">
                                        {original > 0 && (
                                            <span className={cn(
                                                'absolute right-4 top-4 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium',
                                                changePct >= 0 ? 'bg-emerald-500/15 text-emerald-300' : 'bg-red-500/15 text-red-300',
                                            )}>
                                                {changePct >= 0 ? <ArrowUpRight className="h-3 w-3" /> : <ArrowDownRight className="h-3 w-3" />}
                                                {changePct >= 0 ? '+' : ''}{changePct.toFixed(1)}%
                                            </span>
                                        )}
                                        <p className="text-xs uppercase tracking-wide text-slate-400">Presupuesto Actualizado</p>
                                        <p className="mt-1 text-3xl font-bold tracking-tight">
                                            {updatedDot === -1 ? updatedFull : (
                                                <>
                                                    {updatedFull.slice(0, updatedDot)}
                                                    <span className="text-xl text-slate-400">{updatedFull.slice(updatedDot)}</span>
                                                </>
                                            )}
                                        </p>
                                        <p className="mt-1 text-xs text-slate-400">{project.currency_prefix} · Original + aditivas</p>
                                    </div>

                                    {/* Barra Original / Aditivas */}
                                    <div>
                                        <div className="flex h-2.5 w-full overflow-hidden rounded-full bg-muted">
                                            <div className="h-full bg-primary" style={{ width: `${origPct}%` }} />
                                            <div className="h-full bg-emerald-500" style={{ width: `${aditPct}%` }} />
                                        </div>
                                        <div className="mt-2 flex items-center gap-4 text-xs text-muted-foreground">
                                            <span className="flex items-center gap-1.5">
                                                <span className="h-2 w-2 rounded-full bg-primary" /> Original {origPct.toFixed(1)}%
                                            </span>
                                            <span className="flex items-center gap-1.5">
                                                <span className="h-2 w-2 rounded-full bg-emerald-500" /> Aditivas {aditPct.toFixed(1)}%
                                            </span>
                                        </div>
                                    </div>

                                    {/* Filas */}
                                    <div className="space-y-1.5">
                                        <div className="flex items-baseline justify-between text-sm">
                                            <span className="text-muted-foreground">Original</span>
                                            <span className="font-medium">{formatCurrencyPlain(projectDashboard.original_budget)}</span>
                                        </div>
                                        <div className="flex items-baseline justify-between text-sm">
                                            <span className="text-emerald-700 dark:text-emerald-400">Aditivas</span>
                                            <span className="font-medium text-emerald-700 dark:text-emerald-400">+ {formatCurrencyPlain(projectDashboard.additional_budget)}</span>
                                        </div>
                                        <div className="flex items-baseline justify-between text-sm">
                                            <span className="text-muted-foreground">Deductivas</span>
                                            <span className="font-medium text-muted-foreground">{formatCurrencyPlain(projectDashboard.deductive_budget)}</span>
                                        </div>
                                        {(Number(projectDashboard.pending_additional) > 0 || Number(projectDashboard.pending_deductive) > 0) && (
                                            <>
                                                <div className="my-1.5 flex items-center gap-2 text-[10px] uppercase tracking-wider text-muted-foreground">
                                                    <span className="h-px flex-1 bg-border" />
                                                    <span>En proceso de autorización</span>
                                                    <span className="h-px flex-1 bg-border" />
                                                </div>
                                                {Number(projectDashboard.pending_additional) > 0 && (
                                                    <div className="flex items-baseline justify-between text-sm">
                                                        <span className="text-amber-700 dark:text-amber-400">Aditivas en proceso</span>
                                                        <span className="font-medium text-amber-700 dark:text-amber-400">{formatCurrencyPlain(projectDashboard.pending_additional)}</span>
                                                    </div>
                                                )}
                                                {Number(projectDashboard.pending_deductive) > 0 && (
                                                    <div className="flex items-baseline justify-between text-sm">
                                                        <span className="text-amber-700 dark:text-amber-400">Deductivas en proceso</span>
                                                        <span className="font-medium text-amber-700 dark:text-amber-400">{formatCurrencyPlain(projectDashboard.pending_deductive)}</span>
                                                    </div>
                                                )}
                                            </>
                                        )}
                                        <div className="flex items-baseline justify-between border-t pt-2 text-sm">
                                            <span className="font-semibold">Actualizado</span>
                                            <span className="font-bold">{formatCurrencyPlain(projectDashboard.updated_budget)}</span>
                                        </div>
                                    </div>

                                    {/* Avance del presupuesto: Comprometido + Pagado sobre el presupuesto actualizado */}
                                    <div className="rounded-xl border bg-muted/30 p-4">
                                        <div className="flex items-center justify-between">
                                            <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Avance del Presupuesto</p>
                                            <p className="text-xs font-semibold text-muted-foreground">{Math.min(100, committedPct + paidPct).toFixed(0)}% consumido</p>
                                        </div>
                                        <div className="mt-2 flex h-2.5 w-full overflow-hidden rounded-full bg-muted">
                                            <div className="h-full bg-emerald-500 transition-all" style={{ width: `${paidPct}%` }} />
                                            <div className="h-full bg-amber-400 transition-all" style={{ width: `${committedPct}%` }} />
                                        </div>
                                        <div className="mt-3 grid grid-cols-3 gap-2">
                                            <div className="rounded-lg bg-background p-3">
                                                <p className="text-xs text-muted-foreground">Comprometido</p>
                                                <p className="text-base font-bold text-amber-600 dark:text-amber-400">{formatCurrencyPlain(projectDashboard.committed_total)}</p>
                                            </div>
                                            <div className="rounded-lg bg-background p-3">
                                                <p className="text-xs text-muted-foreground">Pagado</p>
                                                <p className="text-base font-bold text-emerald-600 dark:text-emerald-400">{formatCurrencyPlain(projectDashboard.paid_total)}</p>
                                            </div>
                                            <div className="rounded-lg bg-background p-3">
                                                <p className="text-xs text-muted-foreground">Disponible</p>
                                                <p className={cn(
                                                    'text-base font-bold',
                                                    remaining < 0 ? 'text-red-600 dark:text-red-400' : 'text-orange-600 dark:text-orange-400',
                                                )}>
                                                    {formatCurrencyPlain(projectDashboard.remaining_budget)}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    );
                })()}

                {/* Department Breakdown */}
                {departmentBreakdown.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Inversión por Departamento</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                {departmentBreakdown.map((dept) => {
                                    const deptTotal = Math.max(1, Number(dept.total));
                                    const paidW = Math.min(100, (Number(dept.paid) / deptTotal) * 100);
                                    const committedW = Math.min(100 - paidW, (Number(dept.committed) / deptTotal) * 100);
                                    return (
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

                                        {/* Barra apilada: pagado + comprometido sobre el total */}
                                        <div className="flex items-center gap-2.5">
                                            <div className="relative flex h-2 flex-1 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                                <div className="h-full bg-emerald-500 transition-all" style={{ width: `${paidW}%` }} />
                                                <div className="h-full bg-amber-400 transition-all" style={{ width: `${committedW}%` }} />
                                            </div>
                                            <span className="whitespace-nowrap text-xs font-medium text-gray-500 dark:text-gray-400">
                                                {dept.percent_paid.toFixed(0)}% consumido
                                            </span>
                                        </div>

                                        {/* Comprometido / Pagado / Disponible */}
                                        <div className="space-y-1">
                                            <div className="flex items-center justify-between text-xs">
                                                <span className="text-amber-700 dark:text-amber-400">Comprometido</span>
                                                <span className="font-medium text-amber-700 dark:text-amber-400">{formatCurrency(dept.committed)}</span>
                                            </div>
                                            <div className="flex items-center justify-between text-xs">
                                                <span className="text-emerald-700 dark:text-emerald-400">Pagado</span>
                                                <span className="font-medium text-emerald-700 dark:text-emerald-400">{formatCurrency(dept.paid)}</span>
                                            </div>
                                            <div className="flex items-center justify-between text-xs">
                                                <span className="text-gray-600 dark:text-gray-300">Disponible</span>
                                                <span className="font-medium text-gray-700 dark:text-gray-200">{formatCurrency(dept.pending)}</span>
                                            </div>
                                        </div>
                                    </button>
                                    );
                                })}
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
                                                const isUserDept = !!firstItem.department?.id && userDepartmentIds.includes(firstItem.department.id);
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
                                                            <td className="px-4 py-3 border-r border-gray-100 dark:border-gray-800 max-w-[420px] align-top">
                                                                <div className="flex items-start gap-1.5">
                                                                    <div className="flex-1 min-w-0">
                                                                        <div className="font-medium text-foreground whitespace-normal break-words leading-snug">
                                                                            {group.categoryName ? `${group.categoryName} - ${group.conceptName}` : group.conceptName}
                                                                        </div>
                                                                        {!isSingle && (
                                                                            <div className="text-xs text-gray-500 mt-0.5">{group.items.length} conceptos</div>
                                                                        )}
                                                                        {isSingle && firstItem.is_addendum && (
                                                                            <Badge variant="outline" className="mt-0.5 border-amber-400 text-amber-600 text-[10px] dark:border-amber-600 dark:text-amber-400">
                                                                                Aditiva
                                                                            </Badge>
                                                                        )}
                                                                    </div>
                                                                    {canEditRequestConcept && isSingle && (
                                                                        <button
                                                                            type="button"
                                                                            onClick={(e) => { e.stopPropagation(); openEditDescription(firstItem); }}
                                                                            className="shrink-0 rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                                                                            title="Editar concepto y descripción"
                                                                        >
                                                                            <Pencil className="h-3.5 w-3.5" />
                                                                        </button>
                                                                    )}
                                                                </div>
                                                            </td>
                                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800 max-w-[200px]" title={isSingle ? (firstItem.description ?? undefined) : undefined}>
                                                                {isSingle ? (
                                                                    <span className="block truncate uppercase">
                                                                        {firstItem.description ? firstItem.description : <span className="text-gray-400">—</span>}
                                                                    </span>
                                                                ) : (
                                                                    <span className="text-gray-400">—</span>
                                                                )}
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
                                                                        {canEditRequestConcept && (
                                                                            <button
                                                                                type="button"
                                                                                onClick={(e) => { e.stopPropagation(); openEditDescription(ir); }}
                                                                                className="shrink-0 rounded p-0.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                                                                                title="Editar concepto y descripción"
                                                                            >
                                                                                <Pencil className="h-3 w-3" />
                                                                            </button>
                                                                        )}
                                                                    </div>
                                                                </td>
                                                                <td className="px-4 py-2.5 text-xs text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800 max-w-[200px]" title={ir.description ?? undefined}>
                                                                    <span className="block truncate uppercase">
                                                                        {ir.description ? ir.description : <span className="text-gray-400">—</span>}
                                                                    </span>
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
                                                                    {formatNative(ir.total, ir.currency?.id)}
                                                                    {nativeNoteOf(ir.total, ir.currency?.id) && (
                                                                        <div className="text-[10px] text-gray-400">orig. {nativeNoteOf(ir.total, ir.currency?.id)}</div>
                                                                    )}
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
                                                    {formatNative(payment.total, payment.currency_id)}
                                                    {nativeNoteOf(payment.total, payment.currency_id) && (
                                                        <div className="text-[10px] font-normal text-gray-400">orig. {nativeNoteOf(payment.total, payment.currency_id)}</div>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <TooltipProvider delayDuration={200}>
                                                            <Tooltip>
                                                                <TooltipTrigger asChild>
                                                                    <span className="inline-block">
                                                                        <Button
                                                                            size="sm"
                                                                            variant="ghost"
                                                                            className="text-blue-600 hover:bg-blue-50 hover:text-blue-700 dark:text-blue-400 dark:hover:bg-blue-950"
                                                                            onClick={() => openEditDraftPayment(payment)}
                                                                            disabled={!paymentPolicy.capture.canAct}
                                                                            title={paymentPolicy.capture.canAct ? 'Editar pago' : undefined}
                                                                        >
                                                                            <Pencil className="h-3.5 w-3.5" />
                                                                        </Button>
                                                                    </span>
                                                                </TooltipTrigger>
                                                                {!paymentPolicy.capture.canAct && (
                                                                    <TooltipContent side="top" className="max-w-sm text-xs">
                                                                        Ventana de captura cerrada. Próxima apertura: <span className="font-semibold">{formatPolicyTime(paymentPolicy.capture.opensAt)}</span>.
                                                                    </TooltipContent>
                                                                )}
                                                            </Tooltip>
                                                        </TooltipProvider>
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
                                <TooltipProvider delayDuration={200}>
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <span className="inline-block">
                                                <Button
                                                    disabled={selectedDraftIds.size === 0 || submittingBatch || !paymentPolicy.submit.canAct}
                                                    onClick={handleSubmitBatch}
                                                >
                                                    <Send className="mr-2 h-4 w-4" />
                                                    {submittingBatch ? 'Enviando...' : 'Enviar a Autorización'}
                                                </Button>
                                            </span>
                                        </TooltipTrigger>
                                        {!paymentPolicy.submit.canAct && (
                                            <TooltipContent side="top" className="max-w-sm text-xs">
                                                Ventana de envío cerrada. Próxima apertura: <span className="font-semibold">{formatPolicyTime(paymentPolicy.submit.opensAt)}</span>.
                                            </TooltipContent>
                                        )}
                                    </Tooltip>
                                </TooltipProvider>
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
                            {filteredAuthorized.length === 0 ? (
                                <div className="rounded-md border border-dashed border-gray-300 dark:border-gray-700 py-10 text-center text-sm text-muted-foreground">
                                    No hay pagos pendientes de documentos que coincidan con los filtros aplicados.
                                    <div className="mt-2">
                                        <Button variant="outline" size="sm" onClick={clearAuthorizedFilters}>Limpiar filtros</Button>
                                    </div>
                                </div>
                            ) : (
                                <>
                                    <div className="overflow-x-auto rounded-md border border-gray-200 dark:border-gray-700">
                                        <table className="w-full text-sm">
                                            <thead className="bg-gray-50 dark:bg-gray-800">
                                                <tr className="border-b-2 border-gray-200 text-left text-gray-600 dark:border-gray-700 dark:text-gray-300">
                                                    <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700 align-middle">
                                                        <SortableHeader label="Folio" column="folio" sortBy={authorizedSortBy} onSort={handleAuthorizedSort}>
                                                            <ColumnFilterPopover
                                                                value={authorizedColumnFilters.folio ?? ''}
                                                                onChange={(v) => setAuthorizedColumnFilter('folio', v)}
                                                            />
                                                        </SortableHeader>
                                                    </th>
                                                    <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700 align-middle">
                                                        <SortableHeader label="Concepto" column="concept" sortBy={authorizedSortBy} onSort={handleAuthorizedSort}>
                                                            <ColumnFilterPopover
                                                                value={authorizedColumnFilters.concept ?? ''}
                                                                onChange={(v) => setAuthorizedColumnFilter('concept', v)}
                                                            />
                                                        </SortableHeader>
                                                    </th>
                                                    <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700 align-middle">
                                                        <span className="inline-flex items-center">
                                                            Descripción
                                                            <ColumnFilterPopover
                                                                value={authorizedColumnFilters.description ?? ''}
                                                                onChange={(v) => setAuthorizedColumnFilter('description', v)}
                                                            />
                                                        </span>
                                                    </th>
                                                    <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700 align-middle">
                                                        <SortableHeader label="Categoría del Concepto" column="category" sortBy={authorizedSortBy} onSort={handleAuthorizedSort}>
                                                            <ColumnFilterPopover
                                                                value={authorizedColumnFilters.category ?? ''}
                                                                onChange={(v) => setAuthorizedColumnFilter('category', v)}
                                                            />
                                                        </SortableHeader>
                                                    </th>
                                                    <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700 align-middle">
                                                        <SortableHeader label="Proveedor" column="provider" sortBy={authorizedSortBy} onSort={handleAuthorizedSort}>
                                                            <ColumnFilterPopover
                                                                value={authorizedColumnFilters.provider ?? ''}
                                                                onChange={(v) => setAuthorizedColumnFilter('provider', v)}
                                                            />
                                                        </SortableHeader>
                                                    </th>
                                                    <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700 align-middle">
                                                        <SortableHeader label="Departamento" column="department" sortBy={authorizedSortBy} onSort={handleAuthorizedSort}>
                                                            <ColumnFilterPopover
                                                                value={authorizedColumnFilters.department ?? ''}
                                                                onChange={(v) => setAuthorizedColumnFilter('department', v)}
                                                                options={authorizedDepartmentOptions}
                                                            />
                                                        </SortableHeader>
                                                    </th>
                                                    <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700 align-middle">
                                                        <SortableHeader label="Solicitante" column="user" sortBy={authorizedSortBy} onSort={handleAuthorizedSort}>
                                                            <ColumnFilterPopover
                                                                value={authorizedColumnFilters.user ?? ''}
                                                                onChange={(v) => setAuthorizedColumnFilter('user', v)}
                                                            />
                                                        </SortableHeader>
                                                    </th>
                                                    <th className="px-4 py-3 font-semibold whitespace-nowrap text-right border-r border-gray-200 dark:border-gray-700 align-middle">
                                                        <SortableHeader label="Monto a pagar" column="approved_amount" sortBy={authorizedSortBy} onSort={handleAuthorizedSort} align="right" />
                                                    </th>
                                                    <th className="px-4 py-3 font-semibold whitespace-nowrap text-right">Documentos</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                                {paginatedAuthorized.map((payment) => (
                                                    <tr key={payment.uuid} className="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                                        <td className="px-4 py-3 font-mono text-xs text-gray-500 border-r border-gray-100 dark:border-gray-800">
                                                            #{String(payment.folio_number).padStart(5, '0')}
                                                            {payment.is_legacy && (
                                                                <Badge variant="secondary" className="ml-1.5 align-middle text-[10px]">Histórico</Badge>
                                                            )}
                                                        </td>
                                                        <td className="px-4 py-3 font-medium border-r border-gray-100 dark:border-gray-800">{payment.concept_name}</td>
                                                        <td className="px-4 py-3 text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800 max-w-[200px] truncate uppercase" title={payment.description ?? undefined}>
                                                            {payment.description ? payment.description : <span className="text-gray-400">—</span>}
                                                        </td>
                                                        <td className="px-4 py-3 text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800">{payment.category_name}</td>
                                                        <td className="px-4 py-3 text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800">{payment.provider}</td>
                                                        <td className="px-4 py-3 text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800">{payment.department_name}</td>
                                                        <td className="px-4 py-3 text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800">{payment.user_name}</td>
                                                        <td className="px-4 py-3 text-right font-mono font-semibold border-r border-gray-100 dark:border-gray-800">
                                                            {formatNative(payment.approved_amount, payment.currency_id)}
                                                            {nativeNoteOf(payment.approved_amount, payment.currency_id) && (
                                                                <div className="text-[10px] font-normal text-gray-400">orig. {nativeNoteOf(payment.approved_amount, payment.currency_id)}</div>
                                                            )}
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
                                                                onClick={() => openUploadDialog(payment.uuid, payment.payment_type)}
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

                                    {/* Footer: selector + contador + paginación */}
                                    <div className="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground">
                                        <div className="flex items-center gap-3">
                                            <div className="flex items-center gap-1.5">
                                                <Select value={String(authorizedPerPage)} onValueChange={(v) => { setAuthorizedPerPage(Number(v)); setAuthorizedPage(1); }}>
                                                    <SelectTrigger className="h-7 w-[70px] text-xs"><SelectValue /></SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="10">10</SelectItem>
                                                        <SelectItem value="25">25</SelectItem>
                                                        <SelectItem value="50">50</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <span>por página</span>
                                            </div>
                                            <div>
                                                Mostrando {(authorizedCurrentPage - 1) * authorizedPerPage + 1}-{Math.min(authorizedCurrentPage * authorizedPerPage, filteredAuthorized.length)} de {filteredAuthorized.length} pagos pendientes
                                            </div>
                                        </div>
                                        <NumberedPagination
                                            currentPage={authorizedCurrentPage}
                                            totalPages={authorizedTotalPages}
                                            onPageChange={setAuthorizedPage}
                                        />
                                    </div>
                                </>
                            )}
                        </CardContent>
                    </Card>
                )}

                {/* Payment History Card */}
                <Card>
                    <CardHeader>
                        <CardTitle>
                            {(() => {
                                // Caso: filtro a 'all' (solo privilegiados pueden tener ese estado)
                                if (canSeeAllDepartments && historyDepartmentFilter === 'all') {
                                    return 'Historial de Pagos — TODOS LOS DEPARTAMENTOS';
                                }
                                // Caso: filtro a un dpto específico (ID)
                                if (historyDepartmentFilter !== 'mine' && historyDepartmentFilter !== 'all') {
                                    const targetId = parseInt(historyDepartmentFilter, 10);
                                    const target = (canSeeAllDepartments ? (departments ?? []) : (userDepartments ?? []))
                                        .find((d) => d.id === targetId);
                                    if (target) {
                                        return `Historial de Pagos — ${target.name}`;
                                    }
                                }
                                // Caso: filtro 'mine' con multi-dpto no privilegiado
                                if (!canSeeAllDepartments && userDepartments && userDepartments.length > 1) {
                                    return 'Historial de Pagos — Mis Departamentos';
                                }
                                // Caso: privilegiado en 'mine' o mono-dpto user
                                return userDepartmentName
                                    ? `Historial de Pagos — ${userDepartmentName}`
                                    : 'Historial de Pagos';
                            })()}
                        </CardTitle>
                        <p className="mt-1 text-xs text-muted-foreground">
                            {(() => {
                                if (canSeeAllDepartments && historyDepartmentFilter === 'all') {
                                    return 'Todos los pagos del proyecto, de todos los departamentos. Los borradores aparecen en la tarjeta "Pagos en Borrador".';
                                }
                                if (historyDepartmentFilter !== 'mine' && historyDepartmentFilter !== 'all') {
                                    const targetId = parseInt(historyDepartmentFilter, 10);
                                    const target = (canSeeAllDepartments ? (departments ?? []) : (userDepartments ?? []))
                                        .find((d) => d.id === targetId);
                                    if (target) {
                                        return <>Todos los pagos solicitados en <span className="font-medium">{target.name}</span> para este proyecto, en cualquier etapa del flujo. Los borradores aparecen en la tarjeta "Pagos en Borrador".</>;
                                    }
                                }
                                if (!canSeeAllDepartments && userDepartments && userDepartments.length > 1) {
                                    const names = userDepartments.map((d) => d.name).join(', ');
                                    return <>Todos los pagos solicitados en tus departamentos (<span className="font-medium">{names}</span>) para este proyecto, en cualquier etapa del flujo. Los borradores aparecen en la tarjeta "Pagos en Borrador".</>;
                                }
                                return <>Todos los pagos solicitados en {userDepartmentName ? <span className="font-medium">{userDepartmentName}</span> : 'tu departamento'} para este proyecto, en cualquier etapa del flujo. Los borradores aparecen en la tarjeta "Pagos en Borrador".</>;
                            })()}
                        </p>
                    </CardHeader>
                    <CardContent>
                        {/* Filtros */}
                        <div className="flex flex-wrap items-end gap-3 mb-4">
                            <div className="space-y-1">
                                <div className="flex items-center gap-1">
                                    <label className="text-xs font-medium text-gray-500 dark:text-gray-400">Buscar</label>
                                    <TooltipProvider delayDuration={200}>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <button type="button" className="text-gray-400 hover:text-gray-600">
                                                    <Info className="h-3 w-3" />
                                                </button>
                                            </TooltipTrigger>
                                            <TooltipContent side="top" className="max-w-xs text-xs">
                                                Busca en: folio, proveedor, concepto, categoría, descripción, RFC, folio de factura y solicitante.
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </div>
                                <div className="relative">
                                    <Search className="absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                    <Input
                                        className="pl-8 w-64"
                                        placeholder="Buscar en toda la tabla..."
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
                            <div className={cn('space-y-1', ! weekFilterEnabled && 'opacity-40 pointer-events-none')}>
                                <label className="text-xs font-medium text-gray-500 dark:text-gray-400">Semana de provisión</label>
                                <WeekNavigator
                                    week={selectedWeek}
                                    year={selectedYear}
                                    currentWeek={projectDashboard.current_week}
                                    currentYear={projectDashboard.current_year}
                                    onNavigate={navigateWeek}
                                />
                            </div>
                            {canSeeAllDepartments && (
                                <div className="space-y-1">
                                    <label className="text-xs font-medium text-gray-500 dark:text-gray-400">&nbsp;</label>
                                    <Button
                                        type="button"
                                        variant={weekFilterEnabled ? 'outline' : 'default'}
                                        size="sm"
                                        onClick={() => { setWeekFilterEnabled((v) => !v); setHistoryPage(1); }}
                                        title={weekFilterEnabled ? 'Mostrar todas las semanas' : 'Activar filtro de semana'}
                                    >
                                        {weekFilterEnabled ? 'Todas las semanas' : '✓ Todas las semanas'}
                                    </Button>
                                </div>
                            )}
                            {(canSeeAllDepartments || (userDepartments && userDepartments.length > 1)) && (
                                <div className="space-y-1">
                                    <label className="text-xs font-medium text-gray-500 dark:text-gray-400">Departamento</label>
                                    <Select value={historyDepartmentFilter} onValueChange={(v) => { setHistoryDepartmentFilter(v); setHistoryPage(1); }}>
                                        <SelectTrigger className="w-56">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {canSeeAllDepartments ? (
                                                <>
                                                    <SelectItem value="mine">{userDepartmentName ?? 'Mi departamento'}</SelectItem>
                                                    <SelectItem value="all">Todos los departamentos</SelectItem>
                                                    {(departments ?? []).map((d) => (
                                                        <SelectItem key={d.id} value={String(d.id)}>{d.name}</SelectItem>
                                                    ))}
                                                </>
                                            ) : (
                                                <>
                                                    <SelectItem value="mine">Mis departamentos</SelectItem>
                                                    {(userDepartments ?? []).map((d) => (
                                                        <SelectItem key={d.id} value={String(d.id)}>{d.name}</SelectItem>
                                                    ))}
                                                </>
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
                            <div className="space-y-1">
                                <label className="text-xs font-medium text-gray-500 dark:text-gray-400">&nbsp;</label>
                                <Button asChild variant="outline" size="sm" className="shrink-0" title="Descargar PDF del historial filtrado">
                                    <a href={buildPaymentHistoryPdfUrl()} target="_blank" rel="noopener noreferrer">
                                        <FileDown className="mr-1 h-4 w-4" /> PDF
                                    </a>
                                </Button>
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
                                                <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700 align-middle">
                                                    <SortableHeader label="Folio" column="folio" sortBy={historySortBy} onSort={handleHistorySort}>
                                                        <ColumnFilterPopover value={historyColumnFilters.folio ?? ''} onChange={(v) => setColumnFilter('folio', v)} placeholder="Folio..." />
                                                    </SortableHeader>
                                                </th>
                                                <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700 align-middle">
                                                    <SortableHeader label="Estatus" column="status" sortBy={historySortBy} onSort={handleHistorySort}>
                                                        <ColumnFilterPopover
                                                            value={historyColumnFilters.status ?? ''}
                                                            onChange={(v) => setColumnFilter('status', v)}
                                                            options={Array.from(new Set(userPaymentHistory.map(p => p.status))).sort().map(s => ({ value: s, label: historyStatusLabel(s) }))}
                                                        />
                                                    </SortableHeader>
                                                </th>
                                                <th className="px-4 py-3 font-semibold border-r border-gray-200 dark:border-gray-700 align-middle leading-tight">
                                                    <TooltipProvider delayDuration={200}>
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <span className="inline-flex">
                                                                    <SortableHeader label="Pro-Pago" column="payment_provision_date" sortBy={historySortBy} onSort={handleHistorySort}>
                                                                        <ColumnFilterPopover
                                                                            value={historyColumnFilters.payment_provision_date ?? ''}
                                                                            onChange={(v) => setColumnFilter('payment_provision_date', v)}
                                                                            type="date"
                                                                        />
                                                                    </SortableHeader>
                                                                </span>
                                                            </TooltipTrigger>
                                                            <TooltipContent side="top" className="text-xs">Completo fecha programación de pago</TooltipContent>
                                                        </Tooltip>
                                                    </TooltipProvider>
                                                </th>
                                                <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700 align-middle">
                                                    <SortableHeader label="Categoría" column="category" sortBy={historySortBy} onSort={handleHistorySort}>
                                                        <ColumnFilterPopover value={historyColumnFilters.category ?? ''} onChange={(v) => setColumnFilter('category', v)} placeholder="Categoría..." />
                                                    </SortableHeader>
                                                </th>
                                                <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700 align-middle">
                                                    <SortableHeader label="Concepto" column="concept" sortBy={historySortBy} onSort={handleHistorySort}>
                                                        <ColumnFilterPopover value={historyColumnFilters.concept ?? ''} onChange={(v) => setColumnFilter('concept', v)} placeholder="Concepto..." />
                                                    </SortableHeader>
                                                </th>
                                                <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700 align-middle">
                                                    <span className="inline-flex items-center">
                                                        Descripción
                                                        <ColumnFilterPopover
                                                            value={historyColumnFilters.description ?? ''}
                                                            onChange={(v) => setColumnFilter('description', v)}
                                                            placeholder="Descripción..."
                                                        />
                                                    </span>
                                                </th>
                                                <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700 align-middle">
                                                    <SortableHeader label="Proveedor" column="provider" sortBy={historySortBy} onSort={handleHistorySort}>
                                                        <ColumnFilterPopover value={historyColumnFilters.provider ?? ''} onChange={(v) => setColumnFilter('provider', v)} placeholder="Proveedor..." />
                                                    </SortableHeader>
                                                </th>
                                                {canSeeAllDepartments && historyDepartmentFilter === 'all' && (
                                                    <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700 align-middle">
                                                        <span className="inline-flex items-center">
                                                            Depto
                                                            <ColumnFilterPopover
                                                                value={historyColumnFilters.department ?? ''}
                                                                onChange={(v) => setColumnFilter('department', v)}
                                                                options={Array.from(new Set(userPaymentHistory.map(p => p.department_name))).sort().map(d => ({ value: d, label: d }))}
                                                            />
                                                        </span>
                                                    </th>
                                                )}
                                                <th className="px-4 py-3 font-semibold whitespace-nowrap text-right border-r border-gray-200 dark:border-gray-700 align-middle">
                                                    <SortableHeader label="Monto Solicitado" column="total" sortBy={historySortBy} onSort={handleHistorySort} align="right" />
                                                </th>
                                                <th className="px-4 py-3 font-semibold whitespace-nowrap text-right border-r border-gray-200 dark:border-gray-700 align-middle">
                                                    <SortableHeader label="Monto Aprobado" column="approved_amount" sortBy={historySortBy} onSort={handleHistorySort} align="right" />
                                                </th>
                                                <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700 align-middle">Documentos</th>
                                                <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700 align-middle">
                                                    <SortableHeader label="Solicitante" column="user" sortBy={historySortBy} onSort={handleHistorySort}>
                                                        <ColumnFilterPopover value={historyColumnFilters.user ?? ''} onChange={(v) => setColumnFilter('user', v)} placeholder="Solicitante..." />
                                                    </SortableHeader>
                                                </th>
                                                <th className="px-4 py-3 font-semibold border-r border-gray-200 dark:border-gray-700 align-middle leading-tight">
                                                    <SortableHeader label="Fecha Solicitud" column="created_at" sortBy={historySortBy} onSort={handleHistorySort}>
                                                        <ColumnFilterPopover
                                                            value={historyColumnFilters.created_at ?? ''}
                                                            onChange={(v) => setColumnFilter('created_at', v)}
                                                            type="date"
                                                        />
                                                    </SortableHeader>
                                                </th>
                                                <th className="px-4 py-3 font-semibold whitespace-nowrap text-right align-middle">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                            {paginatedHistory.map((payment) => (
                                                <tr key={payment.uuid} className="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                                    {/* 1. Folio */}
                                                    <td className="px-4 py-3 font-mono text-xs text-gray-500 border-r border-gray-100 dark:border-gray-800">
                                                        #{String(payment.folio_number).padStart(5, '0')}
                                                    </td>
                                                    {/* 2. Estatus */}
                                                    <td className="px-4 py-3 border-r border-gray-100 dark:border-gray-800">
                                                        <span className={cn('inline-flex px-2 py-0.5 rounded-full text-[10px] font-medium', historyStatusColorClass(payment.status))}>
                                                            {historyStatusLabel(payment.status)}
                                                        </span>
                                                        {payment.is_legacy && (
                                                            <div className="mt-0.5 text-[10px] text-gray-400">Flujo anterior</div>
                                                        )}
                                                    </td>
                                                    {/* 3. Fecha Programación Pago */}
                                                    <td className="px-4 py-3 text-xs text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800 whitespace-nowrap">
                                                        {payment.payment_provision_date ?? <span className="text-gray-400">—</span>}
                                                    </td>
                                                    {/* Categoría */}
                                                    <td className="px-4 py-3 text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800 max-w-[160px] truncate" title={payment.category_name}>
                                                        {payment.category_name}
                                                    </td>
                                                    {/* Concepto */}
                                                    <td className="px-4 py-3 font-medium border-r border-gray-100 dark:border-gray-800 max-w-[200px] truncate" title={payment.concept_name}>
                                                        {payment.concept_name}
                                                    </td>
                                                    {/* Descripción */}
                                                    <td className="px-4 py-3 text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800 max-w-[200px] truncate uppercase" title={payment.description ?? undefined}>
                                                        {payment.description ? payment.description : <span className="text-gray-400">—</span>}
                                                    </td>
                                                    {/* 7. Proveedor */}
                                                    <td className="px-4 py-3 text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800 max-w-[180px] truncate" title={payment.provider}>
                                                        {payment.provider}
                                                    </td>
                                                    {canSeeAllDepartments && historyDepartmentFilter === 'all' && (
                                                        <td className="px-4 py-3 text-xs text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800">
                                                            {payment.department_name}
                                                        </td>
                                                    )}
                                                    {/* 8. Monto Solicitado */}
                                                    <td className="px-4 py-3 text-right font-mono text-sm border-r border-gray-100 dark:border-gray-800">
                                                        {formatNative(payment.total, payment.currency_id)}
                                                        {nativeNoteOf(payment.total, payment.currency_id) && (
                                                            <div className="text-[10px] text-gray-400">orig. {nativeNoteOf(payment.total, payment.currency_id)}</div>
                                                        )}
                                                    </td>
                                                    {/* 9. Monto Aprobado */}
                                                    <td className="px-4 py-3 text-right font-mono text-sm border-r border-gray-100 dark:border-gray-800">
                                                        {payment.approved_amount !== null ? (
                                                            <>
                                                                {formatNative(payment.approved_amount, payment.currency_id)}
                                                                {nativeNoteOf(payment.approved_amount, payment.currency_id) && (
                                                                    <div className="text-[10px] text-gray-400">orig. {nativeNoteOf(payment.approved_amount, payment.currency_id)}</div>
                                                                )}
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
                                                    {/* 10. Documentos */}
                                                    <td className="px-4 py-3 text-center border-r border-gray-100 dark:border-gray-800">
                                                        {payment.documents_count > 0 ? (
                                                            <span className="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-medium text-blue-700 dark:bg-blue-950 dark:text-blue-300" title={`${payment.documents_count} documento(s)`}>
                                                                <FileText className="h-3 w-3" />
                                                                {payment.documents_count}
                                                            </span>
                                                        ) : (
                                                            <span className="text-gray-300">—</span>
                                                        )}
                                                    </td>
                                                    {/* 11. Solicitante */}
                                                    <td className="px-4 py-3 text-xs text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800 max-w-[140px] truncate" title={payment.user_name}>
                                                        {payment.user_name}
                                                    </td>
                                                    {/* 12. Fecha Solicitud */}
                                                    <td className="px-4 py-3 text-xs text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800 whitespace-nowrap">
                                                        {payment.created_at ? payment.created_at.slice(0, 10) : '—'}
                                                    </td>
                                                    {/* Acciones */}
                                                    <td className="px-4 py-3 text-right">
                                                        <div className="inline-flex items-center justify-end gap-1">
                                                            {isSuperAdmin && (
                                                                <Button
                                                                    size="sm"
                                                                    variant="ghost"
                                                                    title="Editar fecha de programación (Super Admin)"
                                                                    onClick={() => setEditDateState({ uuid: payment.uuid, folio: payment.folio_number, date: payment.payment_provision_date ?? '' })}
                                                                >
                                                                    <Pencil className="h-3.5 w-3.5" />
                                                                </Button>
                                                            )}
                                                            <Button
                                                                size="sm"
                                                                variant="ghost"
                                                                title="Ver detalle"
                                                                onClick={() => setHistoryDetailUuid(payment.uuid)}
                                                            >
                                                                <Eye className="h-3.5 w-3.5" />
                                                            </Button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                {/* Selector por página + Paginación */}
                                <div className="mt-4 flex flex-wrap items-center justify-between gap-2">
                                    <div className="flex items-center gap-3 text-xs text-muted-foreground">
                                        <div className="flex items-center gap-1.5">
                                            <Select value={String(historyPerPage)} onValueChange={(v) => { setHistoryPerPage(Number(v)); setHistoryPage(1); }}>
                                                <SelectTrigger className="h-7 w-[70px] text-xs"><SelectValue /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="10">10</SelectItem>
                                                    <SelectItem value="25">25</SelectItem>
                                                    <SelectItem value="50">50</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <span>por página</span>
                                        </div>
                                        <div>
                                            Mostrando {(historyCurrentPage - 1) * historyPerPage + 1}-{Math.min(historyCurrentPage * historyPerPage, filteredHistory.length)} de {filteredHistory.length} pagos
                                        </div>
                                    </div>
                                    <NumberedPagination
                                        currentPage={historyCurrentPage}
                                        totalPages={historyTotalPages}
                                        onPageChange={setHistoryPage}
                                    />
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

                                {/* Reemplazar documentos (solo superadmin, pagos completados) */}
                                {isSuperAdmin && selectedHistoryPayment.status === 'completed' && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="w-full"
                                        onClick={() => {
                                            const target = selectedHistoryPayment;
                                            setHistoryDetailUuid(null);
                                            openUploadDialog(target.uuid, target.payment_type);
                                        }}
                                    >
                                        <Upload className="mr-1.5 h-3.5 w-3.5" />
                                        Reemplazar documentos
                                    </Button>
                                )}

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
                                            <dd className="font-medium">{investmentPaymentTypeLabel(selectedHistoryPayment.payment_type)}</dd>
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
                                            <dd className="font-mono">{formatNative(selectedHistoryPayment.subtotal, selectedHistoryPayment.currency_id)}</dd>
                                        </div>
                                        <div className="flex justify-between">
                                            <dt className="text-muted-foreground">IVA</dt>
                                            <dd className="font-mono">{formatNative(selectedHistoryPayment.iva, selectedHistoryPayment.currency_id)}</dd>
                                        </div>
                                        <div className="flex justify-between border-t pt-2">
                                            <dt className="font-semibold">Total solicitado</dt>
                                            <dd className="text-right font-mono font-semibold">
                                                {formatNative(selectedHistoryPayment.total, selectedHistoryPayment.currency_id)}
                                                {nativeNoteOf(selectedHistoryPayment.total, selectedHistoryPayment.currency_id) && (
                                                    <div className="text-[10px] font-normal text-gray-400">orig. {nativeNoteOf(selectedHistoryPayment.total, selectedHistoryPayment.currency_id)}</div>
                                                )}
                                            </dd>
                                        </div>
                                        {selectedHistoryPayment.approved_amount !== null && (
                                            <div className="flex justify-between">
                                                <dt className="font-semibold text-amber-700 dark:text-amber-400">
                                                    Monto aprobado por PM
                                                </dt>
                                                <dd className="font-mono font-semibold text-amber-700 dark:text-amber-400">
                                                    {formatNative(selectedHistoryPayment.approved_amount, selectedHistoryPayment.currency_id)}
                                                    {selectedHistoryPayment.was_adjusted && (
                                                        <div className="text-[10px] font-normal text-amber-600 dark:text-amber-500">
                                                            ajustado desde {formatNative(selectedHistoryPayment.total, selectedHistoryPayment.currency_id)}
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

            {/* Edit inline dialog (concepto + descripción) */}
            {/* Editar fecha de programación (Super Admin) — recalcula la semana */}
            <Dialog open={editDateState !== null} onOpenChange={(open) => { if (!open && !editDateSaving) setEditDateState(null); }}>
                <DialogContent className="sm:max-w-md">
                    {editDateState && (() => {
                        const iso = isoWeekOf(editDateState.date);
                        return (
                            <>
                                <DialogHeader>
                                    <DialogTitle>Editar fecha de programación — #{String(editDateState.folio).padStart(5, '0')}</DialogTitle>
                                    <DialogDescription>
                                        Corrección de Super Admin. Al guardar, la semana de provisión se recalcula y el pago se mueve a esa semana.
                                    </DialogDescription>
                                </DialogHeader>
                                <div className="space-y-3 py-2">
                                    <div className="space-y-1.5">
                                        <Label htmlFor="edit_provision_date">Fecha de programación de pago</Label>
                                        <Input
                                            id="edit_provision_date"
                                            type="date"
                                            value={editDateState.date}
                                            onChange={(e) => setEditDateState((prev) => (prev ? { ...prev, date: e.target.value } : prev))}
                                        />
                                    </div>
                                    <div className="rounded-md border bg-muted/40 px-3 py-2 text-sm">
                                        {iso ? (
                                            <span>Quedará en <span className="font-semibold">Semana {iso.week} / {iso.year}</span></span>
                                        ) : (
                                            <span className="text-muted-foreground">Selecciona una fecha válida.</span>
                                        )}
                                    </div>
                                </div>
                                <DialogFooter>
                                    <Button type="button" variant="outline" onClick={() => setEditDateState(null)} disabled={editDateSaving}>
                                        Cancelar
                                    </Button>
                                    <Button
                                        type="button"
                                        disabled={editDateSaving || !editDateState.date}
                                        onClick={() => {
                                            setEditDateSaving(true);
                                            router.patch(
                                                `/investment-payment-requests/${editDateState.uuid}/provision-date`,
                                                { payment_provision_date: editDateState.date },
                                                {
                                                    preserveScroll: true,
                                                    onSuccess: () => setEditDateState(null),
                                                    onFinish: () => setEditDateSaving(false),
                                                },
                                            );
                                        }}
                                    >
                                        {editDateSaving ? 'Guardando...' : 'Guardar'}
                                    </Button>
                                </DialogFooter>
                            </>
                        );
                    })()}
                </DialogContent>
            </Dialog>

            <Dialog open={editDescState !== null} onOpenChange={(open) => { if (!open) closeEditDescription(); }}>
                <DialogContent className="sm:max-w-lg">
                    <TooltipProvider delayDuration={300}>
                    <DialogHeader>
                        <DialogTitle>Editar Solicitud — #{editDescState ? String(editDescState.folio).padStart(5, '0') : ''}</DialogTitle>
                        <DialogDescription>
                            Actualiza el concepto de inversión y/o la descripción.
                        </DialogDescription>
                    </DialogHeader>
                    {(() => {
                        const targetDeptId = editDescState?.departmentId ?? null;
                        const targetDeptName = editDescState?.departmentName ?? null;
                        const snapshot = editDescState?.currentConceptSnapshot ?? null;
                        const filteredConcepts = targetDeptId !== null
                            ? availableConcepts.filter((c) => (conceptDepartmentMap[c.investment_expense_category_id] ?? []).includes(targetDeptId))
                            : availableConcepts;
                        const inFiltered = snapshot ? filteredConcepts.some((c) => c.id === snapshot.id) : false;
                        const inAvailable = snapshot ? availableConcepts.some((c) => c.id === snapshot.id) : false;
                        const showCurrentOutOfDept = snapshot && !inFiltered && inAvailable;
                        const showCurrentInactive = snapshot && !inAvailable;
                        const conceptLabel = (c: PageProps['availableConcepts'][number]) =>
                            c.category?.name ? `${c.category.name} - ${c.name}` : c.name;
                        const snapshotLabel = snapshot
                            ? (snapshot.categoryName ? `${snapshot.categoryName} - ${snapshot.name}` : snapshot.name)
                            : '';
                        // Texto del botón del Combobox
                        const currentInList = availableConcepts.find((c) => String(c.id) === editConceptId);
                        const selectedLabel = currentInList
                            ? conceptLabel(currentInList)
                            : (snapshot && String(snapshot.id) === editConceptId ? snapshotLabel : '');
                        return (
                    <div className="space-y-4 py-2 min-w-0">
                        <div className="space-y-2 min-w-0">
                            <Label htmlFor="edit_concept">Concepto de Inversión</Label>
                            <Tooltip>
                                <Popover open={editConceptOpen} onOpenChange={setEditConceptOpen}>
                                    <TooltipTrigger asChild>
                                        <PopoverTrigger asChild>
                                            <Button
                                                id="edit_concept"
                                                type="button"
                                                variant="outline"
                                                role="combobox"
                                                aria-expanded={editConceptOpen}
                                                className="w-full max-w-full min-w-0 justify-between overflow-hidden font-normal"
                                            >
                                                <span className="min-w-0 flex-1 truncate text-left">
                                                    {selectedLabel || <span className="text-muted-foreground">Selecciona un concepto</span>}
                                                </span>
                                                <ChevronsUpDownIcon className="ml-2 size-4 shrink-0 opacity-50" />
                                            </Button>
                                        </PopoverTrigger>
                                    </TooltipTrigger>
                                <PopoverContent
                                    className="p-0 overflow-hidden z-[60]"
                                    style={{
                                        width: 'var(--radix-popover-trigger-width)',
                                        maxWidth: 'var(--radix-popover-trigger-width)',
                                    }}
                                    align="start"
                                >
                                    <Command>
                                        <CommandInput placeholder="Buscar concepto..." />
                                        <CommandList>
                                                <CommandEmpty>Sin resultados.</CommandEmpty>
                                                <CommandGroup>
                                                    {showCurrentOutOfDept && snapshot && (
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <CommandItem
                                                                    key={`current-out-${snapshot.id}`}
                                                                    value={`actual fuera dpto ${snapshotLabel}`}
                                                                    onSelect={() => { setEditConceptId(String(snapshot.id)); setEditConceptOpen(false); }}
                                                                    className="w-full min-w-0"
                                                                >
                                                                    <CheckIcon className={cn('mr-2 size-4 shrink-0', editConceptId === String(snapshot.id) ? 'opacity-100' : 'opacity-0')} />
                                                                    <span className="min-w-0 flex-1 truncate">⚠️ [Actual, fuera de dpto] {snapshotLabel}</span>
                                                                </CommandItem>
                                                            </TooltipTrigger>
                                                            <TooltipContent side="right" className="z-[70] max-w-sm whitespace-normal break-words text-xs leading-snug">
                                                                ⚠️ [Actual, fuera de dpto] {snapshotLabel}
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {showCurrentInactive && snapshot && (
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <CommandItem
                                                                    key={`current-inactive-${snapshot.id}`}
                                                                    value={`actual inactivo ${snapshotLabel}`}
                                                                    onSelect={() => { setEditConceptId(String(snapshot.id)); setEditConceptOpen(false); }}
                                                                    className="w-full min-w-0"
                                                                >
                                                                    <CheckIcon className={cn('mr-2 size-4 shrink-0', editConceptId === String(snapshot.id) ? 'opacity-100' : 'opacity-0')} />
                                                                    <span className="min-w-0 flex-1 truncate">⚠️ [Actual, inactivo] {snapshotLabel}</span>
                                                                </CommandItem>
                                                            </TooltipTrigger>
                                                            <TooltipContent side="right" className="z-[70] max-w-sm whitespace-normal break-words text-xs leading-snug">
                                                                ⚠️ [Actual, inactivo] {snapshotLabel}
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )}
                                                    {filteredConcepts.map((c) => (
                                                        <Tooltip key={c.id}>
                                                            <TooltipTrigger asChild>
                                                                <CommandItem
                                                                    value={conceptLabel(c)}
                                                                    onSelect={() => { setEditConceptId(String(c.id)); setEditConceptOpen(false); }}
                                                                    className="w-full min-w-0"
                                                                >
                                                                    <CheckIcon className={cn('mr-2 size-4 shrink-0', editConceptId === String(c.id) ? 'opacity-100' : 'opacity-0')} />
                                                                    <span className="min-w-0 flex-1 truncate">{conceptLabel(c)}</span>
                                                                </CommandItem>
                                                            </TooltipTrigger>
                                                            <TooltipContent side="right" className="z-[70] max-w-sm whitespace-normal break-words text-xs leading-snug">
                                                                {conceptLabel(c)}
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    ))}
                                                </CommandGroup>
                                        </CommandList>
                                    </Command>
                                    </PopoverContent>
                                </Popover>
                                {selectedLabel && (
                                    <TooltipContent side="bottom" align="start" className="max-w-sm whitespace-normal break-words text-xs leading-snug">
                                        {selectedLabel}
                                    </TooltipContent>
                                )}
                            </Tooltip>
                            <div className="flex items-center gap-1 text-[11px] text-muted-foreground">
                                <span>
                                    Solo conceptos de <span className="font-medium">{targetDeptName ?? 'tu departamento'}</span>.
                                </span>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <button type="button" className="inline-flex items-center gap-0.5 text-primary hover:underline">
                                            <Info className="size-3" /> Ver más
                                        </button>
                                    </TooltipTrigger>
                                    <TooltipContent side="bottom" align="start" className="max-w-sm whitespace-normal break-words text-xs leading-snug">
                                        Si el concepto actual aparece como <em>fuera de dpto</em> o <em>inactivo</em>, puedes mantenerlo o elegir uno nuevo. ¿Necesitas uno de otro departamento?{' '}
                                        <a href="/admin/investment-expense-categories" target="_blank" rel="noopener noreferrer" className="text-primary underline">
                                            Agrega el departamento a la categoría
                                        </a>.
                                    </TooltipContent>
                                </Tooltip>
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="edit_description">Descripción <span className="text-gray-400">(opcional)</span></Label>
                            <textarea
                                id="edit_description"
                                className="border-input focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border bg-transparent px-3 py-2 text-sm uppercase shadow-xs focus-visible:ring-[3px] focus-visible:outline-none"
                                rows={4}
                                value={editDescValue}
                                onChange={(e) => setEditDescValue(e.target.value.toUpperCase())}
                                placeholder="Descripción del concepto..."
                            />
                        </div>
                    </div>
                        );
                    })()}
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={closeEditDescription} disabled={editDescSaving}>
                            Cancelar
                        </Button>
                        <Button type="button" onClick={handleSaveDescription} disabled={editDescSaving}>
                            {editDescSaving ? 'Guardando...' : 'Guardar'}
                        </Button>
                    </DialogFooter>
                    </TooltipProvider>
                </DialogContent>
            </Dialog>

            {/* Upload documents dialog */}
            <Dialog open={uploadDialogUuid !== null} onOpenChange={(open) => { if (!open) closeUploadDialog(); }}>
                <DialogContent className="sm:max-w-3xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Subir documentos del pago</DialogTitle>
                        <DialogDescription>
                            {uploadType === 'factura'
                                ? 'Adjunta el PDF y el XML de la factura. Ambos son obligatorios (máximo 10 MB cada uno).'
                                : 'Adjunta un documento (PDF o imagen, máximo 10 MB).'}
                        </DialogDescription>
                    </DialogHeader>
                    {(() => {
                        const target = uploadDialogUuid
                            ? (authorizedPayments?.payments.find((p) => p.uuid === uploadDialogUuid)
                                ?? userPaymentHistory.find((p) => p.uuid === uploadDialogUuid))
                            : null;
                        if (!target?.documents || target.documents.length === 0) {
                            return null;
                        }
                        return (
                            <div className="space-y-2 pt-2">
                                <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    Documentos actuales — {target.documents.length}
                                </p>
                                <DocumentPreview documents={target.documents} />
                                <p className="text-[11px] text-muted-foreground">
                                    Si los documentos están equivocados, carga nuevos abajo para reemplazarlos.
                                </p>
                            </div>
                        );
                    })()}
                    <div className="space-y-2 pt-2">
                        <Label>Tipo de pago</Label>
                        <div className="flex flex-wrap gap-2">
                            {UPLOAD_TYPES.map((type) => (
                                <Button
                                    key={type}
                                    type="button"
                                    size="sm"
                                    variant={uploadType === type ? 'default' : 'outline'}
                                    onClick={() => changeUploadType(type)}
                                    disabled={uploading}
                                >
                                    {investmentPaymentTypeLabel(type)}
                                </Button>
                            ))}
                        </div>
                    </div>
                    {uploadType === 'factura' ? (
                        <div className="grid gap-4 pt-2 sm:grid-cols-2">
                            <div className="space-y-2">
                                <Label>Factura PDF <span className="text-red-500">*</span></Label>
                                <FileUpload
                                    files={uploadPdf ? [uploadPdf] : []}
                                    onChange={(f) => setUploadPdf(f[0] ?? null)}
                                    maxFiles={1}
                                    accept=".pdf"
                                />
                                <InputError message={uploadErrors.pdf} />
                            </div>
                            <div className="space-y-2">
                                <Label>Factura XML <span className="text-red-500">*</span></Label>
                                <FileUpload
                                    files={uploadXml ? [uploadXml] : []}
                                    onChange={(f) => setUploadXml(f[0] ?? null)}
                                    maxFiles={1}
                                    accept=".xml"
                                />
                                <InputError message={uploadErrors.xml} />
                            </div>
                        </div>
                    ) : (
                        <div className="space-y-2 pt-2">
                            <Label>Documento <span className="text-red-500">*</span></Label>
                            <FileUpload
                                files={uploadDocument ? [uploadDocument] : []}
                                onChange={(f) => setUploadDocument(f[0] ?? null)}
                                maxFiles={1}
                                accept=".pdf,.jpg,.jpeg,.png"
                            />
                            <InputError message={uploadErrors.document} />
                        </div>
                    )}
                    {uploadErrors.payment_type && (
                        <div className="pt-2">
                            <InputError message={uploadErrors.payment_type} />
                        </div>
                    )}
                    <div className="flex justify-end gap-2 pt-4">
                        <Button variant="outline" onClick={closeUploadDialog} disabled={uploading}>
                            Cancelar
                        </Button>
                        <Button
                            onClick={handleUploadDocuments}
                            disabled={!uploadCanSubmit || uploading}
                        >
                            {uploading
                                ? 'Subiendo...'
                                : ((uploadDialogUuid
                                    && (authorizedPayments?.payments.find((p) => p.uuid === uploadDialogUuid)?.documents.length
                                        ?? userPaymentHistory.find((p) => p.uuid === uploadDialogUuid)?.documents.length ?? 0) > 0)
                                    ? 'Reemplazar documentos'
                                    : 'Subir documentos')}
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
                userDepartmentIds={userDepartmentIds}
                onRequestPayment={(ir) => openPaymentModal(ir)}
                paymentPolicy={paymentPolicy}
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
                    paymentPolicy={paymentPolicy}
                />
            )}
        </AppLayout>
    );
}

/* ─── Payments Drawer ─── */

type PaymentsDrawerProps = {
    open: boolean;
    onClose: () => void;
    investmentRequest: InvestmentRequest | null;
    payments: InvestmentPayment[];
    summary: PaymentsSummary | null;
    loading: boolean;
    userDepartmentIds: number[];
    onRequestPayment: (ir: InvestmentRequest) => void;
    paymentPolicy: import('@/types/payment-policy').PaymentPolicyPayload;
};

function PaymentsDrawer({
    open, onClose, investmentRequest: ir, payments, summary, loading, userDepartmentIds, onRequestPayment, paymentPolicy,
}: PaymentsDrawerProps) {
    const { formatCurrency, formatNative, nativeNoteOf } = useCurrencyFormatters();

    if (!ir) return null;

    const isUserDept = !!ir.department?.id && userDepartmentIds.includes(ir.department.id);
    const groupRemaining = Number(ir.group_remaining ?? ir.remaining_balance);
    const canRequestPayment = isUserDept && groupRemaining > 0;

    const groupBudget = Number(ir.group_budget ?? (summary?.total_concept ?? ir.total));
    const groupPaid = Number(ir.group_paid ?? (summary?.total_paid ?? 0));
    const groupCommitted = Number(ir.group_committed ?? (summary?.committed ?? 0));
    const paidPct = groupBudget > 0 ? Math.min(100, (groupPaid / groupBudget) * 100) : 0;
    const committedPct = groupBudget > 0 ? Math.min(100 - paidPct, (groupCommitted / groupBudget) * 100) : 0;
    const progressPercent = paidPct + committedPct;

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
                            <span className="text-right font-mono font-semibold">
                                {formatNative(ir.total, ir.currency?.id)}
                                {nativeNoteOf(ir.total, ir.currency?.id) && (
                                    <div className="text-[10px] font-normal text-gray-400">orig. {nativeNoteOf(ir.total, ir.currency?.id)}</div>
                                )}
                            </span>
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
                                    <span className="text-muted-foreground">Comprometido</span>
                                    <span className="font-mono font-medium text-amber-600 dark:text-amber-400">{formatCurrency(summary.committed)}</span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Pagado</span>
                                    <span className="font-mono font-medium text-blue-600 dark:text-blue-400">{formatCurrency(summary.total_paid)}</span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Saldo disponible</span>
                                    <span className={cn('font-mono font-semibold', Number(ir.group_remaining ?? summary.remaining) > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400')}>
                                        {formatCurrency(ir.group_remaining ?? summary.remaining)}
                                    </span>
                                </div>
                                {/* Barra apilada: pagado + comprometido sobre el presupuesto */}
                                <div className="space-y-1">
                                    <div className="flex h-2.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                        <div className="h-full bg-blue-500 transition-all" style={{ width: `${paidPct}%` }} />
                                        <div className="h-full bg-amber-400 transition-all" style={{ width: `${committedPct}%` }} />
                                    </div>
                                    <p className="text-right text-xs text-muted-foreground">
                                        {progressPercent.toFixed(0)}% consumido · {summary.count} {summary.count === 1 ? 'pago' : 'pagos'}
                                    </p>
                                    <p className="text-right text-[10px] text-muted-foreground/80">
                                        El saldo descuenta lo comprometido (incluye borradores) y lo pagado.
                                    </p>
                                </div>
                            </>
                        )}
                    </div>

                    {/* Action button */}
                    {canRequestPayment && (
                        <TooltipProvider delayDuration={200}>
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <span className="block w-full">
                                        <Button
                                            className="w-full"
                                            onClick={() => onRequestPayment(ir)}
                                            disabled={!paymentPolicy.capture.canAct}
                                        >
                                            <Banknote className="mr-2 h-4 w-4" />
                                            Solicitar Pago
                                        </Button>
                                    </span>
                                </TooltipTrigger>
                                {!paymentPolicy.capture.canAct && (
                                    <TooltipContent side="bottom" className="max-w-sm text-xs">
                                        Ventana de captura cerrada. Próxima apertura: <span className="font-semibold">{formatPolicyTime(paymentPolicy.capture.opensAt)}</span>.
                                    </TooltipContent>
                                )}
                            </Tooltip>
                        </TooltipProvider>
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
                                    return (
                                        <div key={payment.id} className="rounded-lg border p-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                            <div className="flex items-start justify-between">
                                                <div className="min-w-0 flex-1">
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-mono text-xs font-medium">
                                                            #{String(payment.folio_number).padStart(5, '0')}
                                                        </span>
                                                        <Badge variant="outline" className="text-xs">
                                                            {investmentPaymentTypeLabel(payment.payment_type)}
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
                                                    <p className="font-mono text-sm font-semibold">{formatNative(payment.total, payment.currency_id)}</p>
                                                    {nativeNoteOf(payment.total, payment.currency_id) && (
                                                        <div className="text-[10px] font-normal text-gray-400">orig. {nativeNoteOf(payment.total, payment.currency_id)}</div>
                                                    )}
                                                    <div className="mt-1 flex justify-end">
                                                        <span className={cn('inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium', historyStatusColorClass(payment.status))}>
                                                            {historyStatusLabel(payment.status)}
                                                        </span>
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
    paymentPolicy: import('@/types/payment-policy').PaymentPolicyPayload;
};

function PaymentRequestModal({
    open, onClose, investmentRequest: ir,
    currencies, branches, errors,
    editingPayment,
    paymentPolicy,
}: PaymentRequestModalProps) {
    const { formatCurrency } = useCurrencyFormatters();
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
                payment_type: (['factura', 'reembolso', 'estrategia', 'anticipo', 'cotizacion', 'pagare', 'domiciliado'].includes(editingPayment.payment_type) ? editingPayment.payment_type : 'factura') as 'factura' | 'reembolso' | 'estrategia' | 'anticipo' | 'cotizacion' | 'pagare' | 'domiciliado',
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
            payment_provision_date: paymentPolicy.provision.nextDate,
            currency_id: ir.currency?.id ? String(ir.currency.id) : '',
            branch_id: ir.branch?.id ? String(ir.branch.id) : '',
            payment_type: 'factura' as 'factura' | 'reembolso' | 'estrategia' | 'anticipo' | 'cotizacion' | 'pagare' | 'domiciliado',
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

    // Contador regresivo hasta el cierre de la ventana (solo si está activa y el user NO tiene override)
    const remaining = useTimeRemaining(
        paymentPolicy.capture.isWindowActive && !paymentPolicy.isOverride
            ? paymentPolicy.capture.closesAt
            : null
    );
    const warningMin = paymentPolicy.warningMinutesBeforeClose;
    const inWarningZone = remaining !== null && remaining.totalSeconds > 0 && remaining.totalSeconds <= warningMin * 60;

    // Auto-cerrar el modal cuando llega el cierre.
    useEffect(() => {
        if (remaining && remaining.hasPassed && open) {
            onClose();
        }
    }, [remaining?.hasPassed, open, onClose]);

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

    const changePaymentType = (type: 'factura' | 'reembolso' | 'estrategia' | 'anticipo' | 'cotizacion' | 'pagare' | 'domiciliado') => {
        setValues((prev) => ({ ...prev, payment_type: type }));
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

        if (values.payment_type === 'factura') {
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
                    {remaining !== null && !remaining.hasPassed && (
                        <div className={cn(
                            'mt-2 flex items-center gap-2 rounded-md border px-3 py-2 text-xs',
                            inWarningZone
                                ? 'border-red-300 bg-red-50 text-red-900 dark:border-red-800 dark:bg-red-950/30 dark:text-red-100 animate-pulse'
                                : 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-100'
                        )}>
                            <Clock className="size-3.5 shrink-0" />
                            {inWarningZone ? (
                                <span>
                                    <strong>¡Atención!</strong> La ventana cierra en <span className="font-mono font-semibold">{remaining.formatted}</span>. Guarda antes de que se cierre — el modal se cerrará automáticamente al pasar el tiempo.
                                </span>
                            ) : (
                                <span>
                                    Ventana de captura cierra en <span className="font-mono font-semibold">{remaining.formatted}</span> ({formatPolicyTime(paymentPolicy.capture.closesAt)}).
                                </span>
                            )}
                        </div>
                    )}
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
                                        min={paymentPolicy.provision.nextDate}
                                        max={paymentPolicy.provision.nextDate}
                                    />
                                    <p className="text-[11px] text-muted-foreground">
                                        Política operativa: pago el <span className="font-medium">{paymentPolicy.provision.label}</span>.
                                    </p>
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
                                <div className="space-y-2">
                                    <Label>Tipo de pago</Label>
                                    <div className="flex flex-wrap gap-2">
                                        {(['factura', 'reembolso', 'estrategia', 'anticipo', 'cotizacion', 'pagare', 'domiciliado'] as const).map((type) => (
                                            <Button
                                                key={type}
                                                type="button"
                                                size="sm"
                                                variant={values.payment_type === type ? 'default' : 'outline'}
                                                onClick={() => changePaymentType(type)}
                                            >
                                                {investmentPaymentTypeLabel(type)}
                                            </Button>
                                        ))}
                                    </div>
                                </div>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    {values.payment_type === 'factura'
                                        ? 'Adjunta el PDF y XML de la factura.'
                                        : 'Adjunta 1 documento (PDF o imagen).'}
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

                                {values.payment_type === 'factura' ? (
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="space-y-2">
                                            <Label>Factura PDF</Label>
                                            <FileUpload
                                                files={invoicePdf ? [invoicePdf] : []}
                                                onChange={(f) => setInvoicePdf(f[0] ?? null)}
                                                maxFiles={1}
                                                accept=".pdf"
                                                error={errors['invoice_documents'] || errors['invoice_documents.0']}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label>Factura XML</Label>
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
                                        maxFiles={1}
                                        accept=".pdf,.jpg,.jpeg,.png"
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
