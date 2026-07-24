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

class InvestmentRequestsConsolidatedExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithMapping, WithStyles, WithTitle
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
            'Departamento',
            'Categoría',
            'Concepto',
            'Folio',
            'Tipo',
            'Estatus',
            'Descripción',
            "Total ({$this->displayPrefix})",
            "Presupuesto de Grupo ({$this->displayPrefix})",
            "Saldo de Grupo ({$this->displayPrefix})",
            'Moneda',
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return [
            $row['department'] ?? '—',
            $row['category'] ?? '—',
            $row['concept'] ?? '—',
            $row['folio'] ?? '—',
            $row['type'] ?? '—',
            $row['status'] ?? '—',
            $row['description'] ?? '—',
            (float) ($row['total_display'] ?? 0),
            (float) ($row['group_budget'] ?? 0),
            (float) ($row['group_remaining'] ?? 0),
            $this->displayPrefix,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
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
        return 'Solicitudes';
    }
}
