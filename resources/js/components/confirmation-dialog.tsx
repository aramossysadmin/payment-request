import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type ConfirmationDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description?: string;
    variant?: 'default' | 'destructive';
    confirmLabel?: string;
    cancelLabel?: string;
    onConfirm: () => void;
    processing?: boolean;
    children?: ReactNode;
};

export function ConfirmationDialog({
    open,
    onOpenChange,
    title,
    description,
    variant = 'default',
    confirmLabel = 'Confirmar',
    cancelLabel = 'Cancelar',
    onConfirm,
    processing = false,
    children,
}: ConfirmationDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    {description && (
                        <DialogDescription>{description}</DialogDescription>
                    )}
                </DialogHeader>

                {children}

                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                        disabled={processing}
                    >
                        {cancelLabel}
                    </Button>
                    <Button
                        variant={variant === 'destructive' ? 'destructive' : 'default'}
                        onClick={onConfirm}
                        disabled={processing}
                    >
                        {processing ? 'Procesando...' : confirmLabel}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
