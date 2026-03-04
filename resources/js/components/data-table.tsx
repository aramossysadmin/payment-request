import { router } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

export type Column<T> = {
    key: string;
    label: string;
    className?: string;
    render: (item: T) => ReactNode;
};

type DataTableProps<T> = {
    columns: Column<T>[];
    data: T[];
    keyExtractor: (item: T) => string | number;
    onRowClick?: (item: T) => string;
    emptyMessage?: string;
    actions?: (item: T) => ReactNode;
};

export function DataTable<T>({
    columns,
    data,
    keyExtractor,
    onRowClick,
    emptyMessage = 'No se encontraron registros.',
    actions,
}: DataTableProps<T>) {
    return (
        <div className="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead className="bg-gray-50 dark:bg-gray-800/50">
                    <tr>
                        {columns.map((col) => (
                            <th
                                key={col.key}
                                className={cn(
                                    'px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400',
                                    col.className,
                                )}
                            >
                                {col.label}
                            </th>
                        ))}
                        {actions && (
                            <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Acciones
                            </th>
                        )}
                    </tr>
                </thead>
                <tbody className="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                    {data.length === 0 ? (
                        <tr>
                            <td
                                colSpan={columns.length + (actions ? 1 : 0)}
                                className="px-4 py-12 text-center text-sm text-gray-500 dark:text-gray-400"
                            >
                                {emptyMessage}
                            </td>
                        </tr>
                    ) : (
                        data.map((item) => (
                            <tr
                                key={keyExtractor(item)}
                                className={cn(
                                    'transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/50',
                                    onRowClick && 'cursor-pointer',
                                )}
                                onClick={() => {
                                    if (onRowClick) {
                                        router.visit(onRowClick(item));
                                    }
                                }}
                            >
                                {columns.map((col) => (
                                    <td
                                        key={col.key}
                                        className={cn(
                                            'whitespace-nowrap px-4 py-3 text-sm text-gray-900 dark:text-gray-100',
                                            col.className,
                                        )}
                                    >
                                        {col.render(item)}
                                    </td>
                                ))}
                                {actions && (
                                    <td
                                        className="whitespace-nowrap px-4 py-3 text-right text-sm"
                                        onClick={(e) => e.stopPropagation()}
                                    >
                                        {actions(item)}
                                    </td>
                                )}
                            </tr>
                        ))
                    )}
                </tbody>
            </table>
        </div>
    );
}
