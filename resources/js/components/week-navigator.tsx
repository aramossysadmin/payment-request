import { CalendarCheck, ChevronLeft, ChevronRight } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

interface WeekNavigatorProps {
    week: number;
    year: number;
    currentWeek: number;
    currentYear: number;
    onNavigate: (direction: number) => void;
}

export function WeekNavigator({ week, year, currentWeek, currentYear, onNavigate }: WeekNavigatorProps) {
    return (
        <div className="flex items-center gap-2">
            <Button variant="outline" size="icon" onClick={() => onNavigate(-1)}>
                <ChevronLeft className="h-4 w-4" />
            </Button>
            <div className="flex items-center gap-2 rounded-lg border px-4 py-2">
                <CalendarCheck className="h-4 w-4 text-gray-500" />
                <span className="text-sm font-medium">
                    Semana {week} / {year}
                </span>
                {week === currentWeek && year === currentYear && (
                    <Badge className="bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">Actual</Badge>
                )}
            </div>
            <Button variant="outline" size="icon" onClick={() => onNavigate(1)}>
                <ChevronRight className="h-4 w-4" />
            </Button>
        </div>
    );
}
