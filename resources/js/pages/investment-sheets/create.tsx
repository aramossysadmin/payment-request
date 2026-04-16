import { Head, router, usePage } from '@inertiajs/react';
import { CheckIcon, ChevronsUpDownIcon } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { FileUpload } from '@/components/file-upload';
import InputError from '@/components/input-error';
import { ProviderAutocomplete } from '@/components/provider-autocomplete';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Branch, Currency, Project } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Conceptos de Inversión', href: '/investment-sheets' },
    { title: 'Nuevo Concepto', href: '/investment-sheets/create' },
];

const ivaRateOptions = [
    { value: '0.00', label: 'IVA 0%' },
    { value: '0.08', label: 'IVA 8%' },
    { value: '0.16', label: 'IVA 16%' },
];

type InvestmentExpenseConceptOption = { id: number; name: string; category?: { id: number; name: string } | null };

type PageProps = {
    currencies: Currency[];
    branches: Branch[];
    investmentExpenseConcepts: InvestmentExpenseConceptOption[];
    projects: Project[];
    errors: Record<string, string>;
};

export default function Create() {
    const { currencies, branches, investmentExpenseConcepts, projects, errors } =
        usePage<PageProps>().props;

    const [values, setValues] = useState({
        provider: '',
        rfc: '',
        contact_name: '',
        contact_email: '',
        contact_phone: '',
        invoice_folio: '',
        currency_id: '',
        branch_id: '',
        investment_expense_concept_id: '',
        description: '',
        subtotal: '',
        iva_rate: '',
        iva: '',
        retention: false,
        total: '',
    });

    const [selectedProjectId, setSelectedProjectId] = useState('');
    const [expenseConceptOpen, setExpenseConceptOpen] = useState(false);
    const [files, setFiles] = useState<File[]>([]);
    const [processing, setProcessing] = useState(false);

    const handleProjectChange = (projectId: string) => {
        setSelectedProjectId(projectId);
        const project = projects.find((p) => String(p.id) === projectId);
        if (project) {
            setValues((prev) => ({ ...prev, branch_id: String(project.branch_id) }));
        }
    };

    const recalculate = (subtotal: number, ivaRate: number) => {
        const iva = Math.round(subtotal * ivaRate * 100) / 100;
        const total = Math.round((subtotal + iva) * 100) / 100;
        return { iva: iva.toFixed(2), total: total.toFixed(2) };
    };

    const handleChange = (field: string, value: string) => {
        if (field === 'subtotal') {
            const subtotal = parseFloat(value) || 0;
            const { iva, total } = recalculate(subtotal, parseFloat(values.iva_rate) || 0);
            setValues((prev) => ({ ...prev, subtotal: value, iva, total }));
            return;
        }
        if (field === 'iva_rate') {
            const subtotal = parseFloat(values.subtotal) || 0;
            const { iva, total } = recalculate(subtotal, parseFloat(value) || 0);
            setValues((prev) => ({ ...prev, iva_rate: value, iva, total }));
            return;
        }
        setValues((prev) => ({ ...prev, [field]: value }));
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        const formData = new FormData();
        if (selectedProjectId) formData.append('project_id', selectedProjectId);
        Object.entries(values).forEach(([key, val]) => {
            formData.append(key, typeof val === 'boolean' ? (val ? '1' : '0') : String(val));
        });

        files.forEach((file) => formData.append('advance_documents[]', file));

        router.post('/investment-sheets', formData, {
            forceFormData: true,
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo Concepto de Inversión" />

            <div className="p-4 md:p-6">
                <h1 className="mb-6 text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                    Nuevo Concepto de Inversión
                </h1>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Row 1: Datos Generales + Información del Proveedor */}
                    <div className="grid gap-6 lg:grid-cols-2">
                        {/* Section 1: Datos Generales del Concepto de Inversión */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Datos Generales del Concepto de Inversión</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label>Proyecto</Label>
                                    <Select
                                        value={selectedProjectId}
                                        onValueChange={handleProjectChange}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar proyecto" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {projects.map((p) => (
                                                <SelectItem key={p.id} value={String(p.id)}>
                                                    {p.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Sucursal</Label>
                                    <Input
                                        value={branches.find((b) => String(b.id) === values.branch_id)?.name ?? ''}
                                        readOnly
                                        disabled
                                        placeholder="Se asigna al seleccionar proyecto"
                                        className="bg-gray-50 dark:bg-gray-800"
                                    />
                                    <InputError message={errors.branch_id} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Gasto de Inversión</Label>
                                    <Popover open={expenseConceptOpen} onOpenChange={setExpenseConceptOpen}>
                                        <PopoverTrigger asChild>
                                            <Button
                                                variant="outline"
                                                role="combobox"
                                                aria-expanded={expenseConceptOpen}
                                                className="w-full justify-between font-normal"
                                            >
                                                {values.investment_expense_concept_id
                                                    ? (() => {
                                                        const selected = investmentExpenseConcepts.find(
                                                            (ec) => String(ec.id) === values.investment_expense_concept_id,
                                                        );
                                                        return selected
                                                            ? `${selected.category?.name ?? ''} - ${selected.name}`
                                                            : 'Seleccionar';
                                                    })()
                                                    : 'Seleccionar'}
                                                <ChevronsUpDownIcon className="ml-2 size-4 shrink-0 opacity-50" />
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent className="w-[--radix-popover-trigger-width] p-0" align="start">
                                            <Command>
                                                <CommandInput placeholder="Buscar gasto de inversión..." />
                                                <CommandList>
                                                    <CommandEmpty>Sin resultados.</CommandEmpty>
                                                    <CommandGroup>
                                                        {investmentExpenseConcepts.map((ec) => (
                                                            <CommandItem
                                                                key={ec.id}
                                                                value={`${ec.category?.name ?? ''} - ${ec.name}`}
                                                                onSelect={() => {
                                                                    handleChange('investment_expense_concept_id', String(ec.id));
                                                                    setExpenseConceptOpen(false);
                                                                }}
                                                            >
                                                                <CheckIcon
                                                                    className={cn(
                                                                        'mr-2 size-4',
                                                                        values.investment_expense_concept_id === String(ec.id)
                                                                            ? 'opacity-100'
                                                                            : 'opacity-0',
                                                                    )}
                                                                />
                                                                {ec.category?.name ?? ''} - {ec.name}
                                                            </CommandItem>
                                                        ))}
                                                    </CommandGroup>
                                                </CommandList>
                                            </Command>
                                        </PopoverContent>
                                    </Popover>
                                    <InputError message={errors.investment_expense_concept_id} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="description">
                                        Descripción del concepto <span className="text-gray-400">(opcional)</span>
                                    </Label>
                                    <textarea
                                        id="description"
                                        className="border-input focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:outline-none"
                                        rows={3}
                                        value={values.description}
                                        onChange={(e) => handleChange('description', e.target.value)}
                                        placeholder="Descripción detallada del concepto de inversión..."
                                    />
                                    <InputError message={errors.description} />
                                </div>
                            </CardContent>
                        </Card>

                        {/* Section 2: Información del Proveedor */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Información del Proveedor</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="provider">Razón Social</Label>
                                        <ProviderAutocomplete
                                            id="provider"
                                            value={values.provider}
                                            field="provider"
                                            placeholder="Razón Social"
                                            onChange={(v) => handleChange('provider', v)}
                                            onSelect={(s) => {
                                                setValues((prev) => ({
                                                    ...prev,
                                                    provider: s.provider,
                                                    rfc: s.rfc ?? prev.rfc,
                                                }));
                                            }}
                                        />
                                        <InputError message={errors.provider} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="rfc">RFC</Label>
                                        <ProviderAutocomplete
                                            id="rfc"
                                            value={values.rfc}
                                            field="rfc"
                                            placeholder="RFC"
                                            maxLength={13}
                                            onChange={(v) => handleChange('rfc', v)}
                                            onSelect={(s) => {
                                                setValues((prev) => ({
                                                    ...prev,
                                                    provider: s.provider,
                                                    rfc: s.rfc ?? prev.rfc,
                                                }));
                                            }}
                                        />
                                        <InputError message={errors.rfc} />
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="contact_name">Nombre del Contacto</Label>
                                    <Input
                                        id="contact_name"
                                        value={values.contact_name}
                                        onChange={(e) => handleChange('contact_name', e.target.value)}
                                        placeholder="Nombre completo del contacto"
                                    />
                                    <InputError message={errors.contact_name} />
                                </div>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="contact_email">Correo del Contacto</Label>
                                        <Input
                                            id="contact_email"
                                            type="email"
                                            value={values.contact_email}
                                            onChange={(e) => handleChange('contact_email', e.target.value)}
                                            placeholder="correo@ejemplo.com"
                                        />
                                        <InputError message={errors.contact_email} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="contact_phone">Teléfono del Contacto</Label>
                                        <Input
                                            id="contact_phone"
                                            value={values.contact_phone}
                                            onChange={(e) => handleChange('contact_phone', e.target.value)}
                                            placeholder="(000) 000-0000"
                                        />
                                        <InputError message={errors.contact_phone} />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Row 2: Datos Financieros + Documentos Adjuntos */}
                    <div className="grid gap-6 lg:grid-cols-2">
                        {/* Section 3: Datos Financieros */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Datos Financieros</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="invoice_folio">
                                        Folio de Factura o Cotización <span className="text-gray-400">(opcional)</span>
                                    </Label>
                                    <Input
                                        id="invoice_folio"
                                        value={values.invoice_folio}
                                        onChange={(e) => handleChange('invoice_folio', e.target.value)}
                                        placeholder="FAC-0001 / COT-0001"
                                    />
                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                        Si no cuenta con folio de factura o cotización, puede dejar este campo en blanco.
                                    </p>
                                    <InputError message={errors.invoice_folio} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Moneda</Label>
                                    <Select
                                        value={values.currency_id}
                                        onValueChange={(v) => handleChange('currency_id', v)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {currencies.map((c) => (
                                                <SelectItem key={c.id} value={String(c.id)}>
                                                    {c.prefix}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.currency_id} />
                                </div>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="subtotal">Subtotal</Label>
                                        <div className="relative">
                                            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">$</span>
                                            <Input
                                                id="subtotal"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                className="pl-7"
                                                value={values.subtotal}
                                                onChange={(e) => handleChange('subtotal', e.target.value)}
                                                placeholder="0.00"
                                            />
                                        </div>
                                        <InputError message={errors.subtotal} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Tasa de IVA</Label>
                                        <Select
                                            value={values.iva_rate}
                                            onValueChange={(v) => handleChange('iva_rate', v)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {ivaRateOptions.map((opt) => (
                                                    <SelectItem key={opt.value} value={opt.value}>
                                                        {opt.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.iva_rate} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="iva">{ivaRateOptions.find((o) => o.value === values.iva_rate)?.label ?? 'IVA'}</Label>
                                        <div className="relative">
                                            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">$</span>
                                            <Input
                                                id="iva"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                className="bg-gray-50 pl-7 dark:bg-gray-800"
                                                value={values.iva}
                                                readOnly
                                                tabIndex={-1}
                                                placeholder="0.00"
                                            />
                                        </div>
                                        <InputError message={errors.iva} />
                                    </div>
                                    <div className="flex items-center gap-2 self-end pb-2">
                                        <Checkbox
                                            id="retention"
                                            checked={values.retention as boolean}
                                            onCheckedChange={(checked) =>
                                                setValues((prev) => ({ ...prev, retention: checked === true }))
                                            }
                                        />
                                        <Label htmlFor="retention" className="cursor-pointer">
                                            Aplica retención
                                        </Label>
                                        <InputError message={errors.retention} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="total">Total</Label>
                                        <div className="relative">
                                            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">$</span>
                                            <Input
                                                id="total"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                className="bg-gray-50 pl-7 dark:bg-gray-800"
                                                value={values.total}
                                                readOnly
                                                tabIndex={-1}
                                                placeholder="0.00"
                                            />
                                        </div>
                                        <InputError message={errors.total} />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Section 4: Documentos Adjuntos */}
                        <Card>
                            <CardHeader>
                                <CardTitle>
                                    Documentos Adjuntos <span className="text-sm font-normal text-gray-400">(opcional)</span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="mb-3 text-xs text-gray-500 dark:text-gray-400">
                                    Es recomendable adjuntar la cotización correspondiente a este concepto de inversión.
                                </p>
                                <FileUpload
                                    files={files}
                                    onChange={setFiles}
                                    maxFiles={10}
                                    error={errors.advance_documents || errors['advance_documents.0']}
                                />
                            </CardContent>
                        </Card>
                    </div>

                    <div className="flex justify-end gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => router.visit('/investment-sheets')}
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Guardando...' : 'Crear Concepto'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
