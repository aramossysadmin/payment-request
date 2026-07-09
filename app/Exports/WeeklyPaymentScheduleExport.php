<?php

namespace App\Exports;

use App\Models\InvestmentPaymentRequest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WeeklyPaymentScheduleExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithMapping, WithStyles
{
    private const PAYMENT_TYPE_LABELS = [
        'factura' => 'Factura',
        'anticipo' => 'Anticipo',
        'reembolso' => 'Reembolso',
        'estrategia' => 'Estrategia',
        'cotizacion' => 'Cotización',
        'pagare' => 'Pagaré',
        'domiciliado' => 'Domiciliado',
        'factura_espana' => 'Factura España',
    ];

    private const STATUS_LABELS = [
        'approved' => 'Aprobado',
        'completed' => 'Completado',
        'scheduled_for_bank' => 'Programado en banco',
        'receipt_attached' => 'Comprobante adjunto',
    ];

    public function __construct(private Collection $payments) {}

    public function collection(): Collection
    {
        return $this->payments;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Semana', 'Folio', 'Proveedor', 'Concepto', 'Tipo de pago', 'Fecha Provisión', 'Total', 'Moneda', 'Estatus', 'Documentos', 'Comprobante'];
    }

    /**
     * @return array<int, mixed>
     */
    public function map($payment): array
    {
        /** @var InvestmentPaymentRequest $payment */
        $docsCount = is_array($payment->advance_documents)
            ? count(array_filter($payment->advance_documents, fn ($d) => is_string($d) && $d !== ''))
            : 0;
        $receiptCount = is_array($payment->payment_receipt_documents)
            ? count(array_filter($payment->payment_receipt_documents, fn ($d) => is_string($d) && $d !== ''))
            : 0;

        return [
            'S'.$payment->payment_week_number.'/'.($payment->payment_provision_date?->isoWeekYear ?? ''),
            '#'.str_pad((string) $payment->folio_number, 5, '0', STR_PAD_LEFT),
            $payment->provider,
            $payment->investmentRequest?->investmentExpenseConcept?->name ?? '-',
            self::PAYMENT_TYPE_LABELS[$payment->payment_type] ?? $payment->payment_type,
            $payment->payment_provision_date?->format('d/m/Y') ?? '-',
            (float) ($payment->approved_amount ?? $payment->total),
            $payment->currency?->prefix ?? 'MXN',
            self::STATUS_LABELS[$payment->status] ?? $payment->status,
            $docsCount,
            $receiptCount > 0 ? "Sí ({$receiptCount})" : '—',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
