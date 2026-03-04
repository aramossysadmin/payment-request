export function formatCurrency(amount: string | number, prefix: string = '$'): string {
    const num = typeof amount === 'string' ? parseFloat(amount) : amount;

    if (isNaN(num)) {
        return `${prefix}0.00`;
    }

    return `${prefix}${num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
}
