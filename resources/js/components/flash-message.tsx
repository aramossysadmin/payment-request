import { usePage } from '@inertiajs/react';
import { CheckCircle2, XCircle } from 'lucide-react';
import { useEffect, useState } from 'react';
import { cn } from '@/lib/utils';

export function FlashMessage() {
    const { flash } = usePage().props;
    const [visible, setVisible] = useState(false);
    const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    useEffect(() => {
        if (flash.success) {
            setMessage({ type: 'success', text: flash.success });
            setVisible(true);
        } else if (flash.error) {
            setMessage({ type: 'error', text: flash.error });
            setVisible(true);
        }
    }, [flash.success, flash.error]);

    useEffect(() => {
        if (!visible) {
            return;
        }

        const timer = setTimeout(() => setVisible(false), 5000);

        return () => clearTimeout(timer);
    }, [visible]);

    if (!visible || !message) {
        return null;
    }

    return (
        <div
            className={cn(
                'mx-4 mt-4 flex items-center gap-3 rounded-lg border px-4 py-3 text-sm shadow-sm',
                message.type === 'success'
                    ? 'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200'
                    : 'border-red-200 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200',
            )}
        >
            {message.type === 'success' ? (
                <CheckCircle2 className="size-4 shrink-0" />
            ) : (
                <XCircle className="size-4 shrink-0" />
            )}
            <p>{message.text}</p>
            <button
                type="button"
                onClick={() => setVisible(false)}
                className="ml-auto text-current opacity-50 hover:opacity-100"
            >
                &times;
            </button>
        </div>
    );
}
