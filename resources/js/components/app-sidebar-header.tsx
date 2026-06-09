import { usePage } from '@inertiajs/react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { NotificationDropdown } from '@/components/notification-dropdown';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useDisplayCurrency } from '@/contexts/display-currency';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

function DisplayCurrencySelector() {
    const { component } = usePage();
    const { currencies, current, setCurrency } = useDisplayCurrency();

    if (component !== 'investment-sheets/consolidated' || currencies.length < 2 || !current) {
        return null;
    }

    return (
        <Select value={String(current.id)} onValueChange={(v) => setCurrency(Number(v))}>
            <SelectTrigger className="h-9 w-[100px]" aria-label="Moneda de visualización">
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                {currencies.map((c) => (
                    <SelectItem key={c.id} value={String(c.id)}>
                        {c.prefix}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    return (
        <header className="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex flex-1 items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <div className="flex items-center gap-2">
                <DisplayCurrencySelector />
                <NotificationDropdown />
            </div>
        </header>
    );
}
