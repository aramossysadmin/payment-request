import { Head, router } from '@inertiajs/react';
import { Clock } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { formatCurrency } from '@/lib/currency';

type Payment = {
    uuid: string;
    folio_number: number;
    concept: string;
    provider: string;
    rfc: string | null;
    currency_prefix: string;
    total: string;
    approved_amount: string;
    was_adjusted: boolean;
};

type DepartmentGroup = {
    batch_uuid: string;
    department: string;
    week_number: number;
    year: number;
    payments: Payment[];
    subtotal: string;
};

type Props = {
    session: {
        token: string;
        project: string;
        requester: string;
        expires_at: string | null;
        batches_count: number;
        total_payments_count: number;
        grand_total: string;
        department_groups: DepartmentGroup[];
    };
};

export default function BatchApprovalShowFinalSession({ session }: Props) {
    const allUuids = useMemo(
        () => session.department_groups.flatMap((g) => g.payments.map((p) => p.uuid)),
        [session.department_groups],
    );

    const [approvedUuids, setApprovedUuids] = useState<Set<string>>(() => new Set(allUuids));
    const [rejectionReason, setRejectionReason] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const approvedCount = approvedUuids.size;
    const rejectedCount = allUuids.length - approvedCount;

    const approvedTotal = useMemo(() => {
        let total = 0;
        session.department_groups.forEach((group) => {
            group.payments.forEach((p) => {
                if (approvedUuids.has(p.uuid)) {
                    total += Number(p.approved_amount);
                }
            });
        });
        return total;
    }, [approvedUuids, session.department_groups]);

    const togglePayment = (uuid: string) => {
        setApprovedUuids((prev) => {
            const next = new Set(prev);
            if (next.has(uuid)) next.delete(uuid);
            else next.add(uuid);
            return next;
        });
    };

    const toggleDepartment = (group: DepartmentGroup, allChecked: boolean) => {
        setApprovedUuids((prev) => {
            const next = new Set(prev);
            group.payments.forEach((p) => {
                if (allChecked) next.delete(p.uuid);
                else next.add(p.uuid);
            });
            return next;
        });
    };

    const toggleAll = (allChecked: boolean) => {
        setApprovedUuids(allChecked ? new Set() : new Set(allUuids));
    };

    const handleSubmit = () => {
        setSubmitting(true);
        router.post(
            `/investment-final-approval-session/${session.token}/review`,
            {
                approved_uuids: Array.from(approvedUuids),
                rejection_reason: rejectionReason || null,
            },
            {
                onFinish: () => setSubmitting(false),
            },
        );
    };

    const expiresAt = session.expires_at ? new Date(session.expires_at) : null;
    const allChecked = approvedUuids.size === allUuids.length;

    return (
        <>
            <Head title={`Aprobación FINAL — ${session.project}`} />

            <div className="min-h-screen bg-gray-50 py-8 dark:bg-gray-950">
                <div className="mx-auto max-w-5xl space-y-6 px-4">
                    {/* Header */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Aprobación FINAL — {session.project}</CardTitle>
                            <p className="mt-1 text-sm text-muted-foreground">
                                El Project Manager terminó de revisar los pagos. Tienes {session.batches_count}{' '}
                                {session.batches_count === 1 ? 'lote' : 'lotes'} con un total de{' '}
                                {session.total_payments_count}{' '}
                                {session.total_payments_count === 1 ? 'pago' : 'pagos'} que requieren tu aprobación
                                final.
                            </p>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-wrap items-center gap-4 text-sm">
                                <div>
                                    <span className="text-muted-foreground">Project Manager: </span>
                                    <span className="font-medium">{session.requester}</span>
                                </div>
                                {expiresAt && (
                                    <div className="flex items-center gap-1 text-amber-700 dark:text-amber-400">
                                        <Clock className="h-4 w-4" />
                                        <span>
                                            Vigencia: {expiresAt.toLocaleString('es-MX')}
                                        </span>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Toggle all */}
                    <Card>
                        <CardContent className="flex items-center justify-between pt-6">
                            <div className="flex items-center gap-3">
                                <Checkbox
                                    checked={allChecked}
                                    onCheckedChange={() => toggleAll(allChecked)}
                                />
                                <Label className="cursor-pointer" onClick={() => toggleAll(allChecked)}>
                                    {allChecked ? 'Desmarcar todos' : 'Marcar todos'}
                                </Label>
                            </div>
                            <div className="text-sm">
                                <span className="font-semibold text-green-600">{approvedCount} aprobados</span>
                                {' · '}
                                <span className="font-semibold text-red-600">{rejectedCount} rechazados</span>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Department groups */}
                    {session.department_groups.map((group) => {
                        const groupAllChecked = group.payments.every((p) => approvedUuids.has(p.uuid));

                        return (
                            <Card key={group.batch_uuid}>
                                <CardHeader>
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <CardTitle className="text-base">{group.department}</CardTitle>
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                Semana {group.week_number} / {group.year} ·{' '}
                                                {group.payments.length}{' '}
                                                {group.payments.length === 1 ? 'pago' : 'pagos'} · Subtotal:{' '}
                                                {formatCurrency(group.subtotal)}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Checkbox
                                                checked={groupAllChecked}
                                                onCheckedChange={() => toggleDepartment(group, groupAllChecked)}
                                            />
                                            <Label className="cursor-pointer text-xs" onClick={() => toggleDepartment(group, groupAllChecked)}>
                                                {groupAllChecked ? 'Desmarcar' : 'Marcar'} depto
                                            </Label>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <div className="overflow-x-auto rounded-md border border-gray-200 dark:border-gray-700">
                                        <table className="w-full text-sm">
                                            <thead className="bg-gray-50 dark:bg-gray-800">
                                                <tr className="border-b-2 border-gray-200 text-left text-gray-600 dark:border-gray-700 dark:text-gray-300">
                                                    <th className="w-12 px-3 py-3 font-semibold whitespace-nowrap">
                                                        Aprobar
                                                    </th>
                                                    <th className="border-r border-gray-200 px-4 py-3 font-semibold whitespace-nowrap dark:border-gray-700">
                                                        Folio
                                                    </th>
                                                    <th className="border-r border-gray-200 px-4 py-3 font-semibold whitespace-nowrap dark:border-gray-700">
                                                        Concepto
                                                    </th>
                                                    <th className="border-r border-gray-200 px-4 py-3 font-semibold whitespace-nowrap dark:border-gray-700">
                                                        Proveedor
                                                    </th>
                                                    <th className="px-4 py-3 text-right font-semibold whitespace-nowrap">
                                                        Monto a pagar
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                                {group.payments.map((payment) => {
                                                    const isApproved = approvedUuids.has(payment.uuid);
                                                    return (
                                                        <tr key={payment.uuid} className={isApproved ? '' : 'opacity-50'}>
                                                            <td className="px-3 py-3">
                                                                <Checkbox
                                                                    checked={isApproved}
                                                                    onCheckedChange={() => togglePayment(payment.uuid)}
                                                                />
                                                            </td>
                                                            <td className="border-r border-gray-100 px-4 py-3 font-mono text-xs text-gray-500 dark:border-gray-800">
                                                                #{String(payment.folio_number).padStart(5, '0')}
                                                            </td>
                                                            <td className="border-r border-gray-100 px-4 py-3 font-medium dark:border-gray-800">
                                                                {payment.concept}
                                                            </td>
                                                            <td className="border-r border-gray-100 px-4 py-3 text-gray-600 dark:border-gray-800 dark:text-gray-400">
                                                                <div>{payment.provider}</div>
                                                                {payment.rfc && (
                                                                    <div className="text-xs text-gray-500">{payment.rfc}</div>
                                                                )}
                                                            </td>
                                                            <td className="px-4 py-3 text-right font-semibold whitespace-nowrap">
                                                                <div>{formatCurrency(payment.approved_amount)}</div>
                                                                <div className="text-xs text-gray-500">
                                                                    {payment.currency_prefix}
                                                                </div>
                                                                {payment.was_adjusted && (
                                                                    <div className="mt-1 text-xs text-amber-600">
                                                                        Ajustado por PM
                                                                    </div>
                                                                )}
                                                            </td>
                                                        </tr>
                                                    );
                                                })}
                                            </tbody>
                                        </table>
                                    </div>
                                </CardContent>
                            </Card>
                        );
                    })}

                    {/* Submit area */}
                    <Card>
                        <CardContent className="space-y-4 pt-6">
                            {rejectedCount > 0 && (
                                <div className="space-y-2">
                                    <Label>Motivo del rechazo (aplica a los desmarcados)</Label>
                                    <textarea
                                        className="border-input focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:outline-none"
                                        rows={3}
                                        value={rejectionReason}
                                        onChange={(e) => setRejectionReason(e.target.value)}
                                        placeholder="Explica brevemente por qué se rechazan los pagos desmarcados..."
                                    />
                                </div>
                            )}

                            <div className="flex items-center justify-between rounded-md bg-gray-100 px-4 py-3 dark:bg-gray-900">
                                <div className="text-sm">
                                    <div className="font-semibold">Resumen final</div>
                                    <div className="text-muted-foreground">
                                        {approvedCount} aprobados ·{' '}
                                        <span className="font-semibold text-green-600">
                                            {formatCurrency(approvedTotal)}
                                        </span>
                                        {rejectedCount > 0 && (
                                            <>
                                                {' · '}
                                                {rejectedCount} rechazados
                                            </>
                                        )}
                                    </div>
                                </div>
                                <Button onClick={handleSubmit} disabled={submitting}>
                                    {submitting ? 'Procesando...' : 'Confirmar decisiones'}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
