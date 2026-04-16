import { Head, router, usePage } from '@inertiajs/react';
import { Banknote, DollarSign, PieChart, TrendingUp, Wallet } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Control Presupuestal', href: '/investment-dashboard' },
];

type ExecutionItem = {
    name: string;
    budget: string;
    executed: string;
    percent: number;
};

type BudgetComparison = {
    name: string;
    initial: string;
    addendum: string;
    total: string;
    growthPercent: number;
};

type ConceptRow = {
    concept: string;
    department: string;
    baseBudget: string;
    addendumTotal: string;
    addendumCount: number;
    totalBudget: string;
    paid: string;
    remaining: string;
    percent: number;
};

type PageProps = {
    projects: { id: number; name: string }[];
    filters: { project_id: string; department_id: string };
    kpis: { budget: string; executed: string; remaining: string; percent: number };
    byDepartment: ExecutionItem[];
    byConcept: BudgetComparison[];
    conceptTable: ConceptRow[];
    departments: { id: number; name: string }[];
};

function formatCurrency(value: string | number): string {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(value));
}

function ProgressBar({ percent, className }: { percent: number; className?: string }) {
    const color = percent >= 100
        ? 'bg-red-500'
        : percent >= 75
          ? 'bg-amber-500'
          : percent >= 50
            ? 'bg-blue-500'
            : 'bg-green-500';

    return (
        <div className={`h-2.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700 ${className ?? ''}`}>
            <div
                className={`h-full rounded-full transition-all ${color}`}
                style={{ width: `${Math.min(100, percent)}%` }}
            />
        </div>
    );
}

function CircularProgress({ percent }: { percent: number }) {
    const radius = 40;
    const circumference = 2 * Math.PI * radius;
    const offset = circumference - (Math.min(100, percent) / 100) * circumference;

    const color = percent >= 100
        ? '#ef4444'
        : percent >= 75
          ? '#f59e0b'
          : percent >= 50
            ? '#3b82f6'
            : '#22c55e';

    return (
        <div className="relative inline-flex items-center justify-center">
            <svg className="h-24 w-24 -rotate-90">
                <circle cx="48" cy="48" r={radius} fill="none" stroke="currentColor" strokeWidth="8" className="text-gray-200 dark:text-gray-700" />
                <circle cx="48" cy="48" r={radius} fill="none" stroke={color} strokeWidth="8" strokeLinecap="round" strokeDasharray={circumference} strokeDashoffset={offset} className="transition-all duration-500" />
            </svg>
            <span className="absolute text-xl font-bold text-foreground">{percent}%</span>
        </div>
    );
}

export default function InvestmentDashboard() {
    const { projects, filters, kpis, byDepartment, byConcept, conceptTable, departments } =
        usePage<PageProps>().props;

    const applyFilter = (key: string, value: string) => {
        const params: Record<string, string> = { ...filters, [key]: value };
        if (key === 'project_id') {
            params.department_id = 'all';
        }
        router.get('/investment-dashboard', params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Control Presupuestal de Inversión" />

            <div className="space-y-6 p-4 md:p-6">
                {/* Header + Filters */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                            Control Presupuestal
                        </h1>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Análisis de ejecución presupuestal de inversión
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-3">
                        <div className="space-y-1">
                            <label className="text-xs font-medium text-gray-500 dark:text-gray-400">Proyecto</label>
                            <Select value={filters.project_id} onValueChange={(v) => applyFilter('project_id', v)}>
                                <SelectTrigger className="w-56">
                                    <SelectValue placeholder="Seleccionar proyecto" />
                                </SelectTrigger>
                                <SelectContent>
                                    {projects.map((p) => (
                                        <SelectItem key={p.id} value={String(p.id)}>{p.name}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-1">
                            <label className="text-xs font-medium text-gray-500 dark:text-gray-400">Departamento</label>
                            <Select value={filters.department_id} onValueChange={(v) => applyFilter('department_id', v)}>
                                <SelectTrigger className="w-52">
                                    <SelectValue placeholder="Todos" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos</SelectItem>
                                    {departments.map((d) => (
                                        <SelectItem key={d.id} value={String(d.id)}>{d.name}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                {/* KPIs */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <div className="rounded-lg bg-blue-50 p-2 dark:bg-blue-900/20">
                                    <Wallet className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div>
                                    <p className="text-xs text-gray-500 dark:text-gray-400">Presupuesto Total</p>
                                    <p className="text-xl font-bold">{formatCurrency(kpis.budget)}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-3">
                                <div className="rounded-lg bg-purple-50 p-2 dark:bg-purple-900/20">
                                    <Banknote className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                </div>
                                <div>
                                    <p className="text-xs text-gray-500 dark:text-gray-400">Total Ejecutado</p>
                                    <p className="text-xl font-bold">{formatCurrency(kpis.executed)}</p>
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
                                    <p className="text-xs text-gray-500 dark:text-gray-400">Saldo Disponible</p>
                                    <p className="text-xl font-bold">{formatCurrency(kpis.remaining)}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs text-gray-500 dark:text-gray-400">Ejecución</p>
                                    <p className="mt-1 text-sm text-gray-500">
                                        {formatCurrency(kpis.executed)} de {formatCurrency(kpis.budget)}
                                    </p>
                                </div>
                                <CircularProgress percent={kpis.percent} />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Execution bars */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* By Department */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <TrendingUp className="h-4 w-4 text-gray-400" />
                                Ejecución por Departamento
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {byDepartment.length === 0 ? (
                                <p className="py-6 text-center text-sm text-gray-400">Sin datos</p>
                            ) : (
                                <div className="space-y-4">
                                    {byDepartment.map((item) => (
                                        <div key={item.name} className="space-y-1.5">
                                            <div className="flex items-center justify-between text-sm">
                                                <span className="font-medium">{item.name}</span>
                                                <span className="text-gray-500">
                                                    {formatCurrency(item.executed)} / {formatCurrency(item.budget)}
                                                </span>
                                            </div>
                                            <div className="flex items-center gap-3">
                                                <ProgressBar percent={item.percent} className="flex-1" />
                                                <span className="w-12 text-right text-xs font-semibold text-gray-600 dark:text-gray-400">
                                                    {item.percent}%
                                                </span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Budget: Initial vs Addendums */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <PieChart className="h-4 w-4 text-gray-400" />
                                Presupuesto Inicial vs Aditivas
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {byConcept.length === 0 ? (
                                <p className="py-6 text-center text-sm text-gray-400">Sin datos</p>
                            ) : (
                                <div className="space-y-5">
                                    {byConcept.map((item) => (
                                        <div key={item.name} className="space-y-2">
                                            <div className="flex items-center justify-between">
                                                <span className="text-sm font-medium">{item.name}</span>
                                                <span className="text-sm font-semibold">{formatCurrency(item.total)}</span>
                                            </div>
                                            {/* Stacked bar */}
                                            <div className="h-5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700 flex">
                                                {Number(item.initial) > 0 && (
                                                    <div
                                                        className="h-full bg-blue-500 transition-all"
                                                        style={{ width: `${(Number(item.initial) / Number(item.total)) * 100}%` }}
                                                        title={`Inicial: ${formatCurrency(item.initial)}`}
                                                    />
                                                )}
                                                {Number(item.addendum) > 0 && (
                                                    <div
                                                        className="h-full bg-amber-400 transition-all"
                                                        style={{ width: `${(Number(item.addendum) / Number(item.total)) * 100}%` }}
                                                        title={`Aditivas: ${formatCurrency(item.addendum)}`}
                                                    />
                                                )}
                                            </div>
                                            {/* Legend */}
                                            <div className="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                                <div className="flex items-center gap-3">
                                                    <span className="flex items-center gap-1">
                                                        <span className="inline-block h-2.5 w-2.5 rounded-full bg-blue-500" />
                                                        Inicial: {formatCurrency(item.initial)}
                                                    </span>
                                                    {Number(item.addendum) > 0 && (
                                                        <span className="flex items-center gap-1">
                                                            <span className="inline-block h-2.5 w-2.5 rounded-full bg-amber-400" />
                                                            Aditivas: {formatCurrency(item.addendum)}
                                                        </span>
                                                    )}
                                                </div>
                                                {item.growthPercent > 0 && (
                                                    <span className="font-semibold text-amber-600 dark:text-amber-400">+{item.growthPercent}%</span>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Concept Summary Table */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Resumen por Concepto</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {conceptTable.length === 0 ? (
                            <p className="py-8 text-center text-sm text-gray-400">No hay datos para mostrar</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-gray-500 dark:text-gray-400">
                                            <th className="pb-3 pr-4 font-medium">Concepto</th>
                                            <th className="pb-3 pr-4 font-medium">Departamento</th>
                                            <th className="pb-3 pr-4 font-medium text-right">Presupuesto Base</th>
                                            <th className="pb-3 pr-4 font-medium text-right">Aditivas</th>
                                            <th className="pb-3 pr-4 font-medium text-right">Presupuesto Total</th>
                                            <th className="pb-3 pr-4 font-medium text-right">Pagado</th>
                                            <th className="pb-3 pr-4 font-medium text-right">Saldo</th>
                                            <th className="pb-3 font-medium text-right">% Ejecución</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {conceptTable.map((row, i) => (
                                            <tr key={i} className="border-b last:border-0 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                                <td className="py-3 pr-4 font-medium">{row.concept}</td>
                                                <td className="py-3 pr-4 text-gray-600 dark:text-gray-400">{row.department}</td>
                                                <td className="py-3 pr-4 text-right font-mono">{formatCurrency(row.baseBudget)}</td>
                                                <td className="py-3 pr-4 text-right">
                                                    {Number(row.addendumTotal) > 0 ? (
                                                        <span className="font-mono">{formatCurrency(row.addendumTotal)} <span className="text-xs text-amber-600 dark:text-amber-400">({row.addendumCount})</span></span>
                                                    ) : (
                                                        <span className="text-gray-400">—</span>
                                                    )}
                                                </td>
                                                <td className="py-3 pr-4 text-right font-mono font-semibold">{formatCurrency(row.totalBudget)}</td>
                                                <td className="py-3 pr-4 text-right font-mono text-blue-600 dark:text-blue-400">{formatCurrency(row.paid)}</td>
                                                <td className="py-3 pr-4 text-right font-mono">
                                                    <span className={Number(row.remaining) <= 0 ? 'text-red-500' : 'text-green-600 dark:text-green-400'}>
                                                        {formatCurrency(row.remaining)}
                                                    </span>
                                                </td>
                                                <td className="py-3 text-right">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <ProgressBar percent={row.percent} className="w-16" />
                                                        <span className="w-10 text-right text-xs font-semibold">{row.percent}%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
