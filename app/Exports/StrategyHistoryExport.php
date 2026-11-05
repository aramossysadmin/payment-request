<?php

namespace App\Exports;

use App\Enums\InvestmentPaymentType;
use App\Services\StrategyPaymentHistory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StrategyHistoryExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    public function __construct(private Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Folio',
            'Hoja de Inversión',
            'Departamento',
            'Solicitante',
            'Concepto',
            'Proveedor',
            'Código',
            'Tasa (%)',
            'Monto Base',
            'Recargo',
            'Total',
            'Aprobado',
            'Moneda',
            'Tipo',
            'Estado',
            'Fecha Captura',
            'Semana Captura',
            'Fecha Programación',
            'Semana Programación',
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        $type = InvestmentPaymentType::labelFor($row['payment_type']);

        return [
            '#'.str_pad((string) $row['folio_number'], 5, '0', STR_PAD_LEFT),
            $row['project_name'] ?? '—',
            $row['department_name'] ?? '—',
            $row['requester_name'] ?? '—',
            $row['concept_name'] ?? '—',
            $row['provider'] ?? '—',
            $row['strategy_rate'] === null ? 'Anterior al catálogo' : ($row['strategy_name'] ?? '—'),
            $row['strategy_rate'] !== null ? (float) $row['strategy_rate'] : null,
            $row['strategy_amount'] !== null ? (float) $row['strategy_amount'] : null,
            $row['strategy_surcharge'] !== null ? (float) $row['strategy_surcharge'] : null,
            (float) $row['total'],
            $row['approved_amount'] !== null ? (float) $row['approved_amount'] : null,
            $row['currency_prefix'],
            $row['payment_type'] === InvestmentPaymentType::Estrategia->value ? $type : "Tipo actual: {$type}",
            StrategyPaymentHistory::STATUS_LABELS[$row['status']] ?? $row['status'],
            $row['captured_at'] ?? '—',
            $row['capture_week'] ?? '—',
            $row['payment_provision_date'] ?? '—',
            $row['provision_week'] ?? '—',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
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

    public function title(): string
    {
        return 'Estrategias';
    }
}
