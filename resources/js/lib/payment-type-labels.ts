export const investmentPaymentTypeLabels: Record<string, string> = {
    factura: 'Factura',
    reembolso: 'Reembolso',
    estrategia: 'Estrategia',
    anticipo: 'Anticipo',
};

export function investmentPaymentTypeLabel(type?: string | null): string {
    return investmentPaymentTypeLabels[type ?? ''] ?? '—';
}
