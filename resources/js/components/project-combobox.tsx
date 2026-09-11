import { Check, ChevronsUpDown, Lock } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

export type Project = {
    id: number;
    name: string;
    is_active?: boolean;
    closed_at?: string | null;
};

type ProjectComboboxProps = {
    projects: Project[];
    selectedId: number | null;
    onSelect: (id: number) => void;
    placeholder?: string;
    emptyMessage?: string;
};

export function ProjectCombobox({
    projects,
    selectedId,
    onSelect,
    placeholder = 'Seleccionar Hoja de Inversión...',
    emptyMessage = 'No hay proyectos disponibles.',
}: ProjectComboboxProps) {
    const [open, setOpen] = useState(false);

    const selectedProject = selectedId ? projects.find((p) => p.id === selectedId) : null;

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className="w-full justify-between"
                >
                    <span className={cn('flex min-w-0 items-center gap-2 truncate', !selectedProject && 'text-muted-foreground')}>
                        <span className="truncate">
                            {selectedProject ? selectedProject.name : placeholder}
                        </span>
                        {selectedProject?.is_active === false && (
                            <span className="inline-flex shrink-0 items-center gap-1 rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-red-700 dark:bg-red-900/40 dark:text-red-200">
                                <Lock className="h-3 w-3" />
                                Cerrado
                            </span>
                        )}
                    </span>
                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-[var(--radix-popover-trigger-width)] p-0" align="start">
                <Command>
                    <CommandInput placeholder="Buscar proyecto..." />
                    <CommandList>
                        <CommandEmpty>{emptyMessage}</CommandEmpty>
                        <CommandGroup>
                            {projects.map((project) => (
                                <CommandItem
                                    key={project.id}
                                    value={project.name}
                                    onSelect={() => {
                                        onSelect(project.id);
                                        setOpen(false);
                                    }}
                                >
                                    <Check
                                        className={cn(
                                            'mr-2 h-4 w-4',
                                            selectedId === project.id ? 'opacity-100' : 'opacity-0',
                                        )}
                                    />
                                    <span className="flex flex-1 items-center justify-between gap-2">
                                        <span className="truncate">{project.name}</span>
                                        {project.is_active === false && (
                                            <span className="inline-flex shrink-0 items-center gap-1 rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-red-700 dark:bg-red-900/40 dark:text-red-200">
                                                <Lock className="h-3 w-3" />
                                                Cerrado
                                            </span>
                                        )}
                                    </span>
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
