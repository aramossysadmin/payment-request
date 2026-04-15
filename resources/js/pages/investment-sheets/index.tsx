import { Head, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Inbox, Plus, Search, X } from 'lucide-react';
import { useCallback, useState } from 'react';
import { Pagination } from '@/components/pagination';
import { PaymentRequestDetail } from '@/components/payment-request-detail';
import { RequestListItem } from '@/components/request-list-item';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useIsMobile } from '@/hooks/use-mobile';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, PaginatedData } from '@/types';
import type { InvestmentRequest } from '@/types/investment-request';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Hojas de Inversión', href: '/investment-sheets' },
];

type PageProps = {
    investmentRequests: PaginatedData<InvestmentRequest>;
    canApproveIds: string[];
    approvalStages: Record<string, string>;
    canEditPurchaseInvoicesIds: string[];
    canEditVendorPaymentsIds: string[];
    filters: { search?: string; status?: string; status_group?: string };
};

export default function Index() {
    const { investmentRequests, canApproveIds, approvalStages, canEditPurchaseInvoicesIds, canEditVendorPaymentsIds, filters } =
        usePage<PageProps>().props;

    const isMobile = useIsMobile();
    const [selectedId, setSelectedId] = useState<string | null>(
        investmentRequests.data[0]?.uuid ?? null,
    );
    const [search, setSearch] = useState(filters.search ?? '');
    const [mobileView, setMobileView] = useState<'list' | 'detail'>('list');

    const selectedRequest = investmentRequests.data.find(
        (ir) => ir.uuid === selectedId,
    );
    const canApproveSelected = selectedId
        ? canApproveIds.includes(selectedId)
        : false;

    const applyFilters = useCallback(
        (params: Record<string, string>) => {
            const merged = { ...filters, ...params };
            const cleaned: Record<string, string> = {};
            for (const [key, value] of Object.entries(merged)) {
                if (value) {
                    cleaned[key] = value;
                }
            }
            router.get('/investment-sheets', cleaned, {
                preserveState: true,
                preserveScroll: true,
            });
        },
        [filters],
    );

    const handleSearch = useCallback(() => {
        applyFilters({ search });
    }, [search, applyFilters]);

    const handleClearSearch = useCallback(() => {
        setSearch('');
        applyFilters({ search: '' });
    }, [applyFilters]);

    const handleTabChange = useCallback(
        (value: string) => {
            applyFilters({
                status_group: value,
                status: '',
            });
        },
        [applyFilters],
    );

    const handleSelectItem = (id: string) => {
        setSelectedId(id);
        if (isMobile) {
            setMobileView('detail');
        }
    };

    const handleBackToList = () => {
        setMobileView('list');
    };

    const currentTab = filters.status_group || 'pending';

    const listPanel = (
        <div className="flex h-full flex-col">
            <div className="flex items-center justify-between px-4 pt-4 pb-2">
                <h1 className="text-lg font-bold tracking-tight text-foreground">
                    Inversiones
                </h1>
                <Button
                    size="sm"
                    onClick={() => router.visit('/investment-sheets/create')}
                >
                    <Plus className="size-4" />
                    Nueva
                </Button>
            </div>

            <div className="px-4 pb-2">
                <div className="relative">
                    <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        placeholder="Buscar por razón social, folio..."
                        className="pl-9 pr-8"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter') {
                                handleSearch();
                            }
                        }}
                    />
                    {search && (
                        <button
                            type="button"
                            onClick={handleClearSearch}
                            className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                        >
                            <X className="size-4" />
                        </button>
                    )}
                </div>
            </div>

            <div className="px-4 pb-2">
                <Tabs value={currentTab} onValueChange={handleTabChange}>
                    <TabsList className="w-full">
                        <TabsTrigger value="pending">Pendientes</TabsTrigger>
                        <TabsTrigger value="completed">Finalizados</TabsTrigger>
                        <TabsTrigger value="all">Todos</TabsTrigger>
                    </TabsList>
                </Tabs>
            </div>

            <div className="flex-1 space-y-1 overflow-y-auto px-4 pb-2">
                {investmentRequests.data.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-12 text-muted-foreground">
                        <Inbox className="mb-2 size-10" />
                        <p className="text-sm">No hay solicitudes</p>
                    </div>
                ) : (
                    investmentRequests.data.map((ir) => (
                        <RequestListItem
                            key={ir.uuid}
                            paymentRequest={ir as any}
                            isSelected={ir.uuid === selectedId}
                            onClick={() => handleSelectItem(ir.uuid)}
                        />
                    ))
                )}
            </div>

            <div className="border-t border-border px-4 py-2">
                <Pagination links={investmentRequests.meta.links} />
            </div>
        </div>
    );

    const detailPanel = (
        <div className="h-full overflow-y-auto p-6">
            {selectedRequest ? (
                <PaymentRequestDetail
                    paymentRequest={selectedRequest as any}
                    canApprove={canApproveSelected}
                    approvalStage={(selectedId ? approvalStages[selectedId] : null) as 'department' | 'administration' | 'treasury' | null}
                    canEditPurchaseInvoices={false}
                    canEditVendorPayments={false}
                    baseUrl="/investment-sheets"
                    showSapFolios={false}
                />
            ) : (
                <div className="flex h-full flex-col items-center justify-center text-muted-foreground">
                    <Inbox className="mb-3 size-16 opacity-30" />
                    <p className="text-lg font-medium">Selecciona una solicitud</p>
                    <p className="text-sm">
                        Elige una solicitud del panel izquierdo para ver sus detalles
                    </p>
                </div>
            )}
        </div>
    );

    if (isMobile) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Hojas de Inversión" />
                <div className="flex h-[calc(100vh-4rem)] flex-col">
                    {mobileView === 'list' ? (
                        listPanel
                    ) : (
                        <div className="flex h-full flex-col">
                            <div className="flex items-center gap-2 border-b border-border px-4 py-2">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={handleBackToList}
                                >
                                    <ArrowLeft className="size-4" />
                                </Button>
                                <span className="text-sm font-medium text-foreground">
                                    Volver a la lista
                                </span>
                            </div>
                            <div className="flex-1 overflow-y-auto">
                                {detailPanel}
                            </div>
                        </div>
                    )}
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Hojas de Inversión" />
            <div className="grid h-[calc(100vh-4rem)] grid-cols-[380px_1px_1fr]">
                <div className="overflow-hidden">
                    {listPanel}
                </div>
                <div className="bg-border" />
                <div className="overflow-hidden">
                    {detailPanel}
                </div>
            </div>
        </AppLayout>
    );
}
