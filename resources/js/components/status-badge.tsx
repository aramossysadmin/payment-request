import { cn } from '@/lib/utils';
import type { PaymentRequestStatus } from '@/types';

const colorMap: Record<PaymentRequestStatus['color'], string> = {
    warning:
        'bg-yellow-50 text-yellow-800 ring-yellow-600/20 dark:bg-yellow-400/10 dark:text-yellow-500 dark:ring-yellow-400/20',
    info: 'bg-blue-50 text-blue-700 ring-blue-700/10 dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/30',
    purple: 'bg-purple-50 text-purple-700 ring-purple-700/10 dark:bg-purple-400/10 dark:text-purple-400 dark:ring-purple-400/30',
    success:
        'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20',
    gray: 'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-gray-500/10 dark:text-gray-400 dark:ring-gray-500/20',
    danger: 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20',
    orange: 'bg-orange-50 text-orange-700 ring-orange-600/20 dark:bg-orange-500/10 dark:text-orange-400 dark:ring-orange-500/20',
};

const sizeMap = {
    sm: 'px-1.5 py-0.5 text-[10px]',
    md: 'px-2 py-1 text-xs',
    lg: 'px-2.5 py-1 text-sm',
};

export function StatusBadge({
    status,
    size = 'md',
    className,
}: {
    status: PaymentRequestStatus;
    size?: 'sm' | 'md' | 'lg';
    className?: string;
}) {
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-md font-medium ring-1 ring-inset',
                sizeMap[size],
                colorMap[status.color],
                className,
            )}
        >
            {status.label}
        </span>
    );
}
