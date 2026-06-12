import { Clock, Info, Lock } from 'lucide-react';

import { usePaymentPolicy } from '@/hooks/use-payment-policy';
import { formatPolicyTime } from '@/lib/format-policy-time';

export function PaymentPolicyBanner() {
    const policy = usePaymentPolicy();

    if (!policy) {
        return null;
    }

    // Si la ventana está inactiva y el user tiene override, no mostrar (no aporta información).
    if (policy.isOverride && !policy.capture.isWindowActive) {
        return null;
    }

    const captureOpen = policy.capture.canAct;
    const variant: 'open' | 'closed' | 'override' = policy.isOverride
        ? 'override'
        : captureOpen
            ? 'open'
            : 'closed';

    const palette = {
        open: {
            bg: 'bg-emerald-50 dark:bg-emerald-950/30',
            border: 'border-emerald-200 dark:border-emerald-800',
            text: 'text-emerald-900 dark:text-emerald-100',
            icon: <Clock className="size-4 text-emerald-600 dark:text-emerald-400" />,
        },
        closed: {
            bg: 'bg-amber-50 dark:bg-amber-950/30',
            border: 'border-amber-200 dark:border-amber-800',
            text: 'text-amber-900 dark:text-amber-100',
            icon: <Lock className="size-4 text-amber-600 dark:text-amber-400" />,
        },
        override: {
            bg: 'bg-sky-50 dark:bg-sky-950/30',
            border: 'border-sky-200 dark:border-sky-800',
            text: 'text-sky-900 dark:text-sky-100',
            icon: <Info className="size-4 text-sky-600 dark:text-sky-400" />,
        },
    }[variant];

    const message =
        variant === 'open' ? (
            <>
                Ventana de captura abierta hasta <span className="font-semibold">{formatPolicyTime(policy.capture.closesAt)}</span>
            </>
        ) : variant === 'override' ? (
            <>Tienes <span className="font-semibold">override activo</span> — puedes capturar/enviar fuera de ventana.</>
        ) : (
            <>
                Captura cerrada. Próxima apertura: <span className="font-semibold">{formatPolicyTime(policy.capture.opensAt)}</span>
            </>
        );

    return (
        <div className={`flex items-center gap-3 border-b px-4 py-2 text-sm ${palette.bg} ${palette.border} ${palette.text}`}>
            {palette.icon}
            <div className="flex-1">{message}</div>
        </div>
    );
}
