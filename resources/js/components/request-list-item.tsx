import { cn } from '@/lib/utils';
import { formatCurrency } from '@/lib/currency';
import { StatusBadge } from '@/components/status-badge';
import type { PaymentRequest } from '@/types';

type RequestListItemProps = {
    paymentRequest: PaymentRequest;
    isSelected: boolean;
    isInvestment?: boolean;
    onClick: () => void;
};

export function RequestListItem({ paymentRequest: pr, isSelected, isInvestment, onClick }: RequestListItemProps) {
    const ir = pr as any;

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
            {isInvestment && (
                <div className="mt-1.5 space-y-0.5">
                    {ir.project?.name && (
                        <p className="truncate text-xs text-muted-foreground">
                            <span className="font-medium text-foreground/70">Proyecto:</span> {ir.project.name}
                        </p>
                    )}
                    <p className="truncate text-xs text-muted-foreground">
                        <span className="font-medium text-foreground/70">Concepto:</span> {ir.investment_expense_concept?.name ?? ir.expense_concept?.name ?? '—'}
                    </p>
                    {ir.description && (
                        <p className="line-clamp-2 text-xs text-muted-foreground italic">{ir.description}</p>
                    )}
                </div>
            )}
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
