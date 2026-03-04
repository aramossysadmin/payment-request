import { router, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    Bell,
    CheckCircle2,
    Info,
    XCircle,
} from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';

const statusConfig = {
    warning: {
        icon: AlertTriangle,
        className: 'text-amber-500',
    },
    danger: {
        icon: XCircle,
        className: 'text-red-500',
    },
    success: {
        icon: CheckCircle2,
        className: 'text-green-500',
    },
    info: {
        icon: Info,
        className: 'text-blue-500',
    },
};

export function NotificationDropdown() {
    const { unreadNotificationsCount, notifications } = usePage().props;

    const handleMarkAsRead = (id: string) => {
        router.post(`/notifications/${id}/read`, {}, { preserveScroll: true });
    };

    const handleMarkAllAsRead = () => {
        router.post('/notifications/read-all', {}, { preserveScroll: true });
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <button
                    type="button"
                    className="relative rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                >
                    <Bell className="size-5" />
                    {unreadNotificationsCount > 0 && (
                        <span className="absolute -top-0.5 -right-0.5 flex size-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white">
                            {unreadNotificationsCount > 99
                                ? '99+'
                                : unreadNotificationsCount}
                        </span>
                    )}
                </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-80">
                <DropdownMenuLabel className="flex items-center justify-between">
                    <span>Notificaciones</span>
                    {unreadNotificationsCount > 0 && (
                        <button
                            type="button"
                            onClick={handleMarkAllAsRead}
                            className="text-xs font-normal text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                        >
                            Marcar todas como leídas
                        </button>
                    )}
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                {notifications.length === 0 ? (
                    <div className="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                        No tienes notificaciones pendientes
                    </div>
                ) : (
                    notifications.map((notification) => {
                        const config =
                            statusConfig[notification.status] ??
                            statusConfig.info;
                        const Icon = config.icon;

                        return (
                            <DropdownMenuItem
                                key={notification.id}
                                className="flex items-start gap-3 px-3 py-2.5"
                                onClick={() =>
                                    handleMarkAsRead(notification.id)
                                }
                            >
                                <Icon
                                    className={cn(
                                        'mt-0.5 size-5 shrink-0',
                                        config.className,
                                    )}
                                />
                                <div className="min-w-0 flex-1">
                                    <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {notification.title}
                                    </p>
                                    <p className="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">
                                        {notification.body}
                                    </p>
                                    <p className="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                        {notification.created_at}
                                    </p>
                                </div>
                            </DropdownMenuItem>
                        );
                    })
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
