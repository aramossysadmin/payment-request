import { Clock, Info, Lock, X } from 'lucide-react';
import { useEffect, useState } from 'react';

import { usePaymentPolicy } from '@/hooks/use-payment-policy';

const DISMISS_KEY_PREFIX = 'payment-policy-banner-dismissed:';
const DISMISS_TTL_MS = 60 * 60 * 1000; // 1 hora

function getStateKey(payload: ReturnType<typeof usePaymentPolicy>): string | null {
    if (!payload) return null;

    return `${payload.capture.canAct}:${payload.capture.closesAt}:${payload.submit.isWindowActive}:${payload.submit.canAct}`;
}

export function PaymentPolicyBanner() {
    const policy = usePaymentPolicy();
    const stateKey = getStateKey(policy);
    const [dismissed, setDismissed] = useState(false);

    useEffect(() => {
        if (!stateKey) {
            setDismissed(false);

            return;
        }
        const storedAt = localStorage.getItem(`${DISMISS_KEY_PREFIX}${stateKey}`);
        if (!storedAt) {
            setDismissed(false);

            return;
        }
        const elapsed = Date.now() - Number(storedAt);
        setDismissed(elapsed < DISMISS_TTL_MS);
    }, [stateKey]);

    if (!policy || !stateKey || dismissed) {
        return null;
    }

    const handleDismiss = () => {
        localStorage.setItem(`${DISMISS_KEY_PREFIX}${stateKey}`, String(Date.now()));
        setDismissed(true);
    };

    // 3 estados visuales
    const captureOpen = policy.capture.canAct;
    let variant: 'open' | 'closed' | 'override' = captureOpen ? 'open' : 'closed';
    if (policy.isOverride && !policy.capture.isWindowActive) {
        return null; // ventana inactiva y user con override: no mostrar
    }
    if (policy.isOverride) {
        variant = 'override';
    }

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
                Ventana de captura abierta hasta <span className="font-semibold">{policy.capture.closesAtLabel}</span>
            </>
        ) : variant === 'override' ? (
            <>Tienes <span className="font-semibold">override activo</span> — puedes capturar/enviar fuera de ventana.</>
        ) : (
            <>
                Captura cerrada. Próxima apertura: <span className="font-semibold">{policy.capture.opensAtLabel}</span>
            </>
        );

    return (
        <div className={`flex items-center gap-3 border-b px-4 py-2 text-sm ${palette.bg} ${palette.border} ${palette.text}`}>
            {palette.icon}
            <div className="flex-1">{message}</div>
            <button
                type="button"
                onClick={handleDismiss}
                className="rounded p-1 opacity-60 hover:bg-black/5 hover:opacity-100 dark:hover:bg-white/10"
                title="Ocultar (1h)"
            >
                <X className="size-3.5" />
            </button>
        </div>
    );
}
