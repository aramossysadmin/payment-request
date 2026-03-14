import { Head, router, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { FileUpload } from '@/components/file-upload';
import InputError from '@/components/input-error';
import { ProviderAutocomplete } from '@/components/provider-autocomplete';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type {
    BreadcrumbItem,
    Branch,
    Currency,
    ExpenseConcept,
    PaymentRequest,
} from '@/types';

const paymentTypeOptions = [
    { value: 'invoice', label: 'Pago con Factura' },
    { value: 'advance', label: 'Anticipo' },
    { value: 'investment', label: 'Inversiones' },
];

const ivaRateOptions = [
    { value: '0.00', label: 'IVA 0%' },
    { value: '0.08', label: 'IVA 8%' },
    { value: '0.16', label: 'IVA 16%' },
];

type PageProps = {
    paymentRequest: { data: PaymentRequest };
    currencies: Currency[];
    branches: Branch[];
    expenseConcepts: ExpenseConcept[];
    errors: Record<string, string>;
};

export default function Edit() {
    const {
        paymentRequest: resource,
        currencies,
        branches,
        expenseConcepts,
        errors,
    } = usePage<PageProps>().props;
    const pr = resource.data;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Solicitudes de Pago', href: '/payment-requests' },
        {
            title: `#${String(pr.folio_number).padStart(5, '0')}`,
            href: `/payment-requests/${pr.uuid}`,
        },
        {
            title: 'Editar',
            href: `/payment-requests/${pr.uuid}/edit`,
        },
    ];

    const [values, setValues] = useState({
        provider: pr.provider,
        rfc: pr.rfc ?? '',
        invoice_folio: pr.invoice_folio,
        currency_id: String(pr.currency?.id ?? ''),
        branch_id: String(pr.branch?.id ?? ''),
        expense_concept_id: String(pr.expense_concept?.id ?? ''),
        description: pr.description ?? '',
        payment_type: pr.payment_type.value,
        subtotal: pr.subtotal,
        iva_rate: pr.iva_rate.value,
        iva: pr.iva,
        retention: pr.retention,
        total: pr.total,
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
        if (field === 'payment_type') {
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
        formData.append('_method', 'PUT');
        Object.entries(values).forEach(([key, val]) => {
            formData.append(key, typeof val === 'boolean' ? (val ? '1' : '0') : String(val));
        });

        if (values.payment_type === 'invoice') {
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

        router.post(`/payment-requests/${pr.uuid}`, formData, {
            forceFormData: true,
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`Editar Solicitud #${String(pr.folio_number).padStart(5, '0')}`}
            />

            <div className="mx-auto max-w-3xl p-4 md:p-6">
                <h1 className="mb-6 text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                    Editar Solicitud #{String(pr.folio_number).padStart(5, '0')}
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
                                    />
                                    <InputError
                                        message={errors.invoice_folio}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-3">
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
                                    <Label>Sucursal</Label>
                                    <Select
                                        value={values.branch_id}
                                        onValueChange={(v) =>
                                            handleChange('branch_id', v)
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {branches.map((b) => (
                                                <SelectItem
                                                    key={b.id}
                                                    value={String(b.id)}
                                                >
                                                    {b.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.branch_id} />
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
                                    value={values.payment_type}
                                    onValueChange={(v) =>
                                        handleChange('payment_type', v)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {paymentTypeOptions.map((opt) => (
                                            <SelectItem
                                                key={opt.value}
                                                value={opt.value}
                                            >
                                                {opt.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.payment_type} />
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
                            {pr.advance_documents &&
                                pr.advance_documents.length > 0 && (
                                    <div className="mb-4">
                                        <p className="mb-2 text-sm text-gray-500 dark:text-gray-400">
                                            Documentos actuales:
                                        </p>
                                        <ul className="space-y-1">
                                            {pr.advance_documents.map(
                                                (doc, i) => (
                                                    <li
                                                        key={i}
                                                        className="text-sm text-gray-700 dark:text-gray-300"
                                                    >
                                                        {doc
                                                            .split('/')
                                                            .pop()}
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    </div>
                                )}
                            {values.payment_type === 'invoice' ? (
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
                                router.visit(`/payment-requests/${pr.uuid}`)
                            }
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing
                                ? 'Guardando...'
                                : 'Guardar Cambios'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
