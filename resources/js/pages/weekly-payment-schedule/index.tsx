import { Head, router, usePage } from '@inertiajs/react';
import { CalendarCheck, ChevronLeft, ChevronRight, Send } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { formatCurrency } from '@/lib/currency';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface Payment {
    id: number;
    folio_number: number;
    provider: string;
    concept_name: string;
    project_name: string;
    payment_provision_date: string;
    payment_week_number: number;
    total: string;
    currency_prefix: string;
    description: string | null;
}

interface Schedule {
    id: number;
    uuid: string;
    week_number: number;
    year: number;
    status: string;
    creator_name: string;
    created_at: string;
    items_count: number;
    included_count: number;
    total_amount: number;
    approval_status: string;
}

interface Props {
    payments: Payment[];
    schedules: Schedule[];
    currentWeek: number;
    currentYear: number;
}

interface ItemState {
    included: boolean;
    exclusion_reason: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Programación de Pagos Semanal', href: '/weekly-payment-schedule' },
];

const statusLabels: Record<string, string> = {
    draft: 'Borrador',
    pending_approval: 'Pendiente de Autorización',
    approved: 'Autorizado',
    rejected: 'Rechazado',
};

const statusColors: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    pending_approval: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    approved: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    rejected: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
};

export default function WeeklyPaymentScheduleIndex({ payments, schedules, currentWeek, currentYear }: Props) {
    const { errors, props } = usePage();
    const flash = (props as Record<string, unknown>).flash as { success?: string } | undefined;

    const [selectedWeek, setSelectedWeek] = useState(currentWeek);
    const [selectedYear, setSelectedYear] = useState(currentYear);
    const [processing, setProcessing] = useState(false);

    const weekPayments = useMemo(
        () => payments.filter((p) => p.payment_week_number === selectedWeek),
        [payments, selectedWeek],
    );

    const [itemStates, setItemStates] = useState<Record<number, ItemState>>(() => {
        const initial: Record<number, ItemState> = {};
        payments.forEach((p) => {
            initial[p.id] = { included: true, exclusion_reason: '' };
        });
        return initial;
    });

    const toggleItem = (id: number) => {
        setItemStates((prev) => ({
            ...prev,
            [id]: { ...prev[id], included: !prev[id]?.included },
        }));
    };

    const setExclusionReason = (id: number, reason: string) => {
        setItemStates((prev) => ({
            ...prev,
            [id]: { ...prev[id], exclusion_reason: reason },
        }));
    };

    const toggleAll = (checked: boolean) => {
        setItemStates((prev) => {
            const next = { ...prev };
            weekPayments.forEach((p) => {
                next[p.id] = { ...next[p.id], included: checked };
            });
            return next;
        });
    };

    const includedPayments = weekPayments.filter((p) => itemStates[p.id]?.included);
    const totalIncluded = includedPayments.reduce((sum, p) => sum + parseFloat(p.total), 0);
    const allChecked = weekPayments.length > 0 && weekPayments.every((p) => itemStates[p.id]?.included);

    const existingScheduleForWeek = schedules.find(
        (s) => s.week_number === selectedWeek && s.year === selectedYear && s.status !== 'rejected',
    );

    const navigateWeek = (direction: number) => {
        let newWeek = selectedWeek + direction;
        let newYear = selectedYear;
        if (newWeek > 52) {
            newWeek = 1;
            newYear += 1;
        } else if (newWeek < 1) {
            newWeek = 52;
            newYear -= 1;
        }
        setSelectedWeek(newWeek);
        setSelectedYear(newYear);
    };

    const handleSubmit = () => {
        if (weekPayments.length === 0) return;

        setProcessing(true);
        const items = weekPayments.map((p) => ({
            id: p.id,
            included: itemStates[p.id]?.included ?? true,
            exclusion_reason: itemStates[p.id]?.exclusion_reason || null,
        }));

        router.post(
            '/weekly-payment-schedule',
            {
                week_number: selectedWeek,
                year: selectedYear,
                items,
            },
            {
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Programación de Pagos Semanal" />

            <div className="mx-auto max-w-7xl space-y-6 p-4 lg:p-6">
                {flash?.success && (
                    <div className="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300">
                        {flash.success}
                    </div>
                )}

                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Programación de Pagos Semanal</h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Selecciona los pagos autorizados que se procesarán en bancos esta semana.
                        </p>
                    </div>

                    {/* Week navigator */}
                    <div className="flex items-center gap-2">
                        <Button variant="outline" size="icon" onClick={() => navigateWeek(-1)}>
                            <ChevronLeft className="h-4 w-4" />
                        </Button>
                        <div className="flex items-center gap-2 rounded-lg border px-4 py-2">
                            <CalendarCheck className="h-4 w-4 text-gray-500" />
                            <span className="text-sm font-medium">
                                Semana {selectedWeek} / {selectedYear}
                            </span>
                            {selectedWeek === currentWeek && selectedYear === currentYear && (
                                <Badge className="bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">Actual</Badge>
                            )}
                        </div>
                        <Button variant="outline" size="icon" onClick={() => navigateWeek(1)}>
                            <ChevronRight className="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                {/* Summary cards */}
                <div className="grid gap-4 sm:grid-cols-3">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-sm text-gray-500 dark:text-gray-400">Pagos de la semana</div>
                            <div className="text-2xl font-bold">{weekPayments.length}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-sm text-gray-500 dark:text-gray-400">Pagos seleccionados</div>
                            <div className="text-2xl font-bold text-green-600">{includedPayments.length}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-sm text-gray-500 dark:text-gray-400">Total a procesar</div>
                            <div className="text-2xl font-bold text-blue-600">{formatCurrency(totalIncluded)}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Payments table */}
                <Card>
                    <CardHeader className="pb-3">
                        <div className="flex items-center justify-between">
                            <CardTitle className="text-base">Pagos Autorizados — Semana {selectedWeek}</CardTitle>
                            {existingScheduleForWeek && (
                                <Badge className={statusColors[existingScheduleForWeek.status]}>
                                    {statusLabels[existingScheduleForWeek.status]}
                                </Badge>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent>
                        {weekPayments.length === 0 ? (
                            <p className="py-12 text-center text-sm text-gray-400">
                                No hay pagos autorizados para la semana {selectedWeek}.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-gray-500 dark:text-gray-400">
                                            <th className="pb-3 pr-4">
                                                <Checkbox
                                                    checked={allChecked}
                                                    onCheckedChange={(checked) => toggleAll(checked === true)}
                                                    disabled={!!existingScheduleForWeek}
                                                />
                                            </th>
                                            <th className="pb-3 pr-4 font-medium">Folio</th>
                                            <th className="pb-3 pr-4 font-medium">Proveedor</th>
                                            <th className="pb-3 pr-4 font-medium">Concepto</th>
                                            <th className="pb-3 pr-4 font-medium">Proyecto</th>
                                            <th className="pb-3 pr-4 font-medium">Fecha Provisión</th>
                                            <th className="pb-3 pr-4 text-right font-medium">Total</th>
                                            <th className="pb-3 pr-4 font-medium">Moneda</th>
                                            <th className="pb-3 font-medium">Razón de exclusión</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {weekPayments.map((payment) => {
                                            const state = itemStates[payment.id];
                                            const isIncluded = state?.included ?? true;
                                            return (
                                                <tr
                                                    key={payment.id}
                                                    className={`border-b last:border-0 ${!isIncluded ? 'bg-red-50/50 dark:bg-red-900/10' : ''}`}
                                                >
                                                    <td className="py-3 pr-4">
                                                        <Checkbox
                                                            checked={isIncluded}
                                                            onCheckedChange={() => toggleItem(payment.id)}
                                                            disabled={!!existingScheduleForWeek}
                                                        />
                                                    </td>
                                                    <td className="py-3 pr-4 font-mono text-xs">
                                                        #{String(payment.folio_number).padStart(5, '0')}
                                                    </td>
                                                    <td className="py-3 pr-4 font-medium">{payment.provider}</td>
                                                    <td className="py-3 pr-4">{payment.concept_name}</td>
                                                    <td className="py-3 pr-4">{payment.project_name}</td>
                                                    <td className="py-3 pr-4">
                                                        {payment.payment_provision_date
                                                            ? new Date(payment.payment_provision_date + 'T00:00:00').toLocaleDateString('es-MX', {
                                                                  day: '2-digit',
                                                                  month: 'short',
                                                                  year: 'numeric',
                                                              })
                                                            : '-'}
                                                    </td>
                                                    <td className="py-3 pr-4 text-right font-semibold">
                                                        {formatCurrency(payment.total)}
                                                    </td>
                                                    <td className="py-3 pr-4">{payment.currency_prefix}</td>
                                                    <td className="py-3">
                                                        {!isIncluded && (
                                                            <Input
                                                                placeholder="Razón (opcional)"
                                                                value={state?.exclusion_reason ?? ''}
                                                                onChange={(e) => setExclusionReason(payment.id, e.target.value)}
                                                                disabled={!!existingScheduleForWeek}
                                                                className="h-8 text-xs"
                                                            />
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        {/* Submit button */}
                        {weekPayments.length > 0 && !existingScheduleForWeek && (
                            <div className="mt-6 flex items-center justify-between border-t pt-4">
                                <p className="text-sm text-gray-500">
                                    {includedPayments.length} de {weekPayments.length} pagos seleccionados ·{' '}
                                    <span className="font-semibold">{formatCurrency(totalIncluded)}</span>
                                </p>
                                <Button onClick={handleSubmit} disabled={processing || includedPayments.length === 0}>
                                    <Send className="mr-2 h-4 w-4" />
                                    {processing ? 'Enviando...' : 'Guardar Programación'}
                                </Button>
                            </div>
                        )}

                        {existingScheduleForWeek && (
                            <div className="mt-4 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                Ya existe una programación para esta semana con estado:{' '}
                                <span className="font-semibold">{statusLabels[existingScheduleForWeek.status]}</span>.
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Schedule history */}
                {schedules.length > 0 && (
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base">Historial de Programaciones</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left text-gray-500 dark:text-gray-400">
                                            <th className="pb-3 pr-4 font-medium">Semana</th>
                                            <th className="pb-3 pr-4 font-medium">Creado por</th>
                                            <th className="pb-3 pr-4 font-medium">Fecha</th>
                                            <th className="pb-3 pr-4 font-medium">Pagos</th>
                                            <th className="pb-3 pr-4 text-right font-medium">Monto</th>
                                            <th className="pb-3 font-medium">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {schedules.map((schedule) => (
                                            <tr key={schedule.id} className="border-b last:border-0">
                                                <td className="py-3 pr-4 font-medium">
                                                    S{schedule.week_number}/{schedule.year}
                                                </td>
                                                <td className="py-3 pr-4">{schedule.creator_name}</td>
                                                <td className="py-3 pr-4">
                                                    {schedule.created_at
                                                        ? new Date(schedule.created_at).toLocaleDateString('es-MX', {
                                                              day: '2-digit',
                                                              month: 'short',
                                                              year: 'numeric',
                                                          })
                                                        : '-'}
                                                </td>
                                                <td className="py-3 pr-4">
                                                    {schedule.included_count} / {schedule.items_count}
                                                </td>
                                                <td className="py-3 pr-4 text-right font-semibold">
                                                    {formatCurrency(schedule.total_amount)}
                                                </td>
                                                <td className="py-3">
                                                    <Badge className={statusColors[schedule.status]}>
                                                        {statusLabels[schedule.status]}
                                                    </Badge>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
