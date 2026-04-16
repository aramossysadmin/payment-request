import { Head, router, usePage } from '@inertiajs/react';
import { ArrowLeft, CheckCircle, FileDown, Pencil, XCircle } from 'lucide-react';
import { useState } from 'react';
import { ApprovalTimeline } from '@/components/approval-timeline';
import { ConfirmationDialog } from '@/components/confirmation-dialog';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency } from '@/lib/currency';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import type { InvestmentRequest } from '@/types/investment-request';

type PageProps = {
    investmentRequest: { data: InvestmentRequest };
    canApprove: boolean;
    approvalStage: 'department' | 'administration' | 'treasury' | null;
    canEditPurchaseInvoices: boolean;
    canEditVendorPayments: boolean;
};

export default function Show() {
    const { investmentRequest: resource, canApprove, approvalStage } =
        usePage<PageProps>().props;
    const ir = resource.data;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Conceptos de Inversión', href: '/investment-sheets' },
        {
            title: `#${String(ir.folio_number).padStart(5, '0')}`,
            href: `/investment-sheets/${ir.uuid}`,
        },
    ];

    const [approveDialogOpen, setApproveDialogOpen] = useState(false);
    const [rejectDialogOpen, setRejectDialogOpen] = useState(false);
    const [rejectComments, setRejectComments] = useState('');
    const [processing, setProcessing] = useState(false);

    const isEditable = ir.status.name === 'pending_department';
    const isCompleted = ir.status.name === 'completed';

    const handleApprove = () => {
        setProcessing(true);
        router.post(`/investment-sheets/${ir.uuid}/approve`, {}, {
            onFinish: () => { setProcessing(false); setApproveDialogOpen(false); },
        });
    };

    const handleReject = () => {
        setProcessing(true);
        router.post(`/investment-sheets/${ir.uuid}/reject`, { comments: rejectComments }, {
            onFinish: () => { setProcessing(false); setRejectDialogOpen(false); setRejectComments(''); },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Concepto #${String(ir.folio_number).padStart(5, '0')}`} />

            <div className="mx-auto max-w-4xl space-y-6 p-4 md:p-6">
                <div className="flex items-center gap-3">
                    <Button variant="ghost" size="icon" onClick={() => router.visit('/investment-sheets')}>
                        <ArrowLeft className="size-4" />
                    </Button>
                    <h1 className="text-2xl font-bold tracking-tight text-foreground">
                        Concepto de Inversión
                    </h1>
                </div>

                {/* Header Card */}
                <Card className="border-l-4 border-l-primary">
                    <CardContent className="pt-6">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div className="space-y-2">
                                <div className="flex items-center gap-3">
                                    <p className="font-mono text-3xl font-bold text-foreground">
                                        #{String(ir.folio_number).padStart(5, '0')}
                                    </p>
                                    <StatusBadge status={ir.status} />
                                </div>
                                <p className="font-mono text-xs text-muted-foreground">{ir.uuid}</p>
                                {(isEditable || canApprove || isCompleted) && (
                                    <div className="flex gap-2 pt-1">
                                        {isCompleted && (
                                            <a href={`/investment-sheets/${ir.uuid}/pdf`} target="_blank" rel="noopener noreferrer">
                                                <Button variant="outline" size="sm">
                                                    <FileDown className="size-4" />
                                                    PDF
                                                </Button>
                                            </a>
                                        )}
                                        {isEditable && (
                                            <Button variant="outline" size="sm" onClick={() => router.visit(`/investment-sheets/${ir.uuid}/edit`)}>
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
                                    <p>Subtotal: <span className="font-mono font-medium text-foreground">{formatCurrency(ir.subtotal)}</span></p>
                                    <p>IVA ({ir.iva_rate.label}): <span className="font-mono font-medium text-foreground">{formatCurrency(ir.iva)}</span></p>
                                    <p>Retención: <span className="font-medium text-foreground">{ir.retention ? 'Sí' : 'No'}</span></p>
                                    <p className="border-t border-border pt-1 text-base">
                                        Total: <span className="font-mono text-lg font-bold text-foreground">{formatCurrency(ir.total)}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Info Cards */}
                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Información General</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="space-y-3 text-sm">
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Razón Social</dt>
                                    <dd className="font-medium text-foreground">{ir.provider}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">RFC</dt>
                                    <dd className="font-mono font-medium text-foreground">{ir.rfc ?? '—'}</dd>
                                </div>
                                {ir.contact_name && (
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">Contacto</dt>
                                        <dd className="font-medium text-foreground">{ir.contact_name}</dd>
                                    </div>
                                )}
                                {ir.contact_email && (
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">Correo Contacto</dt>
                                        <dd className="font-medium text-foreground">{ir.contact_email}</dd>
                                    </div>
                                )}
                                {ir.contact_phone && (
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">Teléfono Contacto</dt>
                                        <dd className="font-medium text-foreground">{ir.contact_phone}</dd>
                                    </div>
                                )}
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Folio Factura</dt>
                                    <dd className="font-mono font-medium text-foreground">{ir.invoice_folio ?? '—'}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Moneda</dt>
                                    <dd className="font-medium text-foreground">{ir.currency?.prefix ?? '—'}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Sucursal</dt>
                                    <dd className="font-medium text-foreground">{ir.branch?.name ?? '—'}</dd>
                                </div>
                                {ir.project && (
                                    <div className="flex justify-between">
                                        <dt className="text-muted-foreground">Proyecto</dt>
                                        <dd className="font-medium text-foreground">{ir.project.name}</dd>
                                    </div>
                                )}
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Concepto de Gasto</dt>
                                    <dd className="font-medium text-foreground">{ir.investment_expense_concept?.name ?? ir.expense_concept?.name ?? '—'}</dd>
                                </div>
                                {/* Tipo de Pago - oculto temporalmente
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Tipo de Pago</dt>
                                    <dd className="font-medium text-foreground">{ir.payment_type?.name ?? '—'}</dd>
                                </div>
                                */}
                                {ir.description && (
                                    <div>
                                        <dt className="mb-1 text-muted-foreground">Descripción</dt>
                                        <dd className="text-foreground">{ir.description}</dd>
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
                                    <dd className="font-medium text-foreground">{ir.user?.name ?? '—'}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Departamento</dt>
                                    <dd className="font-medium text-foreground">{ir.department?.name ?? '—'}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-muted-foreground">Fecha de Creación</dt>
                                    <dd className="font-medium text-foreground">
                                        {new Date(ir.created_at).toLocaleDateString('es-MX', {
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

                {/* Approval Timeline */}
                <Card>
                    <CardHeader>
                        <CardTitle>Historial de Aprobaciones</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ApprovalTimeline approvals={ir.approvals ?? []} />
                    </CardContent>
                </Card>
            </div>

            <ConfirmationDialog
                open={approveDialogOpen}
                onOpenChange={setApproveDialogOpen}
                title="Aprobar solicitud"
                description="¿Estás seguro de que deseas aprobar este concepto de inversión?"
                confirmLabel="Aprobar"
                onConfirm={handleApprove}
                processing={processing}
            />

            <ConfirmationDialog
                open={rejectDialogOpen}
                onOpenChange={(open) => { setRejectDialogOpen(open); if (!open) setRejectComments(''); }}
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
        </AppLayout>
    );
}
