import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { PaginationLink } from '@/types';

export function Pagination({ links }: { links: PaginationLink[] }) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <nav className="flex items-center justify-center gap-1">
            {links.map((link, index) => (
                <Link
                    key={index}
                    href={link.url ?? ''}
                    preserveScroll
                    preserveState
                    className={cn(
                        'inline-flex h-8 min-w-8 items-center justify-center rounded-md px-3 text-sm transition-colors',
                        link.active
                            ? 'bg-primary text-primary-foreground'
                            : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800',
                        !link.url && 'pointer-events-none opacity-50',
                    )}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                />
            ))}
        </nav>
    );
}
