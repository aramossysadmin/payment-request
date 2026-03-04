import { cn } from '@/lib/utils';
import { formatCurrency } from '@/lib/currency';
import { StatusBadge } from '@/components/status-badge';
import type { PaymentRequest } from '@/types';

type RequestListItemProps = {
    paymentRequest: PaymentRequest;
    isSelected: boolean;
    onClick: () => void;
};

export function RequestListItem({ paymentRequest: pr, isSelected, onClick }: RequestListItemProps) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'w-full text-left rounded-lg border px-3 py-3 transition-colors cursor-pointer',
                isSelected
                    ? 'bg-accent border-primary ring-1 ring-primary'
                    : 'bg-card border-transparent hover:bg-accent/50',
            )}
        >
            <div className="flex items-start justify-between gap-2">
                <span className="font-mono text-xs font-semibold text-primary">
                    #{String(pr.folio_number).padStart(5, '0')}
                </span>
                <StatusBadge status={pr.status} size="sm" />
            </div>
            <p className="mt-1 truncate text-sm font-medium text-foreground">
                {pr.provider}
            </p>
            <div className="mt-1 flex items-center justify-between gap-2">
                <span className="truncate text-xs text-muted-foreground">
                    {pr.user?.name ?? '—'}
                </span>
                <span className="shrink-0 font-mono text-sm font-bold text-foreground">
                    {formatCurrency(pr.total)}
                </span>
            </div>
        </button>
    );
}
