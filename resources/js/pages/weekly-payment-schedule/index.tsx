import { Head, router } from '@inertiajs/react';
import { CheckCircle2, FileDown, FileText, Image as ImageIcon, Inbox, Paperclip, Send, Upload } from 'lucide-react';
import { useMemo, useState } from 'react';
import { DocumentPreview } from '@/components/document-preview';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ProjectCombobox, type Project } from '@/components/project-combobox';
import { WeekNavigator } from '@/components/week-navigator';
import { formatCurrency } from '@/lib/currency';
import { investmentPaymentTypeLabel } from '@/lib/payment-type-labels';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Payment {
    id: number;
    uuid: string;
    folio_number: number;
    provider: string;
    concept_name: string;
    project_name: string;
    payment_provision_date: string;
    payment_week_number: number;
    total: string;
    currency_prefix: string;
    description: string | null;
    payment_type: string;
    status: string;
    documents: { name: string; url: string }[];
    receipt_documents: { name: string; url: string }[];
    receipt_uploaded_at: string | null;
}

interface SchedulePayment {
    uuid: string;
    folio_number: number;
    provider: string;
    concept_name: string;
    payment_provision_date: string | null;
    total: string;
    currency_prefix: string;
    description: string | null;
    payment_type: string;
    status: string;
    included: boolean;
    exclusion_reason: string | null;
    documents: { name: string; url: string }[];
    receipt_documents: { name: string; url: string }[];
    receipt_uploaded_at: string | null;
}

type DocumentItem = { name: string; url: string };
type DocKind = 'pdf' | 'xml' | 'img' | 'other';

function classifyDocs(docs: DocumentItem[]): Array<DocumentItem & { kind: DocKind }> {
    return docs.map((d) => {
        const ext = (d.name.split('.').pop() ?? '').toLowerCase();
        if (ext === 'pdf') return { ...d, kind: 'pdf' };
        if (ext === 'xml') return { ...d, kind: 'xml' };
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) return { ...d, kind: 'img' };
        return { ...d, kind: 'other' };
    });
}

interface Schedule {
    id: number;
    uuid: string;
    week_number: number;
    year: number;
    status: string;
    creator_name: string;
    created_at: string;
    items_count: number;
    included_count: number;
    total_amount: number;
    approval_status: string;
    payments: SchedulePayment[];
}

interface Props {
    payments: Payment[];
    schedules: Schedule[];
    projects: Project[];
    selectedProjectId: number | null;
    currentWeek: number;
    currentYear: number;
}

interface ItemState {
    included: boolean;
    exclusion_reason: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Programación de Pagos Semanal', href: '/weekly-payment-schedule' },
];

const statusLabels: Record<string, string> = {
    draft: 'Borrador',
    pending_approval: 'Pendiente de Autorización',
    approved: 'Autorizado',
    rejected: 'Rechazado',
};

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    pending_approval: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    approved: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    rejected: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
};

const paymentStatusLabels: Record<string, string> = {
    approved: 'Aprobado',
    completed: 'Completado',
    scheduled_for_bank: 'Programado en banco',
    receipt_attached: 'Comprobante adjunto',
};

const paymentStatusColors: Record<string, string> = {
    approved: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    completed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    scheduled_for_bank: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
    receipt_attached: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
};

function DocumentChips({ documents, onPreview }: { documents: DocumentItem[]; onPreview: (docs: DocumentItem[]) => void }) {
    if (documents.length === 0) return null;
    return (
        <button
            type="button"
            className="inline-flex items-center gap-1"
            onClick={() => onPreview(documents)}
            title="Previsualizar documentos"
        >
            {classifyDocs(documents).map((d, i) => {
                const Icon = d.kind === 'img' ? ImageIcon : FileText;
                const label = d.kind === 'pdf' ? 'PDF' : d.kind === 'xml' ? 'XML' : d.kind === 'img' ? 'IMG' : 'DOC';
                return (
                    <span
                        key={i}
                        className="inline-flex items-center gap-1 rounded border px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground hover:bg-accent"
                    >
                        <Icon className="h-3 w-3" /> {label}
                    </span>
                );
            })}
        </button>
    );
}

export default function WeeklyPaymentScheduleIndex({ payments, schedules, projects, selectedProjectId, currentWeek, currentYear }: Props) {
    const [selectedWeek, setSelectedWeek] = useState(currentWeek);
    const [selectedYear, setSelectedYear] = useState(currentYear);
    const [processing, setProcessing] = useState(false);
    const [previewDocs, setPreviewDocs] = useState<DocumentItem[] | null>(null);

    // Diálogo de exportación a Excel
    const [exportOpen, setExportOpen] = useState(false);
    const [exportScope, setExportScope] = useState<'week' | 'all'>('week');

    // Diálogo de carga de comprobante de pago
    const [receiptTarget, setReceiptTarget] = useState<{ uuid: string; folio: number; hasExisting: boolean } | null>(null);
    const [receiptFiles, setReceiptFiles] = useState<File[]>([]);
    const [receiptUploading, setReceiptUploading] = useState(false);
    const [receiptErrors, setReceiptErrors] = useState<Record<string, string>>({});

    const selectedProject = useMemo(
        () => projects.find((p) => p.id === selectedProjectId) ?? null,
        [projects, selectedProjectId],
    );

    const weekPayments = useMemo(
        () => payments.filter((p) => p.payment_week_number === selectedWeek),
        [payments, selectedWeek],
    );

    // Los pagos con comprobante adjunto están finalizados: visibles pero NO programables.
    const schedulableWeekPayments = useMemo(
        () => weekPayments.filter((p) => p.status !== 'receipt_attached'),
        [weekPayments],
    );

    const [itemStates, setItemStates] = useState<Record<number, ItemState>>(() => {
        const initial: Record<number, ItemState> = {};
        payments.forEach((p) => {
            initial[p.id] = { included: true, exclusion_reason: '' };
        });
        return initial;
    });

    const toggleItem = (id: number) => {
        setItemStates((prev) => ({
            ...prev,
            [id]: { ...prev[id], included: !prev[id]?.included },
        }));
    };

    const setExclusionReason = (id: number, reason: string) => {
        setItemStates((prev) => ({
            ...prev,
            [id]: { ...prev[id], exclusion_reason: reason },
        }));
    };

    const toggleAll = (checked: boolean) => {
        setItemStates((prev) => {
            const next = { ...prev };
            schedulableWeekPayments.forEach((p) => {
                next[p.id] = { ...next[p.id], included: checked };
            });
            return next;
        });
    };

    const includedPayments = schedulableWeekPayments.filter((p) => itemStates[p.id]?.included);
    const totalIncluded = includedPayments.reduce((sum, p) => sum + parseFloat(p.total), 0);
    const allChecked = schedulableWeekPayments.length > 0 && schedulableWeekPayments.every((p) => itemStates[p.id]?.included);

    const existingScheduleForWeek = schedules.find(
        (s) => s.week_number === selectedWeek && s.year === selectedYear && s.status !== 'rejected',
    );

    const schedulePayments = existingScheduleForWeek?.payments ?? [];
    const scheduleIncluded = schedulePayments.filter((p) => p.included);
    const scheduleTotal = scheduleIncluded.reduce((sum, p) => sum + parseFloat(p.total), 0);

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
    };

    const handleSubmit = () => {
        if (schedulableWeekPayments.length === 0) return;

        setProcessing(true);
        const items = schedulableWeekPayments.map((p) => ({
            id: p.id,
            included: itemStates[p.id]?.included ?? true,
            exclusion_reason: itemStates[p.id]?.exclusion_reason || null,
        }));

        router.post(
            '/weekly-payment-schedule',
            {
                week_number: selectedWeek,
                year: selectedYear,
                items,
            },
            {
                onFinish: () => setProcessing(false),
            },
        );
    };

    const openReceiptDialog = (uuid: string, folio: number, hasExisting: boolean) => {
        setReceiptTarget({ uuid, folio, hasExisting });
        setReceiptFiles([]);
        setReceiptErrors({});
    };

    const handleUploadReceipt = () => {
        if (!receiptTarget || receiptFiles.length === 0) return;
        setReceiptUploading(true);
        setReceiptErrors({});

        const formData = new FormData();
        receiptFiles.forEach((file) => formData.append('receipt_documents[]', file));

        router.post(`/weekly-payment-schedule/${receiptTarget.uuid}/receipt`, formData, {
            forceFormData: true,
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                setReceiptTarget(null);
                setReceiptFiles([]);
            },
            onError: (errs) => setReceiptErrors(errs as Record<string, string>),
            onFinish: () => setReceiptUploading(false),
        });
    };

    const receiptCell = (payment: { uuid: string; folio_number: number; status: string; receipt_documents: DocumentItem[] }) => (
        <div className="flex items-center gap-2">
            <DocumentChips documents={payment.receipt_documents} onPreview={setPreviewDocs} />
            <Button
                variant="outline"
                size="sm"
                className="h-7 gap-1 px-2 text-xs"
                onClick={() => openReceiptDialog(payment.uuid, payment.folio_number, payment.receipt_documents.length > 0)}
                title={payment.receipt_documents.length > 0 ? 'Reemplazar el comprobante cargado' : 'Cargar comprobante de pago'}
            >
                <Paperclip className="h-3 w-3" />
                {payment.receipt_documents.length > 0 ? 'Reemplazar' : 'Comprobante'}
            </Button>
        </div>
    );

    const paymentStatusBadge = (status: string) => (
        <Badge className={`text-xs ${paymentStatusColors[status] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'}`}>
            {paymentStatusLabels[status] ?? status}
        </Badge>
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Programación de Pagos Semanal" />

            <div className="mx-auto max-w-7xl space-y-6 p-4 lg:p-6">
                {/* Project Selector */}
                <Card>
                    <CardContent className="pt-6">
                        <Label className="mb-2 block">Hoja de Inversión</Label>
                        <ProjectCombobox
                            projects={projects}
                            selectedId={selectedProjectId}
                            onSelect={(id) => router.visit(`/weekly-payment-schedule?project_id=${id}`, { preserveState: false })}
                        />
                    </CardContent>
                </Card>

                {!selectedProjectId ? (
                    <div className="rounded-md border border-dashed py-16 text-center">
                        <Inbox className="mx-auto mb-3 h-12 w-12 text-gray-300 dark:text-gray-600" />
                        <p className="text-base font-medium">Selecciona una Hoja de Inversión</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Elige un proyecto del selector arriba para ver sus pagos programados y el historial de programaciones.
                        </p>
                    </div>
                ) : (
                    <>
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-2xl font-bold tracking-tight">Programación de Pagos Semanal</h1>
                            {selectedProject && (
                                <Badge variant="secondary" className="text-xs">{selectedProject.name}</Badge>
                            )}
                        </div>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Selecciona los pagos autorizados que se procesarán en bancos esta semana.
                        </p>
                    </div>

                    {/* Export + Week navigator */}
                    <div className="flex items-center gap-2">
                        <Button variant="outline" onClick={() => { setExportScope('week'); setExportOpen(true); }}>
                            <FileDown className="mr-2 h-4 w-4" />
                            Exportar Excel
                        </Button>
                        <WeekNavigator
                            week={selectedWeek}
                            year={selectedYear}
                            currentWeek={currentWeek}
                            currentYear={currentYear}
                            onNavigate={navigateWeek}
                        />
                    </div>
                </div>

                {/* Summary cards */}
                <div className="grid gap-4 sm:grid-cols-3">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-sm text-gray-500 dark:text-gray-400">Pagos de la semana</div>
                            <div className="text-2xl font-bold">{existingScheduleForWeek ? schedulePayments.length : weekPayments.length}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-sm text-gray-500 dark:text-gray-400">{existingScheduleForWeek ? 'Pagos incluidos' : 'Pagos seleccionados'}</div>
                            <div className="text-2xl font-bold text-green-600">{existingScheduleForWeek ? scheduleIncluded.length : includedPayments.length}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-sm text-gray-500 dark:text-gray-400">{existingScheduleForWeek ? 'Total programado' : 'Total a procesar'}</div>
                            <div className="text-2xl font-bold text-blue-600">{formatCurrency(existingScheduleForWeek ? scheduleTotal : totalIncluded)}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Payments table */}
                <Card>
                    <CardHeader className="pb-3">
                        <div className="flex items-center justify-between">
                            <CardTitle className="text-base">
                                {existingScheduleForWeek ? `Programación de la Semana ${selectedWeek}` : `Pagos Autorizados — Semana ${selectedWeek}`}
                            </CardTitle>
                            {existingScheduleForWeek && (
                                <Badge className={statusColors[existingScheduleForWeek.status]}>
                                    {statusLabels[existingScheduleForWeek.status]}
                                </Badge>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent>
                        {existingScheduleForWeek ? (
                            /* ─── Semana YA programada: tabla solo-lectura + carga de comprobante ─── */
                            schedulePayments.length === 0 ? (
                                <p className="py-12 text-center text-sm text-gray-400">
                                    La programación de la semana {selectedWeek} no tiene pagos registrados.
                                </p>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b text-left text-gray-500 dark:text-gray-400">
                                                <th className="pb-3 pr-4 font-medium">Folio</th>
                                                <th className="pb-3 pr-4 font-medium">Proveedor</th>
                                                <th className="pb-3 pr-4 font-medium">Concepto</th>
                                                <th className="pb-3 pr-4 font-medium">Tipo de pago</th>
                                                <th className="pb-3 pr-4 font-medium">Fecha Provisión</th>
                                                <th className="pb-3 pr-4 text-right font-medium">Total</th>
                                                <th className="pb-3 pr-4 font-medium">Moneda</th>
                                                <th className="pb-3 pr-4 font-medium">Estatus</th>
                                                <th className="pb-3 pr-4 font-medium">Documentos</th>
                                                <th className="pb-3 font-medium">Comprobante de pago</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {schedulePayments.map((payment) => (
                                                <tr
                                                    key={payment.uuid}
                                                    className={`border-b last:border-0 ${!payment.included ? 'bg-red-50/50 dark:bg-red-900/10' : ''}`}
                                                >
                                                    <td className="py-3 pr-4 font-mono text-xs">
                                                        #{String(payment.folio_number).padStart(5, '0')}
                                                    </td>
                                                    <td className="py-3 pr-4 font-medium">{payment.provider}</td>
                                                    <td className="py-3 pr-4">{payment.concept_name}</td>
                                                    <td className="py-3 pr-4">
                                                        <Badge variant="outline" className="text-xs">
                                                            {investmentPaymentTypeLabel(payment.payment_type)}
                                                        </Badge>
                                                    </td>
                                                    <td className="py-3 pr-4">
                                                        {payment.payment_provision_date
                                                            ? new Date(payment.payment_provision_date + 'T00:00:00').toLocaleDateString('es-MX', {
                                                                  day: '2-digit',
                                                                  month: 'short',
                                                                  year: 'numeric',
                                                              })
                                                            : '-'}
                                                    </td>
                                                    <td className="py-3 pr-4 text-right font-semibold">
                                                        {formatCurrency(payment.total)}
                                                    </td>
                                                    <td className="py-3 pr-4">{payment.currency_prefix}</td>
                                                    <td className="py-3 pr-4">
                                                        {payment.included ? (
                                                            paymentStatusBadge(payment.status)
                                                        ) : (
                                                            <span className="text-xs text-red-600 dark:text-red-400" title={payment.exclusion_reason ?? undefined}>
                                                                Excluido{payment.exclusion_reason ? ` — ${payment.exclusion_reason}` : ''}
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="py-3 pr-4">
                                                        <DocumentChips documents={payment.documents} onPreview={setPreviewDocs} />
                                                    </td>
                                                    <td className="py-3">
                                                        {payment.included ? receiptCell(payment) : null}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )
                        ) : weekPayments.length === 0 ? (
                            <p className="py-12 text-center text-sm text-gray-400">
                                No hay pagos autorizados para la semana {selectedWeek}.
                            </p>
                        ) : (
                            /* ─── Semana sin programación: tabla editable (selección) + comprobante ─── */
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-gray-500 dark:text-gray-400">
                                            <th className="pb-3 pr-4">
                                                <Checkbox
                                                    checked={allChecked}
                                                    onCheckedChange={(checked) => toggleAll(checked === true)}
                                                />
                                            </th>
                                            <th className="pb-3 pr-4 font-medium">Folio</th>
                                            <th className="pb-3 pr-4 font-medium">Proveedor</th>
                                            <th className="pb-3 pr-4 font-medium">Concepto</th>
                                            <th className="pb-3 pr-4 font-medium">Tipo de pago</th>
                                            <th className="pb-3 pr-4 font-medium">Fecha Provisión</th>
                                            <th className="pb-3 pr-4 text-right font-medium">Total</th>
                                            <th className="pb-3 pr-4 font-medium">Moneda</th>
                                            <th className="pb-3 pr-4 font-medium">Estatus</th>
                                            <th className="pb-3 pr-4 font-medium">Documentos</th>
                                            <th className="pb-3 font-medium">Comprobante de pago</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {weekPayments.map((payment) => {
                                            const isFinished = payment.status === 'receipt_attached';
                                            const state = itemStates[payment.id];
                                            const isIncluded = !isFinished && (state?.included ?? true);
                                            return (
                                                <tr
                                                    key={payment.id}
                                                    className={`border-b last:border-0 ${isFinished ? 'bg-emerald-50/50 dark:bg-emerald-900/10' : !isIncluded ? 'bg-red-50/50 dark:bg-red-900/10' : ''}`}
                                                >
                                                    <td className="py-3 pr-4">
                                                        {isFinished ? (
                                                            <CheckCircle2 className="h-4 w-4 text-emerald-600 dark:text-emerald-400" aria-label="Comprobante adjunto" />
                                                        ) : (
                                                            <Checkbox
                                                                checked={isIncluded}
                                                                onCheckedChange={() => toggleItem(payment.id)}
                                                            />
                                                        )}
                                                    </td>
                                                    <td className="py-3 pr-4 font-mono text-xs">
                                                        #{String(payment.folio_number).padStart(5, '0')}
                                                    </td>
                                                    <td className="py-3 pr-4 font-medium">{payment.provider}</td>
                                                    <td className="py-3 pr-4">{payment.concept_name}</td>
                                                    <td className="py-3 pr-4">
                                                        <Badge variant="outline" className="text-xs">
                                                            {investmentPaymentTypeLabel(payment.payment_type)}
                                                        </Badge>
                                                    </td>
                                                    <td className="py-3 pr-4">
                                                        {payment.payment_provision_date
                                                            ? new Date(payment.payment_provision_date + 'T00:00:00').toLocaleDateString('es-MX', {
                                                                  day: '2-digit',
                                                                  month: 'short',
                                                                  year: 'numeric',
                                                              })
                                                            : '-'}
                                                    </td>
                                                    <td className="py-3 pr-4 text-right font-semibold">
                                                        {formatCurrency(payment.total)}
                                                    </td>
                                                    <td className="py-3 pr-4">{payment.currency_prefix}</td>
                                                    <td className="py-3 pr-4">
                                                        {isFinished ? (
                                                            paymentStatusBadge(payment.status)
                                                        ) : !isIncluded ? (
                                                            <Input
                                                                placeholder="Razón (opcional)"
                                                                value={state?.exclusion_reason ?? ''}
                                                                onChange={(e) => setExclusionReason(payment.id, e.target.value)}
                                                                className="h-8 text-xs"
                                                            />
                                                        ) : null}
                                                    </td>
                                                    <td className="py-3 pr-4">
                                                        <DocumentChips documents={payment.documents} onPreview={setPreviewDocs} />
                                                    </td>
                                                    <td className="py-3">{receiptCell(payment)}</td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        {/* Submit button — deshabilitado temporalmente por decisión operativa */}
                        {schedulableWeekPayments.length > 0 && !existingScheduleForWeek && (
                            <div className="mt-6 flex items-center justify-between border-t pt-4">
                                <p className="text-sm text-gray-500">
                                    {includedPayments.length} de {schedulableWeekPayments.length} pagos seleccionados ·{' '}
                                    <span className="font-semibold">{formatCurrency(totalIncluded)}</span>
                                </p>
                                <div className="flex flex-col items-end gap-1">
                                    <Button onClick={handleSubmit} disabled title="Deshabilitado temporalmente">
                                        <Send className="mr-2 h-4 w-4" />
                                        {processing ? 'Enviando...' : 'Guardar Programación'}
                                    </Button>
                                    <span className="text-[11px] text-muted-foreground">Deshabilitado temporalmente.</span>
                                </div>
                            </div>
                        )}

                        {existingScheduleForWeek && (
                            <div className="mt-4 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                Ya existe una programación para esta semana con estado:{' '}
                                <span className="font-semibold">{statusLabels[existingScheduleForWeek.status]}</span>. Los pagos se muestran en solo lectura;
                                puedes adjuntar el comprobante de pago de cada uno.
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Schedule history */}
                {schedules.length > 0 && (
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base">Historial de Programaciones — Proyecto seleccionado</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-gray-500 dark:text-gray-400">
                                            <th className="pb-3 pr-4 font-medium">Semana</th>
                                            <th className="pb-3 pr-4 font-medium">Creado por</th>
                                            <th className="pb-3 pr-4 font-medium">Fecha</th>
                                            <th className="pb-3 pr-4 font-medium">Pagos</th>
                                            <th className="pb-3 pr-4 text-right font-medium">Monto</th>
                                            <th className="pb-3 font-medium">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {schedules.map((schedule) => (
                                            <tr
                                                key={schedule.id}
                                                className="cursor-pointer border-b last:border-0 hover:bg-accent/40"
                                                title="Ver esta semana"
                                                onClick={() => {
                                                    setSelectedWeek(schedule.week_number);
                                                    setSelectedYear(schedule.year);
                                                }}
                                            >
                                                <td className="py-3 pr-4 font-medium">
                                                    S{schedule.week_number}/{schedule.year}
                                                </td>
                                                <td className="py-3 pr-4">{schedule.creator_name}</td>
                                                <td className="py-3 pr-4">
                                                    {schedule.created_at
                                                        ? new Date(schedule.created_at).toLocaleDateString('es-MX', {
                                                              day: '2-digit',
                                                              month: 'short',
                                                              year: 'numeric',
                                                          })
                                                        : '-'}
                                                </td>
                                                <td className="py-3 pr-4">
                                                    {schedule.included_count} / {schedule.items_count}
                                                </td>
                                                <td className="py-3 pr-4 text-right font-semibold">
                                                    {formatCurrency(schedule.total_amount)}
                                                </td>
                                                <td className="py-3">
                                                    <Badge className={statusColors[schedule.status]}>
                                                        {statusLabels[schedule.status]}
                                                    </Badge>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}
                    </>
                )}
            </div>

            {/* Preview de documentos */}
            <Dialog open={previewDocs !== null} onOpenChange={(open) => { if (!open) setPreviewDocs(null); }}>
                <DialogContent className="sm:max-w-3xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Documentos del pago</DialogTitle>
                        <DialogDescription>Vista previa de los archivos adjuntos.</DialogDescription>
                    </DialogHeader>
                    {previewDocs && <DocumentPreview documents={previewDocs} />}
                </DialogContent>
            </Dialog>

            {/* Diálogo: exportar a Excel */}
            <Dialog open={exportOpen} onOpenChange={setExportOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Exportar a Excel</DialogTitle>
                        <DialogDescription>
                            Descarga la programación de pagos de {selectedProject?.name ?? 'este proyecto'} en formato .xlsx (incluye columna de semana).
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-2">
                        <label className={`flex cursor-pointer items-center gap-3 rounded-lg border p-3 text-sm transition-colors ${exportScope === 'week' ? 'border-primary bg-accent/40' : 'hover:bg-accent/20'}`}>
                            <input
                                type="radio"
                                name="export-scope"
                                checked={exportScope === 'week'}
                                onChange={() => setExportScope('week')}
                                className="h-4 w-4"
                            />
                            <span>
                                <span className="font-medium">Semana seleccionada</span>
                                <span className="ml-1 text-muted-foreground">(S{selectedWeek}/{selectedYear})</span>
                            </span>
                        </label>
                        <label className={`flex cursor-pointer items-center gap-3 rounded-lg border p-3 text-sm transition-colors ${exportScope === 'all' ? 'border-primary bg-accent/40' : 'hover:bg-accent/20'}`}>
                            <input
                                type="radio"
                                name="export-scope"
                                checked={exportScope === 'all'}
                                onChange={() => setExportScope('all')}
                                className="h-4 w-4"
                            />
                            <span className="font-medium">Todas las semanas</span>
                        </label>
                        <div className="flex justify-end gap-2 pt-2">
                            <Button variant="outline" onClick={() => setExportOpen(false)}>Cancelar</Button>
                            <Button
                                onClick={() => {
                                    const params = new URLSearchParams({ project_id: String(selectedProjectId) });
                                    if (exportScope === 'week') {
                                        params.set('week', String(selectedWeek));
                                        params.set('year', String(selectedYear));
                                    }
                                    window.location.href = `/weekly-payment-schedule/export?${params.toString()}`;
                                    setExportOpen(false);
                                }}
                            >
                                <FileDown className="mr-2 h-4 w-4" />
                                Descargar
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

            {/* Diálogo: cargar comprobante de pago */}
            <Dialog open={receiptTarget !== null} onOpenChange={(open) => { if (!open && !receiptUploading) setReceiptTarget(null); }}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{receiptTarget?.hasExisting ? 'Reemplazar comprobante de pago' : 'Cargar comprobante de pago'}</DialogTitle>
                        <DialogDescription>
                            Pago #{receiptTarget ? String(receiptTarget.folio).padStart(5, '0') : ''} —{' '}
                            {receiptTarget?.hasExisting
                                ? 'los archivos nuevos reemplazarán el comprobante actual.'
                                : <>al guardar, el pago quedará como <span className="font-semibold">Comprobante adjunto</span> (proceso finalizado).</>}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-3">
                        <div>
                            <Label htmlFor="receipt-files" className="mb-2 block text-sm">Archivos (PDF, XML o imagen · máx. 5 · 10MB c/u)</Label>
                            <Input
                                id="receipt-files"
                                type="file"
                                multiple
                                accept=".pdf,.xml,.jpg,.jpeg,.png"
                                onChange={(e) => setReceiptFiles(Array.from(e.target.files ?? []))}
                            />
                        </div>
                        {receiptFiles.length > 0 && (
                            <ul className="space-y-1 text-xs text-muted-foreground">
                                {receiptFiles.map((f, i) => (
                                    <li key={i} className="flex items-center gap-1.5">
                                        <FileText className="h-3 w-3" /> {f.name}
                                    </li>
                                ))}
                            </ul>
                        )}
                        {Object.values(receiptErrors).length > 0 && (
                            <div className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                                {Object.values(receiptErrors).map((msg, i) => (
                                    <div key={i}>{msg}</div>
                                ))}
                            </div>
                        )}
                        <div className="flex justify-end gap-2">
                            <Button variant="outline" onClick={() => setReceiptTarget(null)} disabled={receiptUploading}>
                                Cancelar
                            </Button>
                            <Button onClick={handleUploadReceipt} disabled={receiptUploading || receiptFiles.length === 0}>
                                <Upload className="mr-2 h-4 w-4" />
                                {receiptUploading ? 'Subiendo...' : receiptTarget?.hasExisting ? 'Reemplazar comprobante' : 'Adjuntar comprobante'}
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
