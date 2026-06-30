import { Head, router, usePage } from '@inertiajs/react';
import { Building2, CheckIcon, ChevronsUpDownIcon, ClipboardList, FileText, Info, Paperclip, Wallet } from 'lucide-react';
import { useMemo, useState, type FormEvent } from 'react';
import { FileUpload } from '@/components/file-upload';
import InputError from '@/components/input-error';
import { ProviderAutocomplete } from '@/components/provider-autocomplete';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
    { title: 'Solicitudes De Inversión', href: '/investment-sheets' },
    { title: 'Nueva Solicitud De Inversión', href: '/investment-sheets/create' },
];

const ivaRateOptions = [
    { value: '0.00', label: 'IVA 0%' },
    { value: '0.04', label: 'IVA 4%' },
    { value: '0.08', label: 'IVA 8%' },
    { value: '0.16', label: 'IVA 16%' },
    { value: '0.21', label: 'IVA 21%' },
];

type DepartmentRef = { id: number; name: string };

type InvestmentExpenseConceptOption = {
    id: number;
    name: string;
    category?: { id: number; name: string; departments?: DepartmentRef[] } | null;
};

type UserDepartment = { id: number; name: string };

type PageProps = {
    currencies: Currency[];
    branches: Branch[];
    investmentExpenseConcepts: InvestmentExpenseConceptOption[];
    projects: Project[];
    userDepartments: UserDepartment[];
    errors: Record<string, string>;
};

export default function Create() {
    const showProviderInfo = false;
    const showRetention = false;

    const { currencies, branches, investmentExpenseConcepts, projects, userDepartments, errors } =
        usePage<PageProps>().props;

    const isMultiDept = (userDepartments?.length ?? 0) > 1;
    const monoDeptId = !isMultiDept && (userDepartments?.length ?? 0) === 1
        ? String(userDepartments[0].id)
        : '';

    const [values, setValues] = useState({
        provider: '',
        rfc: '',
        contact_name: '',
        contact_email: '',
        contact_phone: '',
        invoice_folio: '',
        currency_id: '',
        branch_id: '',
        department_id: monoDeptId,
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
    const [processing, setProcessing] = useState(false);
    const [files, setFiles] = useState<File[]>([]);

    // Filtrar conceptos según el departamento elegido (si multi-dpto) o mostrar todos
    // los del user (si mono-dpto, ya vienen filtrados del backend). Siempre ordenados
    // A-Z (localeCompare es-MX). useMemo evita re-filtrar/ordenar en cada render del
    // formulario (cada keystroke dispara setValues).
    const filteredConcepts = useMemo(() => {
        const base = isMultiDept && values.department_id
            ? investmentExpenseConcepts.filter((ec) =>
                ec.category?.departments?.some((d) => String(d.id) === values.department_id),
              )
            : investmentExpenseConcepts;

        return [...base].sort((a, b) => a.name.localeCompare(b.name, 'es-MX', { sensitivity: 'base' }));
    }, [isMultiDept, values.department_id, investmentExpenseConcepts]);

    const selectedBranchName = branches.find((b) => String(b.id) === values.branch_id)?.name;

    const handleDepartmentChange = (deptId: string) => {
        // Cambiar dpto resetea el concepto (puede no aplicar al nuevo dpto).
        setValues((prev) => ({ ...prev, department_id: deptId, investment_expense_concept_id: '' }));
    };

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
            <Head title="Nueva Solicitud De Inversión" />

            <div className="p-4 md:p-6">
                <div className="mb-6">
                    <h1 className="flex items-center gap-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                        <ClipboardList className="h-6 w-6 text-muted-foreground" />
                        Nueva Solicitud De Inversión
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Captura el concepto, el presupuesto y los documentos de una nueva solicitud de inversión.
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Row 1: Datos Generales */}
                    <div className="grid gap-6">
                        {/* Section 1: Datos Generales del Concepto de Inversión */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <FileText className="h-4 w-4 text-muted-foreground" />
                                    Datos Generales de la Solicitud De Inversión
                                </CardTitle>
                                <CardDescription>Proyecto, sucursal, concepto de inversión y descripción.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
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
                                        <Label id="sucursal-label">Sucursal</Label>
                                        <div
                                            aria-labelledby="sucursal-label"
                                            className="flex h-9 items-center rounded-md border border-input bg-muted/40 px-3 text-sm"
                                        >
                                            {selectedBranchName ? (
                                                <span className="font-medium text-foreground">{selectedBranchName}</span>
                                            ) : (
                                                <span className="text-muted-foreground">Se asigna al seleccionar proyecto</span>
                                            )}
                                        </div>
                                        <InputError message={errors.branch_id} />
                                    </div>
                                </div>
                                {isMultiDept && (
                                    <div className="space-y-2">
                                        <Label>
                                            Departamento <span className="text-red-500">*</span>
                                        </Label>
                                        <Select value={values.department_id} onValueChange={handleDepartmentChange}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar departamento..." />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {userDepartments.map((d) => (
                                                    <SelectItem key={d.id} value={String(d.id)}>
                                                        {d.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <p className="text-xs text-muted-foreground">
                                            Eliges desde qué departamento capturas esta solicitud. Al cambiar el departamento se reinicia el concepto.
                                        </p>
                                        <InputError message={errors.department_id} />
                                    </div>
                                )}
                                <div className="space-y-2">
                                    <Label>Concepto de Inversión</Label>
                                    <Popover open={expenseConceptOpen} onOpenChange={setExpenseConceptOpen}>
                                        <PopoverTrigger asChild>
                                            <Button
                                                variant="outline"
                                                role="combobox"
                                                aria-expanded={expenseConceptOpen}
                                                disabled={isMultiDept && !values.department_id}
                                                className="w-full justify-between font-normal"
                                            >
                                                {values.investment_expense_concept_id
                                                    ? (() => {
                                                        const selected = filteredConcepts.find(
                                                            (ec) => String(ec.id) === values.investment_expense_concept_id,
                                                        );
                                                        return selected
                                                            ? `${selected.category?.name ?? ''} - ${selected.name}`
                                                            : 'Seleccionar';
                                                    })()
                                                    : isMultiDept && !values.department_id
                                                        ? 'Selecciona primero un departamento'
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
                                                        {filteredConcepts.map((ec) => (
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
                                        className="border-input focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border bg-transparent px-3 py-2 text-sm uppercase shadow-xs focus-visible:ring-[3px] focus-visible:outline-none"
                                        rows={3}
                                        value={values.description}
                                        onChange={(e) => handleChange('description', e.target.value.toUpperCase())}
                                        placeholder="Descripción detallada del concepto de inversión..."
                                    />
                                    <InputError message={errors.description} />
                                </div>
                            </CardContent>
                        </Card>

                        {/* Section 2: Información del Proveedor — oculta, se captura en edición */}
                        {showProviderInfo && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Building2 className="h-4 w-4 text-muted-foreground" />
                                    Información del Proveedor
                                </CardTitle>
                                <CardDescription>Datos fiscales y de contacto del proveedor.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4 sm:grid-cols-2">
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
                        )}
                    </div>

                    {/* Row 2: Datos del Presupuesto + Documentos Adjuntos */}
                    <div className="grid gap-6 lg:grid-cols-2">
                        {/* Section 3: Datos del Presupuesto */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Wallet className="h-4 w-4 text-muted-foreground" />
                                    Datos del Presupuesto
                                </CardTitle>
                                <CardDescription>Cotización, moneda y montos. El IVA y el total se calculan automáticamente.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="invoice_folio">
                                        Folio de Cotización <span className="text-gray-400">(opcional)</span>
                                    </Label>
                                    <Input
                                        id="invoice_folio"
                                        value={values.invoice_folio}
                                        onChange={(e) => handleChange('invoice_folio', e.target.value)}
                                        placeholder="COT-0001"
                                    />
                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                        Si no cuenta con folio de cotización, puede dejar este campo en blanco.
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
                                    {/* Campo Aplica retención — oculto, se captura en edición si aplica */}
                                    {showRetention && (
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
                                    )}
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
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Paperclip className="h-4 w-4 text-muted-foreground" />
                                    Documentos Adjuntos <span className="text-sm font-normal text-muted-foreground">(opcional)</span>
                                </CardTitle>
                                <CardDescription>Adjunta archivos (PDF o imagen).</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="mb-3 flex items-start gap-2 rounded-md border border-primary/30 bg-primary/5 px-3 py-2 text-sm text-foreground">
                                    <Info className="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                                    <p>
                                        Aunque adjuntar documentos es opcional, si ya cuentas con la{' '}
                                        <span className="font-medium">cotización</span> es importante adjuntarla desde este paso.
                                    </p>
                                </div>
                                <FileUpload
                                    files={files}
                                    onChange={setFiles}
                                    maxFiles={5}
                                    accept=".pdf,.jpg,.jpeg,.png"
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
