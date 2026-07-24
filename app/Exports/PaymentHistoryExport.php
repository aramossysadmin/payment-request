<?php

namespace App\Exports;

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

class PaymentHistoryExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    public function __construct(
        private Collection $rows,
        private string $displayPrefix,
    ) {}

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
            'Estatus',
            'F. Programación Pago',
            'Semana',
            'Concepto',
            'Descripción',
            'Categoría',
            'Departamento',
            'Proveedor',
            'RFC',
            'Tipo de Pago',
            "Monto Solicitado ({$this->displayPrefix})",
            "Monto Aprobado ({$this->displayPrefix})",
            'Moneda Nativa',
            'Solicitante',
            'F. Solicitud',
            'Moneda Display',
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return [
            $row['folio'] ?? '—',
            $row['status_label'] ?? '—',
            $row['payment_provision_date'] ?? '—',
            $row['week'] ?? '—',
            $row['concept'] ?? '—',
            $row['description'] ?? '—',
            $row['category'] ?? '—',
            $row['department'] ?? '—',
            $row['provider'] ?? '—',
            $row['rfc'] ?? '—',
            $row['payment_type'] ?? '—',
            (float) ($row['total_display'] ?? 0),
            $row['approved_display'] !== null ? (float) $row['approved_display'] : null,
            $row['native_currency'] ?? '—',
            $row['user'] ?? '—',
            $row['created_at'] ?? '—',
            $this->displayPrefix,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'M' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
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
        return 'Historial';
    }
}
