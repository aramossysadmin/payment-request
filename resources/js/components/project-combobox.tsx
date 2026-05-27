import { Check, ChevronsUpDown } from 'lucide-react';
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
                    <span className={cn('truncate', !selectedProject && 'text-muted-foreground')}>
                        {selectedProject ? selectedProject.name : placeholder}
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
                                    {project.name}
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
