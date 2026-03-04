import type { Auth } from '@/types/auth';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            unreadNotificationsCount: number;
            notifications: Array<{
                id: string;
                title: string;
                body: string;
                status: 'warning' | 'danger' | 'success' | 'info';
                created_at: string;
            }>;
            flash: {
                success: string | null;
                error: string | null;
            };
            [key: string]: unknown;
        };
    }
}
