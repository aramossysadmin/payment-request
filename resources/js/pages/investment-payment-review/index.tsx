import { Head, router } from '@inertiajs/react';
import { Eye, Inbox, Send } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { DocumentPreview } from '@/components/document-preview';
import { ProjectCombobox, type Project } from '@/components/project-combobox';
import AppLayout from '@/layouts/app-layout';
import { formatCurrency } from '@/lib/currency';
import type { BreadcrumbItem } from '@/types';

type Payment = {
    uuid: string;
    folio_number: number;
    concept: string;
    project: string;
    provider: string;
    rfc: string | null;
    requester: string;
    currency_prefix: string;
    branch: string;
    subtotal: string;
    iva: string;
    total: string;
    payment_provision_date: string | null;
    payment_week_number: number | null;
    budget_total: string;
    budget_committed: string;
    budget_paid: string;
    budget_available: string;
    documents: { name: string; url: string }[];
};

type Group = {
    department: string;
    count: number;
    total: string;
    payments: Payment[];
};

type Props = {
    groups: Group[];
    totalCount: number;
    projects: Project[];
    selectedProjectId: number | null;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Revisión de Pagos', href: '/investment-payment-review' },
];

function formatPaymentDate(date: string | null): string {
    if (!date) {
        return '—';
    }
    const parsed = new Date(`${date}T00:00:00`);
    if (Number.isNaN(parsed.getTime())) {
        return date;
    }
    return parsed.toLocaleDateString('es-MX', { day: '2-digit', month: 'long', year: 'numeric' });
}

export default function InvestmentPaymentReviewIndex({ groups, totalCount, projects, selectedProjectId }: Props) {
    const [decisions, setDecisions] = useState<Record<string, { approved: boolean; approved_amount: string }>>(
        () => {
            const initial: Record<string, { approved: boolean; approved_amount: string }> = {};
            groups.forEach((g) => {
                g.payments.forEach((p) => {
                    initial[p.uuid] = { approved: true, approved_amount: p.total };
                });
            });
            return initial;
        },
    );
    const [rejectionReason, setRejectionReason] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [detailUuid, setDetailUuid] = useState<string | null>(null);

    const detailPayment = useMemo(
        () => groups.flatMap((g) => g.payments).find((p) => p.uuid === detailUuid) ?? null,
        [groups, detailUuid],
    );

    const toggleApproval = (uuid: string) => {
        setDecisions((prev) => ({
            ...prev,
            [uuid]: { ...prev[uuid], approved: !prev[uuid].approved },
        }));
    };

    const updateAmount = (uuid: string, value: string, maxTotal: string) => {
        setErrors((prev) => {
            const next = { ...prev };
            delete next[uuid];
            return next;
        });

        const numeric = Number(value);
        const maxNumeric = Number(maxTotal);

        if (value !== '' && (isNaN(numeric) || numeric <= 0)) {
            setErrors((prev) => ({ ...prev, [uuid]: 'Debe ser un número mayor a cero' }));
        } else if (numeric > maxNumeric) {
            setErrors((prev) => ({ ...prev, [uuid]: `No puede exceder $${maxTotal}` }));
        }

        setDecisions((prev) => ({
            ...prev,
            [uuid]: { ...prev[uuid], approved_amount: value },
        }));
    };

    const counts = useMemo(() => {
        const approved = Object.values(decisions).filter((d) => d.approved).length;
        const rejected = Object.values(decisions).filter((d) => !d.approved).length;
        return { approved, rejected };
    }, [decisions]);

    const totalApproved = useMemo(() => {
        let total = 0;
        groups.forEach((g) => {
            g.payments.forEach((p) => {
                const d = decisions[p.uuid];
                if (d?.approved) {
                    total += Number(d.approved_amount || p.total);
                }
            });
        });
        return total;
    }, [decisions, groups]);

    const hasErrors = Object.keys(errors).length > 0;

    const handleSubmit = () => {
        if (hasErrors) return;
        setSubmitting(true);

        const payload = {
            decisions: Object.entries(decisions).map(([uuid, d]) => ({
                uuid,
                approved: d.approved,
                approved_amount: d.approved ? Number(d.approved_amount) : null,
            })),
            rejection_reason: rejectionReason || null,
        };

        router.post('/investment-payment-review', payload, {
            preserveScroll: true,
            onFinish: () => setSubmitting(false),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Revisión de Pagos" />

            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-foreground">
                        Revisión de Pagos
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Revisa los pagos aprobados por el CEO. Marca los que apruebas, ajusta los montos si es necesario (solo reducciones), y desmarca los que rechazas.
                    </p>
                </div>

                {/* Project Selector */}
                <Card>
                    <CardContent className="pt-6">
                        <Label className="mb-2 block">Hoja de Inversión</Label>
                        <ProjectCombobox
                            projects={projects}
                            selectedId={selectedProjectId}
                            onSelect={(id) => router.visit(`/investment-payment-review?project_id=${id}`, { preserveState: false })}
                        />
                    </CardContent>
                </Card>

                {!selectedProjectId ? (
                    <Card>
                        <CardContent className="py-16 text-center">
                            <Inbox className="mx-auto mb-3 h-12 w-12 text-muted-foreground/50" />
                            <p className="text-base font-medium">Selecciona una Hoja de Inversión</p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Elige un proyecto del selector arriba para ver sus pagos pendientes de revisión.
                            </p>
                        </CardContent>
                    </Card>
                ) : totalCount === 0 ? (
                    <Card>
                        <CardContent className="py-16 text-center">
                            <Inbox className="mx-auto mb-3 h-12 w-12 text-muted-foreground/50" />
                            <p className="text-sm text-muted-foreground">No hay pagos pendientes de revisión para este proyecto.</p>
                        </CardContent>
                    </Card>
                ) : (
                    <>
                        {groups.map((group) => (
                            <Card key={group.department}>
                                <CardHeader>
                                    <div className="flex items-center justify-between">
                                        <CardTitle>{group.department}</CardTitle>
                                        <div className="text-sm text-muted-foreground">
                                            {group.count} {group.count === 1 ? 'pago' : 'pagos'} · Solicitado: {formatCurrency(group.total)}
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <div className="overflow-x-auto rounded-md border border-gray-200 dark:border-gray-700">
                                        <table className="w-full text-sm">
                                            <thead className="bg-gray-50 dark:bg-gray-800">
                                                <tr className="border-b-2 border-gray-200 text-left text-gray-600 dark:border-gray-700 dark:text-gray-300">
                                                    <th className="px-3 py-3 font-semibold whitespace-nowrap w-12">Aprobar</th>
                                                    <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700">Folio</th>
                                                    <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700">Concepto</th>
                                                    <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700">Proveedor</th>
                                                    <th className="px-4 py-3 font-semibold whitespace-nowrap border-r border-gray-200 dark:border-gray-700">Solicitante</th>
                                                    <th className="px-4 py-3 font-semibold whitespace-nowrap text-right border-r border-gray-200 dark:border-gray-700">Solicitado</th>
                                                    <th className="px-4 py-3 font-semibold whitespace-nowrap text-right border-r border-gray-200 dark:border-gray-700">Aprobar Monto</th>
                                                    <th className="px-2 py-3 font-semibold whitespace-nowrap text-center w-12">Detalle</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                                {group.payments.map((payment) => {
                                                    const decision = decisions[payment.uuid];
                                                    const isApproved = decision?.approved ?? false;
                                                    const error = errors[payment.uuid];

                                                    return (
                                                        <tr key={payment.uuid} className={isApproved ? '' : 'opacity-50'}>
                                                            <td className="px-3 py-3">
                                                                <Checkbox
                                                                    checked={isApproved}
                                                                    onCheckedChange={() => toggleApproval(payment.uuid)}
                                                                />
                                                            </td>
                                                            <td className="px-4 py-3 font-mono text-xs text-gray-500 border-r border-gray-100 dark:border-gray-800">
                                                                #{String(payment.folio_number).padStart(5, '0')}
                                                            </td>
                                                            <td className="px-4 py-3 font-medium border-r border-gray-100 dark:border-gray-800">{payment.concept}</td>
                                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800">
                                                                <div>{payment.provider}</div>
                                                                {payment.rfc && <div className="text-xs text-gray-500">{payment.rfc}</div>}
                                                            </td>
                                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs border-r border-gray-100 dark:border-gray-800">
                                                                {payment.requester}
                                                            </td>
                                                            <td className="px-4 py-3 text-right font-mono font-semibold border-r border-gray-100 dark:border-gray-800">
                                                                {formatCurrency(payment.total)} <span className="text-xs text-gray-500">{payment.currency_prefix}</span>
                                                            </td>
                                                            <td className="px-4 py-3">
                                                                <div className="flex flex-col items-end gap-1">
                                                                    <Input
                                                                        type="number"
                                                                        step="0.01"
                                                                        min="0"
                                                                        max={payment.total}
                                                                        disabled={!isApproved}
                                                                        value={decision?.approved_amount ?? ''}
                                                                        onChange={(e) => updateAmount(payment.uuid, e.target.value, payment.total)}
                                                                        className={`h-8 w-32 text-right font-mono text-sm ${error ? 'border-red-500' : ''}`}
                                                                    />
                                                                    {error && <p className="text-xs text-red-500">{error}</p>}
                                                                </div>
                                                            </td>
                                                            <td className="px-2 py-3 text-center">
                                                                <Button
                                                                    type="button"
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="h-8 w-8"
                                                                    aria-label="Ver detalle del pago"
                                                                    onClick={() => setDetailUuid(payment.uuid)}
                                                                >
                                                                    <Eye className="h-4 w-4" />
                                                                </Button>
                                                            </td>
                                                        </tr>
                                                    );
                                                })}
                                            </tbody>
                                        </table>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}

                        <Card>
                            <CardContent className="pt-6 space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="rejection_reason">
                                        Motivo de rechazo (opcional, aplica a los desmarcados)
                                    </Label>
                                    <textarea
                                        id="rejection_reason"
                                        className="border-input focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:outline-none"
                                        rows={3}
                                        placeholder="Ej. Falta justificación, monto excesivo, etc."
                                        value={rejectionReason}
                                        onChange={(e) => setRejectionReason(e.target.value)}
                                    />
                                </div>

                                <div className="flex flex-col gap-3 border-t pt-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div className="text-sm">
                                        <p className="text-muted-foreground">
                                            {counts.approved} aprobados · {counts.rejected} rechazados
                                        </p>
                                        <p className="text-base font-semibold">
                                            Total a aprobar: <span className="font-mono">{formatCurrency(totalApproved)}</span>
                                        </p>
                                    </div>
                                    <Button
                                        size="lg"
                                        onClick={handleSubmit}
                                        disabled={submitting || hasErrors}
                                    >
                                        <Send className="mr-2 h-4 w-4" />
                                        {submitting ? 'Enviando...' : 'Guardar aprobación final'}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>

                        <Dialog open={detailUuid !== null} onOpenChange={(open) => !open && setDetailUuid(null)}>
                            <DialogContent className="sm:max-w-md">
                                {detailPayment && (
                                    <>
                                        <DialogHeader>
                                            <DialogTitle>
                                                Pago #{String(detailPayment.folio_number).padStart(5, '0')}
                                            </DialogTitle>
                                            <DialogDescription>{detailPayment.concept}</DialogDescription>
                                        </DialogHeader>

                                        <div className="space-y-5 text-sm">
                                            <section className="space-y-2">
                                                <h4 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                                    Semana del pago
                                                </h4>
                                                <div className="flex items-center justify-between">
                                                    <span className="text-muted-foreground">Semana</span>
                                                    <span className="font-medium">
                                                        {detailPayment.payment_week_number
                                                            ? `Semana ${detailPayment.payment_week_number}`
                                                            : '—'}
                                                    </span>
                                                </div>
                                                <div className="flex items-center justify-between">
                                                    <span className="text-muted-foreground">Fecha de pago</span>
                                                    <span className="font-medium">
                                                        {formatPaymentDate(detailPayment.payment_provision_date)}
                                                    </span>
                                                </div>
                                            </section>

                                            <section className="space-y-2 border-t pt-4">
                                                <h4 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                                    Presupuesto del concepto
                                                </h4>
                                                <div className="flex items-center justify-between">
                                                    <span className="text-muted-foreground">Presupuesto</span>
                                                    <span className="font-mono">{formatCurrency(detailPayment.budget_total)}</span>
                                                </div>
                                                <div className="flex items-center justify-between">
                                                    <span className="text-amber-700 dark:text-amber-400">Comprometido</span>
                                                    <span className="font-mono text-amber-700 dark:text-amber-400">{formatCurrency(detailPayment.budget_committed)}</span>
                                                </div>
                                                <div className="flex items-center justify-between">
                                                    <span className="text-blue-700 dark:text-blue-400">Pagado</span>
                                                    <span className="font-mono text-blue-700 dark:text-blue-400">{formatCurrency(detailPayment.budget_paid)}</span>
                                                </div>
                                                <div className="flex items-center justify-between border-t pt-2">
                                                    <span className="font-semibold">Disponible</span>
                                                    <span className="font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                                                        {formatCurrency(detailPayment.budget_available)}
                                                    </span>
                                                </div>
                                            </section>

                                            <section className="space-y-2 border-t pt-4">
                                                <h4 className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                                    Documentos
                                                </h4>
                                                {detailPayment.documents.length === 0 ? (
                                                    <p className="text-sm text-muted-foreground">Sin documentos adjuntos.</p>
                                                ) : (
                                                    <DocumentPreview documents={detailPayment.documents} />
                                                )}
                                            </section>
                                        </div>
                                    </>
                                )}
                            </DialogContent>
                        </Dialog>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
