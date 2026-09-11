import { Lock } from 'lucide-react';

interface ProjectClosedBannerProps {
    /** ISO timestamp de cuando se cerró el proyecto. */
    closedAt: string | null | undefined;
    /** Nombre del proyecto (opcional, se usa para reforzar el mensaje). */
    projectName?: string | null;
}

function formatClosedAt(iso: string): string {
    try {
        const date = new Date(iso);
        return new Intl.DateTimeFormat('es-MX', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        }).format(date);
    } catch {
        return iso;
    }
}

/**
 * Banner rojo que se muestra en la parte superior de las vistas de detalle de un proyecto
 * cerrado. Indica que el proyecto está en modo solo lectura y que las capturas están
 * bloqueadas. Sólo debe renderizarse cuando `project.is_active === false`.
 */
export function ProjectClosedBanner({ closedAt, projectName }: ProjectClosedBannerProps) {
    const suffix = closedAt ? ` Cerrado el ${formatClosedAt(closedAt)}.` : '';
    const projectLabel = projectName ? ` — ${projectName}` : '';

    return (
        <div
            className="flex items-center gap-3 rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900 shadow-sm dark:border-red-800 dark:bg-red-950/30 dark:text-red-100"
            role="alert"
        >
            <Lock className="size-5 text-red-600 dark:text-red-400" aria-hidden />
            <div className="flex-1">
                <span className="font-semibold uppercase tracking-wide">Proyecto cerrado{projectLabel}</span>
                <span className="ml-1">
                    Modo solo lectura. Ningún usuario puede capturar, modificar ni enviar registros.{suffix}
                </span>
            </div>
        </div>
    );
}
