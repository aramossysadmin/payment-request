import { CalendarCheck, Check, ChevronLeft, ChevronRight, ChevronsUpDown } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

export interface WeekOption {
    week: number;
    year: number;
    paymentsCount: number;
}

interface WeekNavigatorProps {
    week: number;
    year: number;
    currentWeek: number;
    currentYear: number;
    onNavigate: (direction: number) => void;
    weekOptions?: WeekOption[];
    onSelect?: (week: number, year: number) => void;
}

const optionValue = (week: number, year: number): string => `Semana ${week} ${year}`;

/** Busca por el inicio del número de semana o por el año completo, para que "20" no coincida con todas las semanas de 2026. */
const matchesWeekSearch = (value: string, search: string): number => {
    const term = search.trim().toLowerCase().replace(/^semana\s*/, '');
    const [, week, year] = value.split(' ');

    return term === '' || week.startsWith(term) || year === term ? 1 : 0;
};

/** La semana actual y las anteriores van primero en orden descendente, por año; las futuras quedan al final en orden ascendente. */
const groupWeekOptions = (options: WeekOption[], currentWeek: number, currentYear: number): Array<{ heading: string; options: WeekOption[]; isUpcoming: boolean }> => {
    const isUpcoming = (option: WeekOption): boolean => option.year > currentYear || (option.year === currentYear && option.week > currentWeek);
    const elapsed = options.filter((option) => !isUpcoming(option)).sort((a, b) => b.year - a.year || b.week - a.week);
    const upcoming = options.filter(isUpcoming).sort((a, b) => a.year - b.year || a.week - b.week);

    const groups = [...new Set(elapsed.map((option) => option.year))].map((year) => ({
        heading: String(year),
        options: elapsed.filter((option) => option.year === year),
        isUpcoming: false,
    }));

    return upcoming.length > 0 ? [...groups, { heading: 'Próximas semanas', options: upcoming, isUpcoming: true }] : groups;
};

function CurrentBadge() {
    return <Badge className="bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">Actual</Badge>;
}

export function WeekNavigator({ week, year, currentWeek, currentYear, onNavigate, weekOptions, onSelect }: WeekNavigatorProps) {
    const [open, setOpen] = useState(false);
    const isCurrent = week === currentWeek && year === currentYear;
    const weekGroups = groupWeekOptions(weekOptions ?? [], currentWeek, currentYear);

    return (
        <div className="flex items-center gap-2">
            <Button variant="outline" size="icon" onClick={() => onNavigate(-1)}>
                <ChevronLeft className="h-4 w-4" />
            </Button>
            {weekOptions && onSelect ? (
                <Popover open={open} onOpenChange={setOpen}>
                    <PopoverTrigger asChild>
                        <Button variant="outline" role="combobox" aria-expanded={open} className="w-60 justify-between">
                            <span className="flex items-center gap-2">
                                <CalendarCheck className="h-4 w-4 text-gray-500" />
                                <span className="text-sm font-medium">
                                    Semana {week} / {year}
                                </span>
                                {isCurrent && <CurrentBadge />}
                            </span>
                            <ChevronsUpDown className="h-4 w-4 shrink-0 opacity-50" />
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent className="w-72 p-0" align="end">
                        <Command defaultValue={optionValue(week, year)} filter={matchesWeekSearch}>
                            <CommandInput placeholder="Buscar semana..." />
                            <CommandList>
                                <CommandEmpty>No hay semanas con ese número.</CommandEmpty>
                                {weekGroups.map((group) => (
                                    <CommandGroup key={group.heading} heading={group.heading}>
                                        {group.options.map((option) => (
                                            <CommandItem
                                                key={optionValue(option.week, option.year)}
                                                value={optionValue(option.week, option.year)}
                                                onSelect={() => {
                                                    onSelect(option.week, option.year);
                                                    setOpen(false);
                                                }}
                                            >
                                                <Check className={cn('mr-2 h-4 w-4', option.week === week && option.year === year ? 'opacity-100' : 'opacity-0')} />
                                                <span className="flex flex-1 items-center justify-between gap-2">
                                                    <span className="flex items-center gap-2">
                                                        Semana {option.week}
                                                        {group.isUpcoming && ` / ${option.year}`}
                                                        {option.week === currentWeek && option.year === currentYear && <CurrentBadge />}
                                                    </span>
                                                    {option.paymentsCount > 0 && (
                                                        <span className="text-xs text-muted-foreground">
                                                            {option.paymentsCount} {option.paymentsCount === 1 ? 'pago' : 'pagos'}
                                                        </span>
                                                    )}
                                                </span>
                                            </CommandItem>
                                        ))}
                                    </CommandGroup>
                                ))}
                            </CommandList>
                        </Command>
                    </PopoverContent>
                </Popover>
            ) : (
                <div className="flex items-center gap-2 rounded-lg border px-4 py-2">
                    <CalendarCheck className="h-4 w-4 text-gray-500" />
                    <span className="text-sm font-medium">
                        Semana {week} / {year}
                    </span>
                    {isCurrent && <CurrentBadge />}
                </div>
            )}
            <Button variant="outline" size="icon" onClick={() => onNavigate(1)}>
                <ChevronRight className="h-4 w-4" />
            </Button>
        </div>
    );
}
