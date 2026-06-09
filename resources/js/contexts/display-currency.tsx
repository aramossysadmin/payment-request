import { usePage } from '@inertiajs/react';
import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import type { Currency } from '@/types/payment-request';

const STORAGE_KEY = 'display-currency';

type DisplayCurrencyContextValue = {
    currencies: Currency[];
    current: Currency | null;
    setCurrency: (id: number) => void;
    rateOf: (id: number | null | undefined) => number;
    convert: (amount: string | number | null, nativeId: number | null | undefined) => number;
    format: (amount: string | number | null, nativeId: number | null | undefined) => string;
    formatMxn: (amount: string | number | null) => string;
    nativeNote: (amount: string | number | null, nativeId: number | null | undefined) => string | null;
};

const DisplayCurrencyContext = createContext<DisplayCurrencyContextValue | null>(null);

function numberFmt(value: number): string {
    return value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

export function DisplayCurrencyProvider({ children }: { children: ReactNode }) {
    const page = usePage<{ currencies?: Currency[]; project?: { currency_id?: number | null } }>();
    const currencies = useMemo(() => page.props.currencies ?? [], [page.props.currencies]);
    const projectCurrencyId = page.props.project?.currency_id ?? null;

    const [currentId, setCurrentId] = useState<number | null>(null);

    useEffect(() => {
        if (currencies.length === 0) {
            return;
        }

        const stored = Number(localStorage.getItem(STORAGE_KEY));
        const storedValid = stored && currencies.some((c) => c.id === stored);
        const fallback = projectCurrencyId && currencies.some((c) => c.id === projectCurrencyId)
            ? projectCurrencyId
            : currencies[0].id;

        setCurrentId(storedValid ? stored : fallback);
    }, [currencies, projectCurrencyId]);

    const setCurrency = useCallback((id: number) => {
        setCurrentId(id);
        localStorage.setItem(STORAGE_KEY, String(id));
    }, []);

    const value = useMemo<DisplayCurrencyContextValue>(() => {
        const byId = (id: number | null | undefined) => currencies.find((c) => c.id === id) ?? null;
        const current = byId(currentId);
        const rateOf = (id: number | null | undefined) => Number(byId(id)?.exchange_rate) || 1;
        const displayRate = rateOf(current?.id);

        const convert = (amount: string | number | null, nativeId: number | null | undefined) => {
            const n = typeof amount === 'string' ? parseFloat(amount) : (amount ?? 0);
            if (Number.isNaN(n)) {
                return 0;
            }
            return (n * rateOf(nativeId)) / displayRate;
        };

        const format = (amount: string | number | null, nativeId: number | null | undefined) =>
            `${current?.prefix ?? '$'} $${numberFmt(convert(amount, nativeId))}`;

        const formatMxn = (amount: string | number | null) => {
            const n = typeof amount === 'string' ? parseFloat(amount) : (amount ?? 0);
            const safe = Number.isNaN(n) ? 0 : n;
            return `${current?.prefix ?? '$'} $${numberFmt(safe / displayRate)}`;
        };

        const nativeNote = (amount: string | number | null, nativeId: number | null | undefined) => {
            const native = byId(nativeId);
            if (!native || !current || native.id === current.id) {
                return null;
            }
            const n = typeof amount === 'string' ? parseFloat(amount) : (amount ?? 0);
            return `${native.prefix} $${numberFmt(Number.isNaN(n) ? 0 : n)}`;
        };

        return { currencies, current, setCurrency, rateOf, convert, format, formatMxn, nativeNote };
    }, [currencies, currentId, setCurrency]);

    return <DisplayCurrencyContext.Provider value={value}>{children}</DisplayCurrencyContext.Provider>;
}

export function useDisplayCurrency(): DisplayCurrencyContextValue {
    const ctx = useContext(DisplayCurrencyContext);
    if (!ctx) {
        throw new Error('useDisplayCurrency must be used within a DisplayCurrencyProvider');
    }
    return ctx;
}
