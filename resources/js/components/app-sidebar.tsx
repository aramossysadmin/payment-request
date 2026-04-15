import { Link } from '@inertiajs/react';
import { Banknote, BookOpen, FileText, LayoutGrid } from 'lucide-react';
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
    {
        title: 'Hojas de Inversión',
        href: '/investment-sheets',
        icon: Banknote,
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
                <NavMain items={docsItems} label="Documentación" />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
