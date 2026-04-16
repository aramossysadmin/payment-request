import { Head, router, usePage } from '@inertiajs/react';
import { ArrowRight, Building2, FileText, FolderOpen } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Hojas de Inversión', href: '/investment-sheets/consolidated' },
];

type ProjectSummary = {
    id: number;
    name: string;
    branch: string | null;
    sheets_count: number;
    total: string;
};

type PageProps = {
    projects: ProjectSummary[];
};

function formatCurrency(value: string | number): string {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(value));
}

export default function ConsolidatedIndex() {
    const { projects } = usePage<PageProps>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Hojas de Inversión" />

            <div className="p-4 md:p-6 space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        Hojas de Inversión
                    </h1>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Selecciona un proyecto para ver el detalle consolidado de sus conceptos de inversión.
                    </p>
                </div>

                {projects.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-16 text-center">
                            <FolderOpen className="mb-4 h-12 w-12 text-gray-300 dark:text-gray-600" />
                            <p className="text-lg font-medium text-gray-500 dark:text-gray-400">
                                No hay proyectos con conceptos de inversión
                            </p>
                            <p className="mt-1 text-sm text-gray-400 dark:text-gray-500">
                                Los conceptos de inversión aparecerán aquí cuando se creen y asocien a un proyecto.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {projects.map((project) => (
                            <Card
                                key={project.id}
                                className="cursor-pointer transition-all hover:shadow-md hover:border-blue-300 dark:hover:border-blue-700"
                                onClick={() => router.visit(`/investment-sheets/consolidated/${project.id}`)}
                            >
                                <CardContent className="pt-6">
                                    <div className="flex items-start justify-between">
                                        <div className="min-w-0 flex-1">
                                            <p className="text-xs font-medium text-gray-500 dark:text-gray-400">Proyecto</p>
                                            <h3 className="truncate text-base font-semibold text-gray-900 dark:text-gray-100">
                                                {project.name}
                                            </h3>
                                            {project.branch && (
                                                <p className="mt-1 flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                                                    <Building2 className="h-3.5 w-3.5" />
                                                    {project.branch}
                                                </p>
                                            )}
                                        </div>
                                        <ArrowRight className="h-5 w-5 shrink-0 text-gray-400" />
                                    </div>

                                    <div className="mt-4 flex items-center justify-between border-t pt-4">
                                        <div className="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                                            <FileText className="h-4 w-4" />
                                            {project.sheets_count} {project.sheets_count === 1 ? 'concepto' : 'conceptos'}
                                        </div>
                                        <span className="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {formatCurrency(project.total)}
                                        </span>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
