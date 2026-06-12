import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-react';
import type { ReactNode } from 'react';

import { cn } from '@/lib/utils';

export type SortDirection = 'asc' | 'desc' | null;

type SortableHeaderProps = {
    label: string;
    column: string;
    sortBy: { column: string; direction: 'asc' | 'desc' } | null;
    onSort: (column: string) => void;
    align?: 'left' | 'right' | 'center';
    /** Renderizar elementos adicionales después del label (ej. ColumnFilterPopover). */
    children?: ReactNode;
};

export function SortableHeader({ label, column, sortBy, onSort, align = 'left', children }: SortableHeaderProps) {
    const isActive = sortBy?.column === column;
    const direction = isActive ? sortBy.direction : null;

    const Icon = direction === 'asc' ? ArrowUp : direction === 'desc' ? ArrowDown : ArrowUpDown;

    return (
        <div
            className={cn(
                'inline-flex items-center gap-1',
                align === 'right' && 'justify-end',
                align === 'center' && 'justify-center',
            )}
        >
            <button
                type="button"
                onClick={() => onSort(column)}
                className={cn(
                    'inline-flex items-center gap-1 rounded px-1 py-0.5 transition-colors',
                    'hover:bg-muted/60',
                    isActive ? 'text-foreground font-semibold' : 'text-foreground',
                )}
                title={`Ordenar por ${label}`}
            >
                <span>{label}</span>
                <Icon className={cn('h-3 w-3', isActive ? 'text-primary' : 'text-muted-foreground')} />
            </button>
            {children}
        </div>
    );
}
