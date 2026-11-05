import { Head, router } from '@inertiajs/react';
import { FileDown, FileSpreadsheet, FileText, Inbox, Percent } from 'lucide-react';
import { useMemo, useState } from 'react';
import { DocumentPreview } from '@/components/document-preview';
import { NumberedPagination } from '@/components/numbered-pagination';
import { ProjectCombobox, type Project } from '@/components/project-combobox';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { formatCurrency } from '@/lib/currency';
import { investmentPaymentTypeLabel } from '@/lib/payment-type-labels';
import type { BreadcrumbItem } from '@/types';

type DocumentItem = { name: string; url: string };

type StrategyPayment = {
    uuid: string;
    folio_number: number;
    project_name: string | null;
    department_id: number | null;
    department_name: string | null;
    requester_name: string | null;
    concept_name: string | null;
    provider: string | null;
    strategy_name: string | null;
    strategy_rate: string | null;
    strategy_amount: string | null;
    strategy_surcharge: string | null;
    total: string;
    approved_amount: string | null;
    currency_prefix: string;
    payment_type: string;
    status: string;
    captured_at: string | null;
    capture_week: number | null;
    capture_week_year: number | null;
    payment_provision_date: string | null;
    provision_week: number | null;
    provision_week_year: number | null;
    documents: DocumentItem[];
    receipt_documents: DocumentItem[];
};

type Props = {
    projects: Project[];
    selectedProjectId: number | 'all' | null;
    payments: StrategyPayment[];
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Estrategias', href: '/strategies' }];

const ALL = 'all';

const statusLabels: Record<string, string> = {
    draft: 'Borrador',
    submitted: 'Enviado',
    ceo_approved: 'CEO Aprobó',
    ceo_rejected: 'CEO Rechazó',
    projectmanager_review: 'En revisión PM',
    projectmanager_approved: 'PM Aprobó (histórico)',
    projectmanager_rejected: 'PM Rechazó',
    final_pending: 'Pendiente Final (histórico)',
    final_approved: 'Aprobado Final',
    final_rejected: 'Rechazado Final',
    documents_pending: 'Esperando docs',
    pending_approval: 'Pendiente',
    approved: 'Aprobado',
    rejected: 'Rechazado',
    completed: 'Completado',
    scheduled_for_bank: 'Programado en banco',
    receipt_attached: 'Comprobante adjunto',
    auto_cancelled: 'Cancelado',
};

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    submitted: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    ceo_approved: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    final_approved: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    scheduled_for_bank: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    receipt_attached: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    ceo_rejected: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    projectmanager_rejected: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    final_rejected: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    auto_cancelled: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
};

const statusLabel = (status: string): string => statusLabels[status] ?? status;

const formatDate = (date: string | null): string =>
    date
        ? new Date(`${date}T12:00:00`).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' })
        : '—';

const formatAmount = (amount: string | null, currency: string): string =>
    amount === null ? '—' : `${formatCurrency(amount)} ${currency}`;

const weekKey = (year: number | null, week: number | null): string | null => (year === null || week === null ? null : `${year}-${week}`);

const weekOptions = (weeks: Array<[number | null, number | null]>): Array<{ key: string; label: string }> => {
    const unique = new Map<string, [number, number]>();

    weeks.forEach(([year, week]) => {
        const key = weekKey(year, week);

        if (key !== null && year !== null && week !== null) {
            unique.set(key, [year, week]);
        }
    });

    return [...unique.entries()]
        .sort(([, a], [, b]) => b[0] - a[0] || b[1] - a[1])
        .map(([key, [year, week]]) => ({ key, label: `Semana ${week} (${year})` }));
};

function DocumentsButton({ documents, onPreview }: { documents: DocumentItem[]; onPreview: (documents: DocumentItem[]) => void }) {
    if (documents.length === 0) {
        return <span className="text-muted-foreground">—</span>;
    }

    return (
        <Button variant="outline" size="sm" className="h-7 gap-1 px-2 text-xs" onClick={() => onPreview(documents)}>
            <FileText className="h-3 w-3" />
            Ver ({documents.length})
        </Button>
    );
}

export default function StrategiesIndex({ projects, selectedProjectId, payments }: Props) {
    const [department, setDepartment] = useState(ALL);
    const [status, setStatus] = useState(ALL);
    const [captureWeek, setCaptureWeek] = useState(ALL);
    const [provisionWeek, setProvisionWeek] = useState(ALL);
    const [page, setPage] = useState(1);
    const [perPage, setPerPage] = useState(10);
    const [previewDocs, setPreviewDocs] = useState<DocumentItem[] | null>(null);

    const departments = useMemo(() => {
        const names = new Map<string, string>();

        payments.forEach((p) => {
            if (p.department_id !== null) {
                names.set(String(p.department_id), p.department_name ?? '—');
            }
        });

        return [...names.entries()].sort((a, b) => a[1].localeCompare(b[1]));
    }, [payments]);
    const statuses = useMemo(() => [...new Set(payments.map((p) => p.status))], [payments]);
    const captureWeeks = useMemo(() => weekOptions(payments.map((p) => [p.capture_week_year, p.capture_week])), [payments]);
    const provisionWeeks = useMemo(() => weekOptions(payments.map((p) => [p.provision_week_year, p.provision_week])), [payments]);

    const filtered = useMemo(
        () =>
            payments.filter(
                (p) =>
                    (department === ALL || String(p.department_id) === department) &&
                    (status === ALL || p.status === status) &&
                    (captureWeek === ALL || weekKey(p.capture_week_year, p.capture_week) === captureWeek) &&
                    (provisionWeek === ALL || weekKey(p.provision_week_year, p.provision_week) === provisionWeek),
            ),
        [payments, department, status, captureWeek, provisionWeek],
    );

    const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
    const currentPage = Math.min(page, totalPages);
    const pageStart = (currentPage - 1) * perPage;
    const visible = filtered.slice(pageStart, pageStart + perPage);

    const reportUrl = (format: 'pdf' | 'excel'): string => {
        const params = new URLSearchParams({ project_id: String(selectedProjectId) });

        if (department !== ALL) {
            params.set('department_id', department);
        }

        if (status !== ALL) {
            params.set('status', status);
        }

        if (captureWeek !== ALL) {
            params.set('capture_week', captureWeek);
        }

        if (provisionWeek !== ALL) {
            params.set('provision_week', provisionWeek);
        }

        return `/strategies/${format}?${params.toString()}`;
    };

    const changeDepartment = (value: string) => {
        setDepartment(value);
        setPage(1);
    };

    const changeStatus = (value: string) => {
        setStatus(value);
        setPage(1);
    };

    const changeCaptureWeek = (value: string) => {
        setCaptureWeek(value);
        setPage(1);
    };

    const changeProvisionWeek = (value: string) => {
        setProvisionWeek(value);
        setPage(1);
    };

    const changePerPage = (value: string) => {
        setPerPage(Number(value));
        setPage(1);
    };

    const selectProject = (id: number | 'all') => {
        setDepartment(ALL);
        setStatus(ALL);
        setCaptureWeek(ALL);
        setProvisionWeek(ALL);
        setPage(1);
        router.visit(`/strategies?project_id=${id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Estrategias" />

            <div className="w-full space-y-6 p-4 lg:p-6">
                <Card>
                    <CardContent className="pt-6">
                        <Label className="mb-2 block">Hoja de Inversión</Label>
                        <ProjectCombobox
                            projects={projects}
                            selectedId={typeof selectedProjectId === 'number' ? selectedProjectId : null}
                            onSelect={selectProject}
                            allOption={{ label: 'Todas', selected: selectedProjectId === ALL, onSelect: () => selectProject(ALL) }}
                        />
                    </CardContent>
                </Card>

                {selectedProjectId === null ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-2 py-12 text-center text-muted-foreground">
                            <Percent className="h-10 w-10 opacity-40" />
                            <p>Selecciona una Hoja de Inversión para ver sus estrategias.</p>
                        </CardContent>
                    </Card>
                ) : payments.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-2 py-12 text-center text-muted-foreground">
                            <Inbox className="h-10 w-10 opacity-40" />
                            <p>{selectedProjectId === ALL ? 'No hay estrategias registradas.' : 'Esta Hoja de Inversión no tiene estrategias.'}</p>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base">
                                Estrategias solicitadas ({filtered.length} de {payments.length})
                            </CardTitle>
                            <div className="flex flex-wrap items-end gap-3 pt-2">
                                <div className="w-full space-y-1 sm:w-48">
                                    <Label className="text-xs">Departamento</Label>
                                    <Select value={department} onValueChange={changeDepartment}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={ALL}>Todos</SelectItem>
                                            {departments.map(([id, name]) => (
                                                <SelectItem key={id} value={id}>
                                                    {name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="w-full space-y-1 sm:w-48">
                                    <Label className="text-xs">Estado</Label>
                                    <Select value={status} onValueChange={changeStatus}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={ALL}>Todos</SelectItem>
                                            {statuses.map((value) => (
                                                <SelectItem key={value} value={value}>
                                                    {statusLabel(value)}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="w-full space-y-1 sm:w-48">
                                    <Label className="text-xs">Semana Captura</Label>
                                    <Select value={captureWeek} onValueChange={changeCaptureWeek}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={ALL}>Todas</SelectItem>
                                            {captureWeeks.map(({ key, label }) => (
                                                <SelectItem key={key} value={key}>
                                                    {label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="w-full space-y-1 sm:w-48">
                                    <Label className="text-xs">Semana Programación</Label>
                                    <Select value={provisionWeek} onValueChange={changeProvisionWeek}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value={ALL}>Todas</SelectItem>
                                            {provisionWeeks.map(({ key, label }) => (
                                                <SelectItem key={key} value={key}>
                                                    {label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="flex gap-2 sm:ml-auto">
                                    <Button asChild variant="outline" size="sm" title="Descargar PDF con los filtros aplicados">
                                        <a href={reportUrl('pdf')} target="_blank" rel="noopener noreferrer">
                                            <FileDown className="mr-1 h-4 w-4" /> PDF
                                        </a>
                                    </Button>
                                    <Button asChild variant="outline" size="sm" title="Descargar Excel con los filtros aplicados">
                                        <a href={reportUrl('excel')} target="_blank" rel="noopener noreferrer">
                                            <FileSpreadsheet className="mr-1 h-4 w-4" /> Excel
                                        </a>
                                    </Button>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-gray-500 dark:text-gray-400">
                                            <th className="whitespace-nowrap pb-3 pr-4 font-medium">Folio</th>
                                            <th className="whitespace-nowrap pb-3 pr-4 font-medium">Hoja de Inversión</th>
                                            <th className="whitespace-nowrap pb-3 pr-4 font-medium">Departamento / Solicitante</th>
                                            <th className="whitespace-nowrap pb-3 pr-4 font-medium">Concepto / Proveedor</th>
                                            <th className="whitespace-nowrap pb-3 pr-4 font-medium">Código y tasa</th>
                                            <th className="whitespace-nowrap pb-3 pr-4 text-right font-medium">Monto base / Recargo</th>
                                            <th className="whitespace-nowrap pb-3 pr-4 text-right font-medium">Total / Aprobado</th>
                                            <th className="whitespace-nowrap pb-3 pr-4 font-medium">Tipo</th>
                                            <th className="whitespace-nowrap pb-3 pr-4 font-medium">Estado</th>
                                            <th className="whitespace-nowrap pb-3 pr-4 font-medium">Fecha Captura</th>
                                            <th className="whitespace-nowrap pb-3 pr-4 font-medium">Semana Captura</th>
                                            <th className="whitespace-nowrap pb-3 pr-4 font-medium">Fecha Programación</th>
                                            <th className="whitespace-nowrap pb-3 pr-4 font-medium">Semana Programación</th>
                                            <th className="whitespace-nowrap pb-3 pr-4 font-medium">Documentos</th>
                                            <th className="whitespace-nowrap pb-3 font-medium">Comprobante</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {visible.map((payment) => (
                                            <tr key={payment.uuid} className="border-b align-top last:border-0">
                                                <td className="whitespace-nowrap py-3 pr-4 font-mono text-xs font-semibold">
                                                    #{String(payment.folio_number).padStart(5, '0')}
                                                </td>
                                                <td className="min-w-40 py-3 pr-4 font-medium">{payment.project_name ?? '—'}</td>
                                                <td className="py-3 pr-4">
                                                    <p className="font-medium">{payment.department_name ?? '—'}</p>
                                                    <p className="text-xs text-muted-foreground">{payment.requester_name ?? '—'}</p>
                                                </td>
                                                <td className="min-w-48 py-3 pr-4">
                                                    <p className="font-medium">{payment.concept_name ?? '—'}</p>
                                                    <p className="text-xs text-muted-foreground">{payment.provider ?? '—'}</p>
                                                </td>
                                                <td className="whitespace-nowrap py-3 pr-4">
                                                    {payment.strategy_rate === null ? (
                                                        <span className="text-xs text-muted-foreground">Anterior al catálogo</span>
                                                    ) : (
                                                        <>
                                                            <p className="font-medium">{payment.strategy_name ?? '—'}</p>
                                                            <p className="text-xs text-muted-foreground">{payment.strategy_rate}%</p>
                                                        </>
                                                    )}
                                                </td>
                                                <td className="whitespace-nowrap py-3 pr-4 text-right">
                                                    <p>{formatAmount(payment.strategy_amount, payment.currency_prefix)}</p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {formatAmount(payment.strategy_surcharge, payment.currency_prefix)}
                                                    </p>
                                                </td>
                                                <td className="whitespace-nowrap py-3 pr-4 text-right">
                                                    <p className="font-semibold">{formatAmount(payment.total, payment.currency_prefix)}</p>
                                                    {payment.approved_amount !== null && (
                                                        <p className="text-xs text-muted-foreground">
                                                            Aprobado: {formatAmount(payment.approved_amount, payment.currency_prefix)}
                                                        </p>
                                                    )}
                                                </td>
                                                <td className="whitespace-nowrap py-3 pr-4">
                                                    {payment.payment_type === 'estrategia' ? (
                                                        investmentPaymentTypeLabel(payment.payment_type)
                                                    ) : (
                                                        <Badge variant="outline" className="text-xs">
                                                            Tipo actual: {investmentPaymentTypeLabel(payment.payment_type)}
                                                        </Badge>
                                                    )}
                                                </td>
                                                <td className="whitespace-nowrap py-3 pr-4">
                                                    <Badge
                                                        className={`text-xs ${statusColors[payment.status] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'}`}
                                                    >
                                                        {statusLabel(payment.status)}
                                                    </Badge>
                                                </td>
                                                <td className="whitespace-nowrap py-3 pr-4">{formatDate(payment.captured_at)}</td>
                                                <td className="whitespace-nowrap py-3 pr-4">{payment.capture_week ?? '—'}</td>
                                                <td className="whitespace-nowrap py-3 pr-4">{formatDate(payment.payment_provision_date)}</td>
                                                <td className="whitespace-nowrap py-3 pr-4">{payment.provision_week ?? '—'}</td>
                                                <td className="py-3 pr-4">
                                                    <DocumentsButton documents={payment.documents} onPreview={setPreviewDocs} />
                                                </td>
                                                <td className="py-3">
                                                    <DocumentsButton documents={payment.receipt_documents} onPreview={setPreviewDocs} />
                                                </td>
                                            </tr>
                                        ))}
                                        {filtered.length === 0 && (
                                            <tr>
                                                <td colSpan={15} className="py-8 text-center text-muted-foreground">
                                                    No hay estrategias con estos filtros.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            {filtered.length > 0 && (
                                <div className="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500 dark:text-gray-400">
                                    <div className="flex items-center gap-3">
                                        <div className="flex items-center gap-1.5">
                                            <Select value={String(perPage)} onValueChange={changePerPage}>
                                                <SelectTrigger className="h-7 w-[70px] text-xs">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="10">10</SelectItem>
                                                    <SelectItem value="25">25</SelectItem>
                                                    <SelectItem value="50">50</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <span>por página</span>
                                        </div>
                                        <div>
                                            Mostrando {pageStart + 1}-{Math.min(pageStart + perPage, filtered.length)} de {filtered.length}
                                        </div>
                                    </div>
                                    <NumberedPagination currentPage={currentPage} totalPages={totalPages} onPageChange={setPage} />
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>

            <Dialog
                open={previewDocs !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setPreviewDocs(null);
                    }
                }}
            >
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle>Documentos del pago</DialogTitle>
                        <DialogDescription>Vista previa de los archivos adjuntos.</DialogDescription>
                    </DialogHeader>
                    {previewDocs && <DocumentPreview documents={previewDocs} />}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
