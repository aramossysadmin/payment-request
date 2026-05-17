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

type Props = {
    batch: {
        uuid: string;
        token: string;
        department: string;
        project: string;
        requester: string;
        week_number: number;
        year: number;
        expires_at: string | null;
        payments: Payment[];
        total_to_pay: string;
    };
};

export default function BatchApprovalShowFinal({ batch }: Props) {
    const [approvedUuids, setApprovedUuids] = useState<Set<string>>(
        () => new Set(batch.payments.map((p) => p.uuid)),
    );
    const [rejectionReason, setRejectionReason] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const approvedCount = approvedUuids.size;
    const rejectedCount = batch.payments.length - approvedCount;

    const approvedTotal = useMemo(
        () =>
            batch.payments
                .filter((p) => approvedUuids.has(p.uuid))
                .reduce((sum, p) => sum + Number(p.approved_amount), 0),
        [approvedUuids, batch.payments],
    );

    const togglePayment = (uuid: string) => {
        setApprovedUuids((prev) => {
            const next = new Set(prev);
            if (next.has(uuid)) next.delete(uuid);
            else next.add(uuid);
            return next;
        });
    };

    const handleSubmit = () => {
        setSubmitting(true);
        router.post(
            `/investment-batch-final-approval/${batch.token}/review`,
            {
                approved_uuids: Array.from(approvedUuids),
                rejection_reason: rejectionReason || null,
            },
            {
                onFinish: () => setSubmitting(false),
            },
        );
    };

    return (
        <>
            <Head title={`Aprobación FINAL — ${batch.department}`} />
            <div className="min-h-screen bg-gray-50 p-4 dark:bg-gray-900 md:p-8">
                <div className="mx-auto max-w-4xl space-y-6">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">
                            Autorización Final de Pagos
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            El Project Manager ya revisó este lote. Esta es la aprobación definitiva antes de proceder con los pagos. Algunos montos pudieron haber sido ajustados.
                        </p>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Información del Lote</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <p className="text-xs text-muted-foreground">Departamento</p>
                                    <p className="font-medium">{batch.department}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">Proyecto</p>
                                    <p className="font-medium">{batch.project}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">Solicitante</p>
                                    <p className="font-medium">{batch.requester}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">Semana / Año</p>
                                    <p className="font-medium">Semana {batch.week_number} ({batch.year})</p>
                                </div>
                            </div>
                            {batch.expires_at && (
                                <div className="mt-4 flex items-center gap-2 rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                                    <Clock className="h-3.5 w-3.5" />
                                    Este enlace expira el {new Date(batch.expires_at).toLocaleString('es-MX')}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Pagos a Autorizar Definitivamente</CardTitle>
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
                                            <th className="px-4 py-3 font-semibold whitespace-nowrap text-right border-r border-gray-200 dark:border-gray-700">Solicitado</th>
                                            <th className="px-4 py-3 font-semibold whitespace-nowrap text-right">A pagar</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100 dark:divide-gray-800">
                                        {batch.payments.map((payment) => (
                                            <tr key={payment.uuid} className="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                                <td className="px-3 py-3">
                                                    <Checkbox
                                                        checked={approvedUuids.has(payment.uuid)}
                                                        onCheckedChange={() => togglePayment(payment.uuid)}
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
                                                <td className="px-4 py-3 text-right font-mono text-sm text-gray-500 border-r border-gray-100 dark:border-gray-800">
                                                    {formatCurrency(payment.total)}
                                                </td>
                                                <td className="px-4 py-3 text-right font-mono font-semibold">
                                                    <div>{formatCurrency(payment.approved_amount)} <span className="text-xs text-gray-500">{payment.currency_prefix}</span></div>
                                                    {payment.was_adjusted && (
                                                        <div className="mt-0.5 text-[10px] uppercase tracking-wide text-amber-600 dark:text-amber-400">
                                                            ↑ ajustado por PM
                                                        </div>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                    <tfoot className="bg-gray-50 dark:bg-gray-800/50">
                                        <tr className="border-t-2 border-gray-200 dark:border-gray-700">
                                            <td colSpan={5} className="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-400">
                                                Total a pagar:
                                            </td>
                                            <td className="px-4 py-3 text-right font-mono font-bold">
                                                {formatCurrency(approvedTotal)}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <div className="mt-5 space-y-2">
                                <Label htmlFor="rejection_reason">
                                    Motivo de rechazo (opcional, aplica a los desmarcados)
                                </Label>
                                <textarea
                                    id="rejection_reason"
                                    className="border-input focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:outline-none"
                                    rows={3}
                                    placeholder="Ej. Cambió la prioridad, problema con proveedor, etc."
                                    value={rejectionReason}
                                    onChange={(e) => setRejectionReason(e.target.value)}
                                />
                            </div>

                            <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div className="text-sm text-muted-foreground">
                                    {approvedCount} aprobados · {rejectedCount} rechazados
                                </div>
                                <Button
                                    size="lg"
                                    onClick={handleSubmit}
                                    disabled={submitting}
                                >
                                    {submitting ? 'Guardando...' : 'Autorizar Pagos Definitivamente'}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
