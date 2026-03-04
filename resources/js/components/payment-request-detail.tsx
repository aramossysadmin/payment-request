import { router } from '@inertiajs/react';
import { CheckCircle, Download, Pencil, XCircle } from 'lucide-react';
import { useState } from 'react';
import { ApprovalTimeline } from '@/components/approval-timeline';
import { ConfirmationDialog } from '@/components/confirmation-dialog';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency } from '@/lib/currency';
import type { PaymentRequest } from '@/types';

type PaymentRequestDetailProps = {
    paymentRequest: PaymentRequest;
    canApprove: boolean;
};

export function PaymentRequestDetail({ paymentRequest: pr, canApprove }: PaymentRequestDetailProps) {
    const [approveDialogOpen, setApproveDialogOpen] = useState(false);
    const [rejectDialogOpen, setRejectDialogOpen] = useState(false);
    const [rejectComments, setRejectComments] = useState('');
    const [processing, setProcessing] = useState(false);

    const handleApprove = () => {
        setProcessing(true);
        router.post(`/payment-requests/${pr.id}/approve`, {}, {
            onFinish: () => {
                setProcessing(false);
                setApproveDialogOpen(false);
            },
        });
    };

    const handleReject = () => {
        setProcessing(true);
        router.post(
            `/payment-requests/${pr.id}/reject`,
            { comments: rejectComments },
            {
                onFinish: () => {
                    setProcessing(false);
                    setRejectDialogOpen(false);
                    setRejectComments('');
                },
            },
        );
    };

    const isEditable = pr.status.name === 'pending_department';

    return (
        <>
            <div className="space-y-6">
                <Card className="border-l-4 border-l-primary">
                    <CardContent className="pt-6">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p className="font-mono text-3xl font-bold text-foreground">
                                    #{String(pr.folio_number).padStart(5, '0')}
                                </p>
                                <div className="mt-2">
                                    <StatusBadge status={pr.status} />
                                </div>
                            </div>
                            <div className="text-right">
                                <div className="space-y-1 text-sm text-muted-foreground">
                                    <p>
                                        Subtotal:{' '}
                                        <span className="font-mono font-medium text-foreground">
                                            {formatCurrency(pr.subtotal)}
                                        </span>
                                    </p>
                                    <p>
                                        IVA:{' '}
                                        <span className="font-mono font-medium text-foreground">
                                            {formatCurrency(pr.iva)}
                                        </span>
                                    </p>
                                    <p>
                                        Retención:{' '}
                                        <span className="font-mono font-medium text-foreground">
                                            {formatCurrency(pr.retention)}
                                        </span>
                                    </p>
                                    <p className="border-t border-border pt-1 text-base">
                                        Total:{' '}
                                        <span className="font-mono text-lg font-bold text-foreground">
                                            {formatCurrency(pr.total)}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Información General</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="space-y-3 text-sm">
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Proveedor</dt>
                                    <dd className="font-medium text-foreground">{pr.provider}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Folio Factura</dt>
                                    <dd className="font-mono font-medium text-foreground">{pr.invoice_folio}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Moneda</dt>
                                    <dd className="font-medium text-foreground">{pr.currency?.name ?? '—'}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Sucursal</dt>
                                    <dd className="font-medium text-foreground">{pr.branch?.name ?? '—'}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Concepto de Gasto</dt>
                                    <dd className="font-medium text-foreground">{pr.expense_concept?.name ?? '—'}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Tipo de Pago</dt>
                                    <dd className="font-medium text-foreground">{pr.payment_type.label}</dd>
                                </div>
                                {pr.description && (
                                    <div>
                                        <dt className="mb-1 text-muted-foreground">Descripción</dt>
                                        <dd className="text-foreground">{pr.description}</dd>
                                    </div>
                                )}
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Solicitante</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="space-y-3 text-sm">
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Nombre</dt>
                                    <dd className="font-medium text-foreground">{pr.user?.name ?? '—'}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Departamento</dt>
                                    <dd className="font-medium text-foreground">{pr.department?.name ?? '—'}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Fecha de Creación</dt>
                                    <dd className="font-medium text-foreground">
                                        {new Date(pr.created_at).toLocaleDateString('es-MX', {
                                            day: '2-digit',
                                            month: 'long',
                                            year: 'numeric',
                                        })}
                                    </dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>
                </div>

                {pr.advance_documents && pr.advance_documents.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Documentos</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ul className="space-y-2">
                                {pr.advance_documents.map((doc, index) => {
                                    const filename = doc.split('/').pop() ?? doc;

                                    return (
                                        <li
                                            key={index}
                                            className="flex items-center gap-3 rounded-md border border-border px-3 py-2"
                                        >
                                            <Download className="size-4 shrink-0 text-muted-foreground" />
                                            <a
                                                href={`/storage/${doc}`}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="text-sm font-medium text-primary hover:underline"
                                            >
                                                {filename}
                                            </a>
                                        </li>
                                    );
                                })}
                            </ul>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Historial de Aprobaciones</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ApprovalTimeline approvals={pr.approvals ?? []} />
                    </CardContent>
                </Card>

                <div className="flex items-center justify-between">
                    <div>
                        {isEditable && (
                            <Button
                                variant="outline"
                                onClick={() => router.visit(`/payment-requests/${pr.id}/edit`)}
                            >
                                <Pencil className="size-4" />
                                Editar
                            </Button>
                        )}
                    </div>
                    {canApprove && (
                        <div className="flex gap-3">
                            <Button
                                variant="outline"
                                className="text-red-700 hover:bg-red-50 hover:text-red-800 dark:text-red-400 dark:hover:bg-red-950"
                                onClick={() => setRejectDialogOpen(true)}
                            >
                                <XCircle className="size-4" />
                                Rechazar
                            </Button>
                            <Button
                                className="bg-green-600 text-white hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600"
                                onClick={() => setApproveDialogOpen(true)}
                            >
                                <CheckCircle className="size-4" />
                                Autorizar
                            </Button>
                        </div>
                    )}
                </div>
            </div>

            <ConfirmationDialog
                open={approveDialogOpen}
                onOpenChange={setApproveDialogOpen}
                title="Aprobar solicitud"
                description="¿Estás seguro de que deseas aprobar esta solicitud de pago?"
                confirmLabel="Aprobar"
                onConfirm={handleApprove}
                processing={processing}
            />

            <ConfirmationDialog
                open={rejectDialogOpen}
                onOpenChange={(open) => {
                    setRejectDialogOpen(open);
                    if (!open) {
                        setRejectComments('');
                    }
                }}
                title="Rechazar solicitud"
                description="Indica el motivo del rechazo. Se notificará al solicitante."
                variant="destructive"
                confirmLabel="Rechazar"
                onConfirm={handleReject}
                processing={processing}
            >
                <div className="py-2">
                    <textarea
                        className="border-input focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:outline-none"
                        rows={3}
                        placeholder="Escribe los comentarios de rechazo (mínimo 10 caracteres)..."
                        value={rejectComments}
                        onChange={(e) => setRejectComments(e.target.value)}
                    />
                </div>
            </ConfirmationDialog>
        </>
    );
}
