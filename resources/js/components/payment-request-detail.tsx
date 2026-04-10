import { router } from '@inertiajs/react';
import { Check, CheckCircle, Download, Pencil, XCircle } from 'lucide-react';
import { useState } from 'react';
import { ApprovalTimeline } from '@/components/approval-timeline';
import { ConfirmationDialog } from '@/components/confirmation-dialog';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatCurrency } from '@/lib/currency';
import type { PaymentRequest } from '@/types';

type PaymentRequestDetailProps = {
    paymentRequest: PaymentRequest;
    canApprove: boolean;
    approvalStage?: 'department' | 'administration' | 'treasury' | null;
    canEditPurchaseInvoices?: boolean;
    canEditVendorPayments?: boolean;
    baseUrl?: string;
};

export function PaymentRequestDetail({
    paymentRequest: pr,
    canApprove,
    approvalStage = null,
    canEditPurchaseInvoices = false,
    canEditVendorPayments = false,
    baseUrl = '/payment-requests',
}: PaymentRequestDetailProps) {
    const [approveDialogOpen, setApproveDialogOpen] = useState(false);
    const [rejectDialogOpen, setRejectDialogOpen] = useState(false);
    const [rejectComments, setRejectComments] = useState('');
    const [processing, setProcessing] = useState(false);

    const [sapFieldValue, setSapFieldValue] = useState('');

    const [editPurchaseInvoices, setEditPurchaseInvoices] = useState(false);
    const [editVendorPayments, setEditVendorPayments] = useState(false);
    const [purchaseInvoicesValue, setPurchaseInvoicesValue] = useState(
        pr.number_purchase_invoices?.toString() ?? '',
    );
    const [vendorPaymentsValue, setVendorPaymentsValue] = useState(
        pr.number_vendor_payments?.toString() ?? '',
    );
    const [savingSapFolios, setSavingSapFolios] = useState(false);

    const handleApprove = () => {
        setProcessing(true);

        const data: Record<string, string> = {};
        if (approvalStage === 'administration' && sapFieldValue) {
            data.number_purchase_invoices = sapFieldValue;
        } else if (approvalStage === 'treasury' && sapFieldValue) {
            data.number_vendor_payments = sapFieldValue;
        }

        router.post(`${baseUrl}/${pr.uuid}/approve`, data, {
            onFinish: () => {
                setProcessing(false);
                setApproveDialogOpen(false);
                setSapFieldValue('');
            },
        });
    };

    const handleReject = () => {
        setProcessing(true);
        router.post(
            `${baseUrl}/${pr.uuid}/reject`,
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

    const handleSaveSapFolio = (field: 'number_purchase_invoices' | 'number_vendor_payments') => {
        setSavingSapFolios(true);
        const value = field === 'number_purchase_invoices' ? purchaseInvoicesValue : vendorPaymentsValue;

        router.patch(
            `${baseUrl}/${pr.uuid}/sap-folios`,
            { [field]: value || null },
            {
                onFinish: () => {
                    setSavingSapFolios(false);
                    if (field === 'number_purchase_invoices') {
                        setEditPurchaseInvoices(false);
                    } else {
                        setEditVendorPayments(false);
                    }
                },
            },
        );
    };

    const isEditable = pr.status.name === 'pending_department';
    const showSapSection = pr.status.name !== 'pending_department';

    const showSapFieldInModal = approvalStage === 'administration' || approvalStage === 'treasury';

    return (
        <>
            <div className="space-y-6">
                <Card className="border-l-4 border-l-primary">
                    <CardContent className="pt-6">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div className="space-y-2">
                                <div className="flex items-center gap-3">
                                    <p className="font-mono text-3xl font-bold text-foreground">
                                        #{String(pr.folio_number).padStart(5, '0')}
                                    </p>
                                    <StatusBadge status={pr.status} />
                                </div>
                                <p className="font-mono text-xs text-muted-foreground">
                                    {pr.uuid}
                                </p>
                                {(isEditable || canApprove) && (
                                    <div className="flex gap-2 pt-1">
                                        {isEditable && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => router.visit(`${baseUrl}/${pr.uuid}/edit`)}
                                            >
                                                <Pencil className="size-4" />
                                                Editar
                                            </Button>
                                        )}
                                        {canApprove && (
                                            <>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="text-red-700 hover:bg-red-50 hover:text-red-800 dark:text-red-400 dark:hover:bg-red-950"
                                                    onClick={() => setRejectDialogOpen(true)}
                                                >
                                                    <XCircle className="size-4" />
                                                    Rechazar
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    className="bg-green-600 text-white hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600"
                                                    onClick={() => setApproveDialogOpen(true)}
                                                >
                                                    <CheckCircle className="size-4" />
                                                    Autorizar
                                                </Button>
                                            </>
                                        )}
                                    </div>
                                )}
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
                                        IVA ({pr.iva_rate.label}):{' '}
                                        <span className="font-mono font-medium text-foreground">
                                            {formatCurrency(pr.iva)}
                                        </span>
                                    </p>
                                    <p>
                                        Retención:{' '}
                                        <span className="font-medium text-foreground">
                                            {pr.retention ? 'Sí' : 'No'}
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
                                    <dt className="text-muted-foreground">Razón Social</dt>
                                    <dd className="font-medium text-foreground">{pr.provider}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">RFC</dt>
                                    <dd className="font-mono font-medium text-foreground">{pr.rfc ?? '—'}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Folio Factura</dt>
                                    <dd className="font-mono font-medium text-foreground">{pr.invoice_folio}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Moneda</dt>
                                    <dd className="font-medium text-foreground">{pr.currency?.prefix ?? '—'}</dd>
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
                                    <dd className="font-medium text-foreground">{pr.payment_type?.name ?? '—'}</dd>
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

                {showSapSection && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Folios SAP</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="space-y-4 text-sm">
                                <div className="flex items-center justify-between">
                                    <dt className="text-muted-foreground">Folio SAP Factura Proveedores</dt>
                                    <dd className="font-mono font-medium text-foreground">
                                        {editPurchaseInvoices ? (
                                            <div className="flex items-center gap-2">
                                                <Input
                                                    type="number"
                                                    min={1}
                                                    value={purchaseInvoicesValue}
                                                    onChange={(e) => setPurchaseInvoicesValue(e.target.value)}
                                                    className="h-8 w-32"
                                                />
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    disabled={savingSapFolios}
                                                    onClick={() => handleSaveSapFolio('number_purchase_invoices')}
                                                >
                                                    <Check className="size-4" />
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    disabled={savingSapFolios}
                                                    onClick={() => {
                                                        setEditPurchaseInvoices(false);
                                                        setPurchaseInvoicesValue(pr.number_purchase_invoices?.toString() ?? '');
                                                    }}
                                                >
                                                    <XCircle className="size-4" />
                                                </Button>
                                            </div>
                                        ) : (
                                            <span className="flex items-center gap-2">
                                                {pr.number_purchase_invoices ?? '—'}
                                                {canEditPurchaseInvoices && (
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() => setEditPurchaseInvoices(true)}
                                                    >
                                                        <Pencil className="size-3" />
                                                    </Button>
                                                )}
                                            </span>
                                        )}
                                    </dd>
                                </div>
                                <div className="flex items-center justify-between">
                                    <dt className="text-muted-foreground">Folio SAP Pago Efectuado</dt>
                                    <dd className="font-mono font-medium text-foreground">
                                        {editVendorPayments ? (
                                            <div className="flex items-center gap-2">
                                                <Input
                                                    type="number"
                                                    min={1}
                                                    value={vendorPaymentsValue}
                                                    onChange={(e) => setVendorPaymentsValue(e.target.value)}
                                                    className="h-8 w-32"
                                                />
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    disabled={savingSapFolios}
                                                    onClick={() => handleSaveSapFolio('number_vendor_payments')}
                                                >
                                                    <Check className="size-4" />
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="ghost"
                                                    disabled={savingSapFolios}
                                                    onClick={() => {
                                                        setEditVendorPayments(false);
                                                        setVendorPaymentsValue(pr.number_vendor_payments?.toString() ?? '');
                                                    }}
                                                >
                                                    <XCircle className="size-4" />
                                                </Button>
                                            </div>
                                        ) : (
                                            <span className="flex items-center gap-2">
                                                {pr.number_vendor_payments ?? '—'}
                                                {canEditVendorPayments && (
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() => setEditVendorPayments(true)}
                                                    >
                                                        <Pencil className="size-3" />
                                                    </Button>
                                                )}
                                            </span>
                                        )}
                                    </dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>
                )}

                {pr.advance_documents && pr.advance_documents.filter((doc): doc is string => typeof doc === 'string' && doc.length > 0).length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Documentos Solicitudes de Pago</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ul className="space-y-2">
                                {pr.advance_documents.filter((doc): doc is string => typeof doc === 'string' && doc.length > 0).map((doc, index) => {
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

            </div>

            <ConfirmationDialog
                open={approveDialogOpen}
                onOpenChange={setApproveDialogOpen}
                title="Aprobar solicitud"
                description="¿Estás seguro de que deseas aprobar esta solicitud de pago?"
                confirmLabel="Aprobar"
                onConfirm={handleApprove}
                processing={processing}
            >
                {showSapFieldInModal && (
                    <div className="space-y-2 py-2">
                        <Label htmlFor="sap-field">
                            {approvalStage === 'administration'
                                ? 'Folio SAP Factura Proveedores'
                                : 'Folio SAP Pago Efectuado'}
                        </Label>
                        <Input
                            id="sap-field"
                            type="number"
                            min={1}
                            placeholder="Opcional"
                            value={sapFieldValue}
                            onChange={(e) => setSapFieldValue(e.target.value)}
                        />
                    </div>
                )}
            </ConfirmationDialog>

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
