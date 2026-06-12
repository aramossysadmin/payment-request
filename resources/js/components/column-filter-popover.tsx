import { Filter, X } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

type ColumnFilterPopoverProps = {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    /** Si se pasa, se renderiza un select con estas opciones en lugar de input text. */
    options?: { value: string; label: string }[];
};

export function ColumnFilterPopover({ value, onChange, placeholder, options }: ColumnFilterPopoverProps) {
    const [open, setOpen] = useState(false);
    const [draft, setDraft] = useState(value);
    const isActive = value !== '';

    const apply = () => {
        onChange(draft);
        setOpen(false);
    };

    const clear = () => {
        setDraft('');
        onChange('');
        setOpen(false);
    };

    return (
        <Popover
            open={open}
            onOpenChange={(o) => {
                setOpen(o);
                if (o) setDraft(value);
            }}
        >
            <PopoverTrigger asChild>
                <button
                    type="button"
                    onClick={(e) => e.stopPropagation()}
                    className={cn(
                        'ml-1 inline-flex h-5 w-5 items-center justify-center rounded transition-colors',
                        isActive
                            ? 'bg-primary text-primary-foreground hover:bg-primary/80'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                    )}
                    title={isActive ? `Filtro activo: "${value}"` : 'Filtrar columna'}
                >
                    <Filter className="h-3 w-3" />
                </button>
            </PopoverTrigger>
            <PopoverContent className="w-56 p-3" align="start" onClick={(e) => e.stopPropagation()}>
                <div className="space-y-2">
                    <p className="text-xs font-medium text-muted-foreground">Filtrar por esta columna</p>
                    {options ? (
                        <select
                            value={draft}
                            onChange={(e) => setDraft(e.target.value)}
                            className="border-input bg-background w-full rounded-md border px-2 py-1.5 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] focus-visible:outline-none"
                        >
                            <option value="">Todas</option>
                            {options.map((opt) => (
                                <option key={opt.value} value={opt.value}>
                                    {opt.label}
                                </option>
                            ))}
                        </select>
                    ) : (
                        <Input
                            value={draft}
                            onChange={(e) => setDraft(e.target.value)}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') apply();
                                if (e.key === 'Escape') setOpen(false);
                            }}
                            placeholder={placeholder ?? 'Buscar...'}
                            autoFocus
                            className="h-8 text-sm"
                        />
                    )}
                    <div className="flex justify-between gap-2">
                        <Button type="button" variant="ghost" size="sm" onClick={clear} className="h-7 px-2 text-xs">
                            <X className="mr-1 h-3 w-3" />
                            Limpiar
                        </Button>
                        <Button type="button" size="sm" onClick={apply} className="h-7 px-2 text-xs">
                            Aplicar
                        </Button>
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    );
}
