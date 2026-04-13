import { Head, router, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { PaymentRequestDetail } from '@/components/payment-request-detail';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, PaymentRequest } from '@/types';

type PageProps = {
    investmentRequest: { data: PaymentRequest };
    canApprove: boolean;
    approvalStage: 'department' | 'administration' | 'treasury' | null;
    canEditPurchaseInvoices: boolean;
    canEditVendorPayments: boolean;
};

export default function Show() {
    const { investmentRequest: resource, canApprove, approvalStage, canEditPurchaseInvoices, canEditVendorPayments } =
        usePage<PageProps>().props;
    const pr = resource.data;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Solicitudes de Inversión', href: '/investment-requests' },
        {
            title: `#${String(pr.folio_number).padStart(5, '0')}`,
            href: `/investment-requests/${pr.uuid}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`Solicitud #${String(pr.folio_number).padStart(5, '0')}`}
            />

            <div className="mx-auto max-w-4xl space-y-6 p-4 md:p-6">
                <div className="flex items-center gap-3">
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => router.visit('/investment-requests')}
                    >
                        <ArrowLeft className="size-4" />
                    </Button>
                    <h1 className="text-2xl font-bold tracking-tight text-foreground">
                        Solicitud de Inversión
                    </h1>
                </div>

                <PaymentRequestDetail
                    paymentRequest={pr}
                    canApprove={canApprove}
                    approvalStage={approvalStage}
                    canEditPurchaseInvoices={false}
                    canEditVendorPayments={false}
                    baseUrl="/investment-requests"
                    showSapFolios={false}
                />
            </div>
        </AppLayout>
    );
}
