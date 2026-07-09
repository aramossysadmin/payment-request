<?php

namespace App\Console\Commands;

use App\Models\InvestmentPaymentRequest;
use App\Models\InvestmentRequest;
use Illuminate\Console\Command;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class GenerateInvestmentPaymentsTemplateCommand extends Command
{
    protected $signature = 'investment:generate-payments-template';

    protected $description = 'Generate an XLSX template for bulk loading historical investment payments. Read-only: queries the database and writes the file to public/downloads/templates/.';

    private const OUTPUT_FILENAME = 'investment_payments_historical.xlsx';

    /**
     * @var list<string>
     */
    private const PAGOS_HEADERS = [
        'investment_request_folio',
        'provider',
        'rfc',
        'invoice_folio',
        'payment_type',
        'payment_provision_date',
        'description',
        'subtotal',
        'iva_rate',
        'iva',
        'retention',
        'total',
        'created_by_email',
        'currency',
        'branch',
        'created_at',
    ];

    /**
     * @var list<string>
     */
    private const PAGOS_LABELS_ES = [
        'Folio del Concepto',
        'Razón Social',
        'RFC',
        'Folio de Factura',
        'Tipo de Pago',
        'Fecha de Pago / Previsión',
        'Descripción',
        'Subtotal',
        'Tasa de IVA',
        'IVA',
        'Retención',
        'Total',
        'Email del Solicitante',
        'Moneda',
        'Sucursal',
        'Fecha de Creación',
    ];

    /**
     * @var list<string>
     */
    private const REFERENCE_HEADERS = [
        'folio_number',
        'provider',
        'description',
        'project',
        'investment_expense_concept',
        'concept_category',
        'branch',
        'currency',
        'total_concept',
        'paid_to_date',
        'remaining_balance',
        'created_by_email',
    ];

    /**
     * @var list<string>
     */
    private const REFERENCE_LABELS_ES = [
        'Folio',
        'Proveedor',
        'Descripción',
        'Proyecto',
        'Gasto de Inversión',
        'Categoría',
        'Sucursal',
        'Moneda',
        'Total del Concepto',
        'Pagado al Día',
        'Saldo Disponible',
        'Email del Solicitante',
    ];

    public function handle(): int
    {
        $outputPath = public_path('downloads/templates/'.self::OUTPUT_FILENAME);
        $directory = dirname($outputPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $writer = new Writer;
        $writer->openToFile($outputPath);

        $headerStyle = (new Style)
            ->setFontBold()
            ->setBackgroundColor('D9E1F2');

        $labelEsStyle = (new Style)
            ->setFontItalic()
            ->setBackgroundColor('F2F2F2');

        $titleStyle = (new Style)
            ->setFontBold()
            ->setFontSize(13);

        $writer->getCurrentSheet()->setName('instrucciones');
        $this->writeInstructionsSheet($writer, $titleStyle, $headerStyle);

        $writer->addNewSheetAndMakeItCurrent()->setName('pagos');
        $writer->addRow(Row::fromValues(self::PAGOS_HEADERS, $headerStyle));
        $writer->addRow(Row::fromValues(self::PAGOS_LABELS_ES, $labelEsStyle));

        $writer->addNewSheetAndMakeItCurrent()->setName('conceptos_referencia');
        $writer->addRow(Row::fromValues(self::REFERENCE_HEADERS, $headerStyle));
        $writer->addRow(Row::fromValues(self::REFERENCE_LABELS_ES, $labelEsStyle));
        $rowCount = $this->writeReferenceData($writer);

        $writer->close();

        $this->info("Template generado: {$outputPath}");
        $this->info('Conceptos en hoja de referencia: '.$rowCount);
        $this->info('URL pública: '.url('/downloads/templates/'.self::OUTPUT_FILENAME));

        return self::SUCCESS;
    }

    private function writeInstructionsSheet(Writer $writer, Style $titleStyle, Style $headerStyle): void
    {
        $writer->addRow(Row::fromValues(['CARGA MASIVA DE PAGOS DE INVERSIÓN — INSTRUCCIONES'], $titleStyle));
        $writer->addRow(Row::fromValues(['']));

        $writer->addRow(Row::fromValues(['Cómo usar este template:'], $titleStyle));
        $generalNotes = [
            '1. Llena la hoja "pagos" a partir de la fila 3. La fila 1 son los nombres técnicos (los lee el seeder) y la fila 2 son las etiquetas en español (informativas — el seeder las ignora).',
            '2. Identifica el concepto padre con la columna "investment_request_folio". Usa la hoja "conceptos_referencia" para conocer los folios disponibles y sus saldos.',
            '3. Todos los pagos se cargarán con estatus "approved" automáticamente. El seeder generará también la fila correspondiente en investment_payment_approvals con estatus "approved" (sin envío de notificaciones).',
            '4. La columna "payment_provision_date" debe ir en formato YYYY-MM-DD. Es la fecha real del pago si ya se efectuó, o la fecha de previsión si está por pagarse.',
            '5. La validación de saldo se hace por GRUPO (mismo proyecto + mismo gasto de inversión). El seeder rechaza filas que excedan el saldo del grupo.',
            '6. NO se cargan archivos PDF/XML en esta carga.',
            '7. Si dejas vacías "currency" o "branch", el seeder las hereda del concepto padre.',
        ];
        foreach ($generalNotes as $note) {
            $writer->addRow(Row::fromValues([$note]));
        }

        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValues(['Diccionario de columnas — hoja "pagos":'], $titleStyle));
        $writer->addRow(Row::fromValues(['Columna', 'Obligatoria', 'Formato / valores válidos', 'Notas'], $headerStyle));

        $pagosDictionary = [
            ['investment_request_folio', 'Sí', 'Entero', 'Folio del concepto padre. Ver hoja conceptos_referencia.'],
            ['provider', 'Sí', 'Texto', 'Razón social del proveedor.'],
            ['rfc', 'No', '12 o 13 caracteres alfanuméricos', 'RFC del proveedor.'],
            ['invoice_folio', 'No', 'Texto', 'Folio fiscal de la factura. Vacío para anticipos.'],
            ['payment_type', 'Sí', 'factura  |  reembolso  |  estrategia  |  anticipo  |  cotizacion  |  pagare  |  domiciliado  |  factura_espana', 'Tipo de pago.'],
            ['payment_provision_date', 'Sí', 'YYYY-MM-DD', 'Fecha real del pago, o de previsión si aún no se ejecuta.'],
            ['description', 'No', 'Texto', 'Descripción libre del pago.'],
            ['subtotal', 'Sí', 'Número decimal ≥ 0', 'Subtotal sin IVA.'],
            ['iva_rate', 'Sí', '0.00  |  0.04  |  0.08  |  0.10  |  0.16  |  0.21', 'Tasa de IVA aplicada.'],
            ['iva', 'Sí', 'Número decimal ≥ 0', 'Monto de IVA. Si iva_rate = 0.00 entonces iva = 0.00.'],
            ['retention', 'No', '0  |  1', 'Aplica retención. Default 0.'],
            ['total', 'Sí', 'Número decimal ≥ 0', 'Subtotal + IVA. Debe coincidir aritméticamente.'],
            ['created_by_email', 'Sí', 'Email', 'Solicitante. De aquí se deriva user_id y department_id.'],
            ['currency', 'No', 'Nombre exacto del catálogo', 'Si vacío, hereda del concepto padre.'],
            ['branch', 'No', 'Nombre exacto del catálogo', 'Si vacío, hereda del concepto padre.'],
            ['created_at', 'No', 'YYYY-MM-DD o YYYY-MM-DD HH:MM:SS', 'Si vacío, se usa la fecha actual al cargar.'],
        ];
        foreach ($pagosDictionary as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValues(['Diccionario de columnas — hoja "conceptos_referencia":'], $titleStyle));
        $writer->addRow(Row::fromValues(['Columna', 'Significado'], $headerStyle));

        $referenceDictionary = [
            ['folio_number', 'Folio del concepto padre. Cópialo en investment_request_folio al llenar pagos.'],
            ['provider', 'Proveedor del concepto (puede estar vacío en el histórico).'],
            ['description', 'Descripción del concepto.'],
            ['project', 'Proyecto.'],
            ['investment_expense_concept', 'Gasto de Inversión.'],
            ['concept_category', 'Categoría del gasto de inversión.'],
            ['branch', 'Sucursal del concepto.'],
            ['currency', 'Moneda del concepto.'],
            ['total_concept', 'Monto total del concepto individual.'],
            ['paid_to_date', 'Suma de pagos (status approved + pending_approval) ya cargados al GRUPO al que pertenece este concepto.'],
            ['remaining_balance', 'Saldo del grupo disponible para nuevos pagos. Es el tope que el seeder valida.'],
            ['created_by_email', 'Solicitante original del concepto.'],
        ];
        foreach ($referenceDictionary as $row) {
            $writer->addRow(Row::fromValues($row));
        }
    }

    private function writeReferenceData(Writer $writer): int
    {
        $concepts = InvestmentRequest::query()
            ->with([
                'project:id,name',
                'investmentExpenseConcept:id,name,investment_expense_category_id',
                'investmentExpenseConcept.category:id,name',
                'branch:id,name',
                'currency:id,name',
                'user:id,email',
            ])
            ->orderBy('folio_number')
            ->get();

        $groupBudgets = [];
        $groupPaid = [];

        foreach ($concepts as $concept) {
            $key = $this->groupKey($concept);
            if ($key === null) {
                continue;
            }
            $groupBudgets[$key] = ($groupBudgets[$key] ?? 0.0) + (float) $concept->total;
        }

        $paymentSums = InvestmentPaymentRequest::query()
            ->whereIn('status', ['pending_approval', 'approved'])
            ->selectRaw('investment_request_id, SUM(total) as paid_total')
            ->groupBy('investment_request_id')
            ->pluck('paid_total', 'investment_request_id')
            ->all();

        foreach ($concepts as $concept) {
            $key = $this->groupKey($concept);
            $paid = (float) ($paymentSums[$concept->id] ?? 0);
            if ($key !== null) {
                $groupPaid[$key] = ($groupPaid[$key] ?? 0.0) + $paid;
            }
        }

        $rowCount = 0;
        foreach ($concepts as $concept) {
            $key = $this->groupKey($concept);

            if ($key !== null) {
                $totalConcept = (float) $concept->total;
                $paidGroup = $groupPaid[$key] ?? 0.0;
                $budgetGroup = $groupBudgets[$key] ?? 0.0;
                $remaining = $budgetGroup - $paidGroup;
            } else {
                $totalConcept = (float) $concept->total;
                $paidGroup = (float) ($paymentSums[$concept->id] ?? 0);
                $remaining = $totalConcept - $paidGroup;
            }

            $writer->addRow(Row::fromValues([
                (string) $concept->folio_number,
                (string) ($concept->provider ?? ''),
                (string) ($concept->description ?? ''),
                (string) ($concept->project?->name ?? ''),
                (string) ($concept->investmentExpenseConcept?->name ?? ''),
                (string) ($concept->investmentExpenseConcept?->category?->name ?? ''),
                (string) ($concept->branch?->name ?? ''),
                (string) ($concept->currency?->name ?? ''),
                number_format($totalConcept, 2, '.', ''),
                number_format($paidGroup, 2, '.', ''),
                number_format($remaining, 2, '.', ''),
                (string) ($concept->user?->email ?? ''),
            ]));
            $rowCount++;
        }

        return $rowCount;
    }

    private function groupKey(InvestmentRequest $concept): ?string
    {
        if (! $concept->project_id || ! $concept->investment_expense_concept_id) {
            return null;
        }

        return "{$concept->project_id}:{$concept->investment_expense_concept_id}";
    }
}
