import { CheckCircle2, Circle, Clock, XCircle } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { PaymentRequestApproval } from '@/types';

const stageLabels: Record<string, string> = {
    department: 'Departamento',
    administration: 'Administración',
    treasury: 'Tesorería',
};

const statusConfig: Record<
    string,
    { icon: typeof Circle; color: string; label: string }
> = {
    pending: {
        icon: Clock,
        color: 'text-yellow-500 dark:text-yellow-400',
        label: 'Pendiente',
    },
    approved: {
        icon: CheckCircle2,
        color: 'text-green-500 dark:text-green-400',
        label: 'Aprobado',
    },
    rejected: {
        icon: XCircle,
        color: 'text-red-500 dark:text-red-400',
        label: 'Rechazado',
    },
};

export function ApprovalTimeline({
    approvals,
}: {
    approvals: PaymentRequestApproval[];
}) {
    if (approvals.length === 0) {
        return (
            <p className="text-sm text-gray-500 dark:text-gray-400">
                No hay aprobaciones registradas.
            </p>
        );
    }

    return (
        <div className="flow-root">
            <ul className="-mb-8">
                {approvals.map((approval, index) => {
                    const config = statusConfig[approval.status];
                    const Icon = config.icon;
                    const isLast = index === approvals.length - 1;

                    return (
                        <li key={approval.id}>
                            <div className="relative pb-8">
                                {!isLast && (
                                    <span
                                        aria-hidden="true"
                                        className="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700"
                                    />
                                )}
                                <div className="relative flex gap-3">
                                    <div>
                                        <span
                                            className={cn(
                                                'flex size-8 items-center justify-center rounded-full bg-white ring-4 ring-white dark:bg-gray-900 dark:ring-gray-900',
                                            )}
                                        >
                                            <Icon
                                                className={cn(
                                                    'size-5',
                                                    config.color,
                                                )}
                                            />
                                        </span>
                                    </div>
                                    <div className="flex min-w-0 flex-1 justify-between gap-4 pt-0.5">
                                        <div>
                                            <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {stageLabels[approval.stage] ??
                                                    approval.stage}
                                            </p>
                                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                                {approval.user.name} —{' '}
                                                <span
                                                    className={cn(
                                                        'font-medium',
                                                        config.color,
                                                    )}
                                                >
                                                    {config.label}
                                                </span>
                                            </p>
                                            {approval.comments && (
                                                <p className="mt-1 text-sm text-red-600 dark:text-red-400">
                                                    &ldquo;{approval.comments}
                                                    &rdquo;
                                                </p>
                                            )}
                                        </div>
                                        <div className="shrink-0 text-right text-xs whitespace-nowrap text-gray-500 dark:text-gray-400">
                                            {approval.responded_at
                                                ? new Date(
                                                      approval.responded_at,
                                                  ).toLocaleDateString(
                                                      'es-MX',
                                                      {
                                                          day: '2-digit',
                                                          month: 'short',
                                                          year: 'numeric',
                                                          hour: '2-digit',
                                                          minute: '2-digit',
                                                      },
                                                  )
                                                : 'En espera'}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}
