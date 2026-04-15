import { Head, router, usePage } from '@inertiajs/react';
import { Building2, DollarSign, FileText, Search, X } from 'lucide-react';
import { useCallback, useState } from 'react';
import { Pagination } from '@/components/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, PaginatedData } from '@/types';
import type { InvestmentRequest } from '@/types/investment-request';

type DepartmentBreakdown = {
    id: number;
    name: string;
    total: string;
    count: number;
};

type PageProps = {
    project: {
        id: number;
        name: string;
        branch: string | null;
    };
    totals: {
        subtotal: string;
        total: string;
        authorized: string;
        pending: string;
        count: number;
    };
    departmentBreakdown: DepartmentBreakdown[];
    investmentRequests: PaginatedData<InvestmentRequest>;
    filters: { search?: string; status?: string; department_id?: string };
};

const statusColors: Record<string, string> = {
    warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    info: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    success: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    danger: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    gray: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400',
    purple: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    orange: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
};

function formatCurrency(value: string | number): string {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(value));
}

export default function Consolidated() {
    const { project, totals, departmentBreakdown, investmentRequests, filters } =
        usePage<PageProps>().props;

    const [search, setSearch] = useState(filters.search ?? '');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Hojas de Inversión', href: '/investment-sheets' },
        { title: `Consolidado: ${project.name}`, href: `/investment-sheets/consolidated/${project.id}` },
    ];

    const applyFilters = useCallback(
        (params: Record<string, string>) => {
            const merged = { ...filters, ...params };
            const cleaned: Record<string, string> = {};
            for (const [key, value] of Object.entries(merged)) {
                if (value) cleaned[key] = value;
            }
            router.get(`/investment-sheets/consolidated/${project.id}`, cleaned, {
                preserveState: true,
                preserveScroll: true,
            });
        },
        [filters, project.id],
    );

    const clearFilters = () => {
        setSearch('');
        router.get(`/investment-sheets/consolidated/${project.id}`, {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const hasActiveFilters = filters.search || filters.status || filters.department_id;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Consolidado — ${project.name}`} />

            <div className="p-4 md:p-6 space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        {project.name}
                    </h1>
                    {project.branch && (
                        <p className="mt-1 flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                            <Building2 className="h-4 w-4" />
                            {project.branch}
                        </p>
                    )}
                </div>

                {/* Summary Cards */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <div className="rounded-lg bg-blue-50 p-2 dark:bg-blue-900/20">
                                    <FileText className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">Total de Conceptos</p>
                                    <p className="text-2xl font-bold">{totals.count}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <div className="rounded-lg bg-green-50 p-2 dark:bg-green-900/20">
                                    <DollarSign className="h-5 w-5 text-green-600 dark:text-green-400" />
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">Subtotal</p>
                                    <p className="text-2xl font-bold">{formatCurrency(totals.subtotal)}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <div className="rounded-lg bg-emerald-50 p-2 dark:bg-emerald-900/20">
                                    <DollarSign className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">Monto Autorizado</p>
                                    <p className="text-2xl font-bold">{formatCurrency(totals.authorized)}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <div className="rounded-lg bg-amber-50 p-2 dark:bg-amber-900/20">
                                    <DollarSign className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">Monto Pendiente</p>
                                    <p className="text-2xl font-bold">{formatCurrency(totals.pending)}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Department Breakdown */}
                {departmentBreakdown.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Inversión por Departamento</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                {departmentBreakdown.map((dept) => (
                                    <button
                                        key={dept.id}
                                        type="button"
                                        onClick={() => applyFilters({ department_id: filters.department_id === String(dept.id) ? '' : String(dept.id) })}
                                        className={`flex items-center justify-between rounded-lg border p-3 text-left transition-colors ${
                                            filters.department_id === String(dept.id)
                                                ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20'
                                                : 'hover:bg-gray-50 dark:hover:bg-gray-800'
                                        }`}
                                    >
                                        <div>
                                            <p className="text-sm font-medium">{dept.name}</p>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                                {dept.count} {dept.count === 1 ? 'hoja' : 'hojas'}
                                            </p>
                                        </div>
                                        <span className="text-sm font-semibold">{formatCurrency(dept.total)}</span>
                                    </button>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Filters & Table */}
                <Card>
                    <CardHeader>
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <CardTitle>Detalle de Hojas de Inversión</CardTitle>
                            <div className="flex items-center gap-2">
                                <div className="relative">
                                    <Search className="absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                    <Input
                                        className="pl-8 w-64"
                                        placeholder="Buscar proveedor, folio..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter') applyFilters({ search });
                                        }}
                                    />
                                </div>
                                <Select
                                    value={filters.status ?? 'all'}
                                    onValueChange={(v) => applyFilters({ status: v === 'all' ? '' : v })}
                                >
                                    <SelectTrigger className="w-44">
                                        <SelectValue placeholder="Todos los estados" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos los estados</SelectItem>
                                        <SelectItem value="pending_department">Pendiente</SelectItem>
                                        <SelectItem value="completed">Completada</SelectItem>
                                    </SelectContent>
                                </Select>
                                {hasActiveFilters && (
                                    <Button variant="ghost" size="icon" onClick={clearFilters}>
                                        <X className="h-4 w-4" />
                                    </Button>
                                )}
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        {investmentRequests.data.length === 0 ? (
                            <div className="py-12 text-center text-gray-500 dark:text-gray-400">
                                No se encontraron hojas de inversión para este proyecto.
                            </div>
                        ) : (
                            <>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b text-left text-gray-500 dark:text-gray-400">
                                                <th className="pb-3 pr-4 font-medium">Folio</th>
                                                <th className="pb-3 pr-4 font-medium">Proveedor</th>
                                                <th className="pb-3 pr-4 font-medium">Departamento</th>
                                                <th className="pb-3 pr-4 font-medium">Concepto</th>
                                                <th className="pb-3 pr-4 font-medium text-right">Subtotal</th>
                                                <th className="pb-3 pr-4 font-medium text-right">IVA</th>
                                                <th className="pb-3 pr-4 font-medium text-right">Total</th>
                                                <th className="pb-3 font-medium">Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {investmentRequests.data.map((ir) => (
                                                <tr
                                                    key={ir.uuid}
                                                    className="border-b last:border-0 hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer"
                                                    onClick={() => router.visit(`/investment-sheets/${ir.uuid}`)}
                                                >
                                                    <td className="py-3 pr-4 font-mono text-xs">
                                                        #{String(ir.folio_number).padStart(5, '0')}
                                                    </td>
                                                    <td className="py-3 pr-4">
                                                        <div className="font-medium">{ir.provider}</div>
                                                        {ir.rfc && (
                                                            <div className="text-xs text-gray-500">{ir.rfc}</div>
                                                        )}
                                                    </td>
                                                    <td className="py-3 pr-4 text-gray-600 dark:text-gray-400">
                                                        {ir.department?.name}
                                                    </td>
                                                    <td className="py-3 pr-4 text-gray-600 dark:text-gray-400">
                                                        {ir.expense_concept?.name}
                                                    </td>
                                                    <td className="py-3 pr-4 text-right font-mono">
                                                        {formatCurrency(ir.subtotal)}
                                                    </td>
                                                    <td className="py-3 pr-4 text-right font-mono">
                                                        {formatCurrency(ir.iva)}
                                                    </td>
                                                    <td className="py-3 pr-4 text-right font-mono font-semibold">
                                                        {formatCurrency(ir.total)}
                                                    </td>
                                                    <td className="py-3">
                                                        <Badge
                                                            variant="secondary"
                                                            className={statusColors[ir.status.color] ?? statusColors.gray}
                                                        >
                                                            {ir.status.label}
                                                        </Badge>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                <div className="mt-4">
                                    <Pagination links={investmentRequests.meta.links} />
                                </div>
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
