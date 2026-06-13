import { ChevronLeft, ChevronRight } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type NumberedPaginationProps = {
    currentPage: number;
    totalPages: number;
    onPageChange: (page: number) => void;
    siblingCount?: number;
};

type PageItem = number | 'ellipsis-left' | 'ellipsis-right';

/**
 * Construye la lista de items a mostrar.
 * Con siblingCount=2 y page=5 de 10 produce: 1 … 3 4 5 6 7 … 10.
 *
 * Reglas:
 * - Si totalPages <= siblingCount*2 + 5 → mostrar todas.
 * - Si la actual está cerca del inicio → "1 ... last_visible … last".
 * - Si la actual está cerca del final → "1 … first_visible ... last".
 * - Si está al medio → "1 … current-N current … current+N … last".
 */
function buildPageItems(currentPage: number, totalPages: number, siblingCount: number): PageItem[] {
    const totalNumbersToShow = siblingCount * 2 + 5;

    if (totalPages <= totalNumbersToShow) {
        return Array.from({ length: totalPages }, (_, i) => i + 1);
    }

    const leftSibling = Math.max(currentPage - siblingCount, 1);
    const rightSibling = Math.min(currentPage + siblingCount, totalPages);
    const showLeftEllipsis = leftSibling > 2;
    const showRightEllipsis = rightSibling < totalPages - 1;

    const items: PageItem[] = [];

    if (!showLeftEllipsis && showRightEllipsis) {
        const leftItemCount = 3 + 2 * siblingCount;
        for (let i = 1; i <= leftItemCount; i++) items.push(i);
        items.push('ellipsis-right');
        items.push(totalPages);
    } else if (showLeftEllipsis && !showRightEllipsis) {
        items.push(1);
        items.push('ellipsis-left');
        const rightItemCount = 3 + 2 * siblingCount;
        for (let i = totalPages - rightItemCount + 1; i <= totalPages; i++) items.push(i);
    } else if (showLeftEllipsis && showRightEllipsis) {
        items.push(1);
        items.push('ellipsis-left');
        for (let i = leftSibling; i <= rightSibling; i++) items.push(i);
        items.push('ellipsis-right');
        items.push(totalPages);
    } else {
        for (let i = 1; i <= totalPages; i++) items.push(i);
    }

    return items;
}

export function NumberedPagination({ currentPage, totalPages, onPageChange, siblingCount = 2 }: NumberedPaginationProps) {
    if (totalPages <= 1) return null;

    const items = buildPageItems(currentPage, totalPages, siblingCount);

    const goTo = (page: number) => {
        if (page >= 1 && page <= totalPages && page !== currentPage) {
            onPageChange(page);
        }
    };

    return (
        <div className="flex items-center gap-1">
            <Button
                variant="outline"
                size="icon"
                className="h-8 w-8"
                disabled={currentPage === 1}
                onClick={() => goTo(currentPage - 1)}
                aria-label="Página anterior"
            >
                <ChevronLeft className="h-4 w-4" />
            </Button>

            {items.map((item, idx) => {
                if (item === 'ellipsis-left' || item === 'ellipsis-right') {
                    return (
                        <span
                            key={`${item}-${idx}`}
                            className="inline-flex h-8 w-6 items-center justify-center text-xs text-muted-foreground select-none"
                            aria-hidden
                        >
                            …
                        </span>
                    );
                }
                const isActive = item === currentPage;
                return (
                    <Button
                        key={item}
                        variant={isActive ? 'default' : 'outline'}
                        size="icon"
                        className={cn('h-8 w-8 text-xs font-medium', isActive && 'pointer-events-none')}
                        onClick={() => goTo(item)}
                        aria-label={`Ir a página ${item}`}
                        aria-current={isActive ? 'page' : undefined}
                    >
                        {item}
                    </Button>
                );
            })}

            <Button
                variant="outline"
                size="icon"
                className="h-8 w-8"
                disabled={currentPage === totalPages}
                onClick={() => goTo(currentPage + 1)}
                aria-label="Página siguiente"
            >
                <ChevronRight className="h-4 w-4" />
            </Button>
        </div>
    );
}
