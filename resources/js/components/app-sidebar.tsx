import { Link } from '@inertiajs/react';
import { Banknote, BarChart3, BookOpen, CalendarCheck, ClipboardList, FileText, LayoutGrid } from 'lucide-react';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';
import AppLogo from './app-logo';

const platformItems: NavItem[] = [
    {
        title: 'Panel',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const requestItems: NavItem[] = [
    {
        title: 'Solicitudes de Pago',
        href: '/payment-requests',
        icon: FileText,
    },
];

const investmentItems: NavItem[] = [
    {
        title: 'Conceptos de Inversión',
        href: '/investment-sheets',
        icon: Banknote,
    },
    {
        title: 'Hojas de Inversión',
        href: '/investment-sheets/consolidated',
        icon: ClipboardList,
    },
    {
        title: 'Control Presupuestal',
        href: '/investment-dashboard',
        icon: BarChart3,
    },
];

const treasuryItems: NavItem[] = [
    {
        title: 'Programación de Pagos',
        href: '/weekly-payment-schedule',
        icon: CalendarCheck,
    },
];

const docsItems: NavItem[] = [
    {
        title: 'Guía de Usuario',
        href: '/guide',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={platformItems} label="Plataforma" />
                <NavMain items={requestItems} label="Solicitudes" />
                <NavMain items={investmentItems} label="Presupuestos de Inversión" />
                <NavMain items={treasuryItems} label="Tesorería" />
                <NavMain items={docsItems} label="Documentación" />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
