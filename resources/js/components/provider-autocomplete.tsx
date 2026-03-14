import { useCallback, useEffect, useRef, useState } from 'react';
import { Input } from '@/components/ui/input';

type ProviderSuggestion = {
    provider: string;
    rfc: string | null;
};

type ProviderAutocompleteProps = {
    value: string;
    onChange: (value: string) => void;
    onSelect: (suggestion: ProviderSuggestion) => void;
    field: 'provider' | 'rfc';
    placeholder?: string;
    maxLength?: number;
    id?: string;
};

export function ProviderAutocomplete({
    value,
    onChange,
    onSelect,
    field,
    placeholder,
    maxLength,
    id,
}: ProviderAutocompleteProps) {
    const [suggestions, setSuggestions] = useState<ProviderSuggestion[]>([]);
    const [isOpen, setIsOpen] = useState(false);
    const [activeIndex, setActiveIndex] = useState(-1);
    const wrapperRef = useRef<HTMLDivElement>(null);
    const debounceRef = useRef<ReturnType<typeof setTimeout>>(undefined);

    useEffect(() => {
        function handleClickOutside(e: MouseEvent) {
            if (
                wrapperRef.current &&
                !wrapperRef.current.contains(e.target as Node)
            ) {
                setIsOpen(false);
            }
        }
        document.addEventListener('mousedown', handleClickOutside);
        return () =>
            document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const search = useCallback(
        (query: string) => {
            if (debounceRef.current) clearTimeout(debounceRef.current);

            if (query.length < 2) {
                setSuggestions([]);
                setIsOpen(false);
                return;
            }

            debounceRef.current = setTimeout(async () => {
                try {
                    const res = await fetch(
                        `/providers/search?q=${encodeURIComponent(query)}&field=${field}`,
                    );
                    const data: ProviderSuggestion[] = await res.json();
                    setSuggestions(data);
                    setIsOpen(data.length > 0);
                    setActiveIndex(-1);
                } catch {
                    setSuggestions([]);
                    setIsOpen(false);
                }
            }, 300);
        },
        [field],
    );

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const val =
            field === 'rfc' ? e.target.value.toUpperCase() : e.target.value;
        onChange(val);
        search(val);
    };

    const handleSelect = (suggestion: ProviderSuggestion) => {
        onSelect(suggestion);
        setIsOpen(false);
        setSuggestions([]);
    };

    const handleKeyDown = (e: React.KeyboardEvent) => {
        if (!isOpen || suggestions.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActiveIndex((prev) =>
                prev < suggestions.length - 1 ? prev + 1 : 0,
            );
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActiveIndex((prev) =>
                prev > 0 ? prev - 1 : suggestions.length - 1,
            );
        } else if (e.key === 'Enter' && activeIndex >= 0) {
            e.preventDefault();
            handleSelect(suggestions[activeIndex]);
        } else if (e.key === 'Escape') {
            setIsOpen(false);
        }
    };

    return (
        <div ref={wrapperRef} className="relative">
            <Input
                id={id}
                value={value}
                onChange={handleChange}
                onKeyDown={handleKeyDown}
                onFocus={() => {
                    if (suggestions.length > 0) setIsOpen(true);
                }}
                placeholder={placeholder}
                maxLength={maxLength}
                autoComplete="off"
            />
            {isOpen && suggestions.length > 0 && (
                <ul className="border-input bg-popover absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md border p-1 shadow-md">
                    {suggestions.map((s, i) => (
                        <li
                            key={`${s.provider}-${s.rfc ?? 'no-rfc'}`}
                            className={`cursor-pointer rounded-sm px-3 py-2 text-sm ${
                                i === activeIndex
                                    ? 'bg-accent text-accent-foreground'
                                    : 'hover:bg-accent hover:text-accent-foreground'
                            }`}
                            onMouseDown={() => handleSelect(s)}
                            onMouseEnter={() => setActiveIndex(i)}
                        >
                            <div className="font-medium">{s.provider}</div>
                            {s.rfc && (
                                <div className="text-muted-foreground text-xs">
                                    RFC: {s.rfc}
                                </div>
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
