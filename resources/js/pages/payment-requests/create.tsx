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
import type { BreadcrumbItem, Branch, Currency, ExpenseConcept, PaymentTypeOption } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Solicitudes de Pago', href: '/payment-requests' },
    { title: 'Nueva Solicitud', href: '/payment-requests/create' },
];

const ivaRateOptions = [
    { value: '0.00', label: 'IVA 0%' },
    { value: '0.08', label: 'IVA 8%' },
    { value: '0.16', label: 'IVA 16%' },
];

type PageProps = {
    currencies: Currency[];
    branches: Branch[];
    expenseConcepts: ExpenseConcept[];
    paymentTypes: PaymentTypeOption[];
    errors: Record<string, string>;
};

export default function Create() {
    const { currencies, branches, expenseConcepts, paymentTypes, errors } =
        usePage<PageProps>().props;

    const [values, setValues] = useState({
        provider: '',
        rfc: '',
        invoice_folio: '',
        currency_id: '',
        branch_id: '',
        expense_concept_id: '',
        description: '',
        payment_type_id: '',
        subtotal: '',
        iva_rate: '',
        iva: '',
        retention: false,
        total: '',
    });

    const [files, setFiles] = useState<File[]>([]);
    const [invoicePdf, setInvoicePdf] = useState<File | null>(null);
    const [invoiceXml, setInvoiceXml] = useState<File | null>(null);
    const [processing, setProcessing] = useState(false);

    const recalculate = (subtotal: number, ivaRate: number) => {
        const iva = Math.round(subtotal * ivaRate * 100) / 100;
        const total = Math.round((subtotal + iva) * 100) / 100;
        return { iva: iva.toFixed(2), total: total.toFixed(2) };
    };

    const handleChange = (field: string, value: string) => {
        if (field === 'subtotal') {
            const subtotal = parseFloat(value) || 0;
            const { iva, total } = recalculate(subtotal, parseFloat(values.iva_rate) || 0);
            setValues((prev) => ({
                ...prev,
                subtotal: value,
                iva,
                total,
            }));
            return;
        }
        if (field === 'iva_rate') {
            const subtotal = parseFloat(values.subtotal) || 0;
            const { iva, total } = recalculate(subtotal, parseFloat(value) || 0);
            setValues((prev) => ({
                ...prev,
                iva_rate: value,
                iva,
                total,
            }));
            return;
        }
        if (field === 'payment_type_id') {
            setFiles([]);
            setInvoicePdf(null);
            setInvoiceXml(null);
        }
        setValues((prev) => ({ ...prev, [field]: value }));
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        const formData = new FormData();
        Object.entries(values).forEach(([key, val]) => {
            formData.append(key, typeof val === 'boolean' ? (val ? '1' : '0') : String(val));
        });

        const selectedType = paymentTypes.find((pt) => String(pt.id) === values.payment_type_id);
        if (selectedType?.requires_invoice_documents) {
            if (invoicePdf) {
                formData.append('advance_documents[]', invoicePdf);
            }
            if (invoiceXml) {
                formData.append('advance_documents[]', invoiceXml);
            }
        } else {
            files.forEach((file) => {
                formData.append('advance_documents[]', file);
            });
        }

        router.post('/payment-requests', formData, {
            forceFormData: true,
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva Solicitud de Pago" />

            <div className="mx-auto max-w-3xl p-4 md:p-6">
                <h1 className="mb-6 text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                    Nueva Solicitud de Pago
                </h1>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Información General</CardTitle>
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
                                        onChange={(v) =>
                                            handleChange('provider', v)
                                        }
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
                                        onChange={(v) =>
                                            handleChange('rfc', v)
                                        }
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
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="invoice_folio">
                                        Folio de Factura
                                    </Label>
                                    <Input
                                        id="invoice_folio"
                                        value={values.invoice_folio}
                                        onChange={(e) =>
                                            handleChange(
                                                'invoice_folio',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="FAC-0001"
                                    />
                                    <InputError
                                        message={errors.invoice_folio}
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label>Sucursal</Label>
                                <BranchCombobox
                                    branches={branches}
                                    value={values.branch_id}
                                    onChange={(v) =>
                                        handleChange('branch_id', v)
                                    }
                                />
                                <InputError message={errors.branch_id} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Moneda</Label>
                                    <Select
                                        value={values.currency_id}
                                        onValueChange={(v) =>
                                            handleChange('currency_id', v)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {currencies.map((c) => (
                                                <SelectItem
                                                    key={c.id}
                                                    value={String(c.id)}
                                                >
                                                    {c.prefix}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.currency_id} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Concepto de Gasto</Label>
                                    <Select
                                        value={values.expense_concept_id}
                                        onValueChange={(v) =>
                                            handleChange(
                                                'expense_concept_id',
                                                v,
                                            )
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {expenseConcepts.map((ec) => (
                                                <SelectItem
                                                    key={ec.id}
                                                    value={String(ec.id)}
                                                >
                                                    {ec.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={errors.expense_concept_id}
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label>Tipo de Pago</Label>
                                <Select
                                    value={values.payment_type_id}
                                    onValueChange={(v) =>
                                        handleChange('payment_type_id', v)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleccionar" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {paymentTypes.map((pt) => (
                                            <SelectItem
                                                key={pt.id}
                                                value={String(pt.id)}
                                            >
                                                {pt.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.payment_type_id} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">
                                    Descripción{' '}
                                    <span className="text-gray-400">
                                        (opcional)
                                    </span>
                                </Label>
                                <textarea
                                    id="description"
                                    className="border-input focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:outline-none"
                                    rows={3}
                                    value={values.description}
                                    onChange={(e) =>
                                        handleChange(
                                            'description',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Notas adicionales..."
                                />
                                <InputError message={errors.description} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Documentos Solicitudes de Pago</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {paymentTypes.find((pt) => String(pt.id) === values.payment_type_id)?.requires_invoice_documents ? (
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label>Factura PDF <span className="text-red-500">*</span></Label>
                                        <FileUpload
                                            files={invoicePdf ? [invoicePdf] : []}
                                            onChange={(f) => setInvoicePdf(f[0] ?? null)}
                                            maxFiles={1}
                                            accept=".pdf"
                                            error={errors['advance_documents'] || errors['advance_documents.0']}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Factura XML <span className="text-red-500">*</span></Label>
                                        <FileUpload
                                            files={invoiceXml ? [invoiceXml] : []}
                                            onChange={(f) => setInvoiceXml(f[0] ?? null)}
                                            maxFiles={1}
                                            accept=".xml"
                                            error={errors['advance_documents.1']}
                                        />
                                    </div>
                                </div>
                            ) : (
                                <FileUpload
                                    files={files}
                                    onChange={setFiles}
                                    maxFiles={10}
                                    error={errors.advance_documents || errors['advance_documents.0']}
                                />
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Montos</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="subtotal">Subtotal</Label>
                                    <div className="relative">
                                        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">
                                            $
                                        </span>
                                        <Input
                                            id="subtotal"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            className="pl-7"
                                            value={values.subtotal}
                                            onChange={(e) =>
                                                handleChange(
                                                    'subtotal',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="0.00"
                                        />
                                    </div>
                                    <InputError message={errors.subtotal} />
                                </div>
                                <div className="space-y-2">
                                    <Label>Tasa de IVA</Label>
                                    <Select
                                        value={values.iva_rate}
                                        onValueChange={(v) =>
                                            handleChange('iva_rate', v)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {ivaRateOptions.map((opt) => (
                                                <SelectItem
                                                    key={opt.value}
                                                    value={opt.value}
                                                >
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
                                        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">
                                            $
                                        </span>
                                        <Input
                                            id="iva"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            className="pl-7 bg-gray-50 dark:bg-gray-800"
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
                                            setValues((prev) => ({
                                                ...prev,
                                                retention: checked === true,
                                            }))
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
                                        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500">
                                            $
                                        </span>
                                        <Input
                                            id="total"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            className="pl-7 bg-gray-50 dark:bg-gray-800"
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

                    <div className="flex justify-end gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() =>
                                router.visit('/payment-requests')
                            }
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Guardando...' : 'Crear Solicitud'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}

function BranchCombobox({
    branches,
    value,
    onChange,
}: {
    branches: Branch[];
    value: string;
    onChange: (value: string) => void;
}) {
    const [open, setOpen] = useState(false);
    const selectedBranch = branches.find((b) => String(b.id) === value);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className="w-full justify-between font-normal"
                >
                    {selectedBranch?.name ?? 'Seleccionar'}
                    <ChevronsUpDownIcon className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-[--radix-popover-trigger-width] p-0" align="start">
                <Command>
                    <CommandInput placeholder="Buscar sucursal..." />
                    <CommandList>
                        <CommandEmpty>Sin resultados.</CommandEmpty>
                        <CommandGroup>
                            {branches.map((b) => (
                                <CommandItem
                                    key={b.id}
                                    value={b.name}
                                    onSelect={() => {
                                        onChange(String(b.id));
                                        setOpen(false);
                                    }}
                                >
                                    <CheckIcon
                                        className={cn(
                                            'mr-2 h-4 w-4',
                                            value === String(b.id)
                                                ? 'opacity-100'
                                                : 'opacity-0',
                                        )}
                                    />
                                    {b.name}
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
