import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import type { PaymentPolicyPayload } from '@/types/payment-policy';

type SharedProps = {
    paymentPolicy?: PaymentPolicyPayload;
};

/**
 * Acceso al payload de política de ventanas que el backend pasa como prop global.
 * Devuelve null si la prop no está presente (por ejemplo, en páginas que no lo pasan).
 */
export function usePaymentPolicy(): PaymentPolicyPayload | null {
    const { props } = usePage<SharedProps>();

    return props.paymentPolicy ?? null;
}

/**
 * Hook que devuelve un objeto con tiempo restante hasta `targetIso`.
 * Se actualiza cada segundo. Devuelve null si no hay target.
 */
export function useTimeRemaining(targetIso: string | null): {
    totalSeconds: number;
    minutes: number;
    seconds: number;
    formatted: string;
    hasPassed: boolean;
} | null {
    const [now, setNow] = useState(() => Date.now());

    useEffect(() => {
        if (!targetIso) {
            return;
        }
        const interval = setInterval(() => setNow(Date.now()), 1000);

        return () => clearInterval(interval);
    }, [targetIso]);

    if (!targetIso) {
        return null;
    }

    const target = new Date(targetIso).getTime();
    const totalSeconds = Math.max(0, Math.floor((target - now) / 1000));
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    const formatted = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

    return {
        totalSeconds,
        minutes,
        seconds,
        formatted,
        hasPassed: totalSeconds === 0,
    };
}
