<?php

namespace Database\Seeders\BulkImport;

use App\Enums\IvaRate;
use App\Models\Branch;
use App\Models\Currency;
use App\Models\ExpenseConcept;
use App\Models\InvestmentExpenseConcept;
use App\Models\InvestmentRequest;
use App\Models\PaymentType;
use App\Models\Project;
use App\Models\User;
use App\States\InvestmentRequest\Completed;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class InvestmentRequestsHistoricalSeeder extends Seeder
{
    private const CSV_FILENAME = 'investment_requests_historical.csv';

    private const REQUIRED_HEADERS = [
        'currency',
        'branch',
        'subtotal',
        'iva_rate',
        'iva',
        'total',
        'created_by_email',
    ];

    public function run(): void
    {
        $absolutePath = database_path('seeders/data/'.self::CSV_FILENAME);

        if (! is_file($absolutePath)) {
            $this->command->error("CSV no encontrado en {$absolutePath}");

            return;
        }

        $reader = Reader::createFromPath($absolutePath, 'r');
        $reader->setHeaderOffset(0);
        $headers = array_map(
            fn (string $header): string => mb_strtolower(trim($header)),
            $reader->getHeader()
        );

        foreach (self::REQUIRED_HEADERS as $required) {
            if (! in_array($required, $headers, true)) {
                $this->command->error("El CSV debe contener la columna \"{$required}\".");

                return;
            }
        }

        $catalogs = [
            'currencies' => $this->indexByName(Currency::query()->pluck('id', 'name')),
            'branches' => $this->indexByName(Branch::query()->pluck('id', 'name')),
            'expense_concepts' => $this->indexByName(ExpenseConcept::query()->pluck('id', 'name')),
            'payment_types' => $this->indexByName(PaymentType::query()->pluck('id', 'name')),
            'projects' => $this->indexByName(Project::query()->pluck('id', 'name')),
            'investment_expense_concepts' => $this->buildInvestmentExpenseConceptsIndex(),
            'users' => User::query()->pluck('id', 'email')
                ->mapWithKeys(fn (int $id, string $email): array => [mb_strtolower($email) => $id])
                ->all(),
        ];

        $userDepartments = User::query()->pluck('department_id', 'id')->all();

        $imported = 0;
        $skipped = [];
        $errors = [];

        DB::transaction(function () use ($reader, $catalogs, $userDepartments, &$imported, &$skipped, &$errors): void {
            foreach ($reader->getRecords() as $rowIndex => $record) {
                $row = $this->normalizeKeys($record);

                $invoiceFolio = mb_strtoupper(trim($row['invoice_folio'] ?? ''));

                $folioFromCsv = isset($row['folio_number']) && $row['folio_number'] !== ''
                    ? (int) $row['folio_number']
                    : null;

                if ($folioFromCsv !== null && InvestmentRequest::withTrashed()->where('folio_number', $folioFromCsv)->exists()) {
                    $skipped[] = "Fila {$rowIndex}: folio {$folioFromCsv} ya existe.";

                    continue;
                }

                $resolved = $this->resolveForeignKeys($row, $catalogs, $rowIndex);
                if (! empty($resolved['errors'])) {
                    $errors = array_merge($errors, $resolved['errors']);

                    continue;
                }

                $userId = $resolved['ids']['user_id'];
                $departmentId = $userDepartments[$userId] ?? null;
                if ($departmentId === null) {
                    $errors[] = "Fila {$rowIndex}: el usuario \"{$row['created_by_email']}\" no tiene departamento asignado.";

                    continue;
                }

                $ivaRate = IvaRate::tryFrom(trim((string) ($row['iva_rate'] ?? '')));
                if ($ivaRate === null) {
                    $errors[] = "Fila {$rowIndex}: iva_rate \"{$row['iva_rate']}\" no es válido. Usa: ".implode(', ', array_column(IvaRate::cases(), 'value'));

                    continue;
                }

                $investmentRequest = new InvestmentRequest([
                    'provider' => $row['provider'] ?? null,
                    'rfc' => $row['rfc'] ?? null,
                    'contact_name' => $row['contact_name'] ?? null,
                    'contact_email' => $row['contact_email'] ?? null,
                    'contact_phone' => $row['contact_phone'] ?? null,
                    'invoice_folio' => $invoiceFolio !== '' ? $invoiceFolio : null,
                    'description' => $row['description'] ?? null,
                    'subtotal' => $row['subtotal'],
                    'iva_rate' => $ivaRate,
                    'iva' => $row['iva'],
                    'retention' => isset($row['retention']) ? (bool) (int) $row['retention'] : false,
                    'total' => $row['total'],
                ]);

                $investmentRequest->currency_id = $resolved['ids']['currency_id'];
                $investmentRequest->branch_id = $resolved['ids']['branch_id'];
                $investmentRequest->expense_concept_id = $resolved['ids']['expense_concept_id'] ?? null;
                $investmentRequest->payment_type_id = $resolved['ids']['payment_type_id'] ?? null;
                $investmentRequest->project_id = $resolved['ids']['project_id'] ?? null;
                $investmentRequest->investment_expense_concept_id = $resolved['ids']['investment_expense_concept_id'] ?? null;
                $investmentRequest->user_id = $userId;
                $investmentRequest->department_id = $departmentId;
                $investmentRequest->status = Completed::class;

                if ($folioFromCsv !== null) {
                    $investmentRequest->folio_number = $folioFromCsv;
                }

                $createdAt = $this->parseDate($row['created_at'] ?? null);
                if ($createdAt !== null) {
                    $investmentRequest->timestamps = false;
                    $investmentRequest->created_at = $createdAt;
                    $investmentRequest->updated_at = $createdAt;
                }

                $investmentRequest->save();
                $imported++;
            }
        });

        $this->report('Conceptos de Inversión (Histórico)', $imported, $skipped, $errors);
    }

    /**
     * @param  array<string, string>  $row
     * @param  array<string, mixed>  $catalogs
     * @return array{ids: array<string, ?int>, errors: list<string>}
     */
    private function resolveForeignKeys(array $row, array $catalogs, int $rowIndex): array
    {
        $errors = [];
        $ids = [];

        $ids['currency_id'] = $this->lookup($catalogs['currencies'], $row['currency'] ?? '');
        if ($ids['currency_id'] === null) {
            $errors[] = "Fila {$rowIndex}: moneda \"{$row['currency']}\" no encontrada.";
        }

        $ids['branch_id'] = $this->lookup($catalogs['branches'], $row['branch'] ?? '');
        if ($ids['branch_id'] === null) {
            $errors[] = "Fila {$rowIndex}: sucursal \"{$row['branch']}\" no encontrada.";
        }

        if (! empty($row['expense_concept'] ?? '')) {
            $ids['expense_concept_id'] = $this->lookup($catalogs['expense_concepts'], $row['expense_concept']);
            if ($ids['expense_concept_id'] === null) {
                $errors[] = "Fila {$rowIndex}: concepto de gasto \"{$row['expense_concept']}\" no encontrado.";
            }
        } else {
            $ids['expense_concept_id'] = null;
        }

        if (! empty($row['payment_type'] ?? '')) {
            $ids['payment_type_id'] = $this->lookup($catalogs['payment_types'], $row['payment_type']);
            if ($ids['payment_type_id'] === null) {
                $errors[] = "Fila {$rowIndex}: tipo de pago \"{$row['payment_type']}\" no encontrado.";
            }
        } else {
            $ids['payment_type_id'] = null;
        }

        if (! empty($row['project'] ?? '')) {
            $ids['project_id'] = $this->lookup($catalogs['projects'], $row['project']);
            if ($ids['project_id'] === null) {
                $errors[] = "Fila {$rowIndex}: proyecto \"{$row['project']}\" no encontrado.";
            }
        } else {
            $ids['project_id'] = null;
        }

        $conceptName = trim($row['investment_expense_concept'] ?? '');
        if ($conceptName !== '') {
            $conceptCategory = trim($row['concept_category'] ?? '');
            $resolution = $this->resolveInvestmentExpenseConceptId(
                $catalogs['investment_expense_concepts'],
                $conceptName,
                $conceptCategory !== '' ? $conceptCategory : null
            );
            if ($resolution['error'] !== null) {
                $errors[] = "Fila {$rowIndex}: gasto de inversión {$resolution['error']}";
            }
            $ids['investment_expense_concept_id'] = $resolution['id'];
        } else {
            $ids['investment_expense_concept_id'] = null;
        }

        $email = mb_strtolower(trim($row['created_by_email'] ?? ''));
        $ids['user_id'] = $catalogs['users'][$email] ?? null;
        if ($ids['user_id'] === null) {
            $errors[] = "Fila {$rowIndex}: usuario con email \"{$email}\" no encontrado.";
        }

        return ['ids' => $ids, 'errors' => $errors];
    }

    /**
     * Build an accent-insensitive index of investment expense concepts grouped by normalized name.
     *
     * @return array<string, array<int, array{id: int, name: string, category: string}>>
     */
    private function buildInvestmentExpenseConceptsIndex(): array
    {
        $index = [];
        InvestmentExpenseConcept::query()
            ->with('category:id,name')
            ->get()
            ->each(function (InvestmentExpenseConcept $concept) use (&$index): void {
                $key = $this->normalizeForLookup($concept->name);
                $index[$key][] = [
                    'id' => (int) $concept->id,
                    'name' => $concept->name,
                    'category' => $concept->category?->name ?? '',
                ];
            });

        return $index;
    }

    /**
     * @param  array<string, array<int, array{id: int, name: string, category: string}>>  $index
     * @return array{id: ?int, error: ?string}
     */
    private function resolveInvestmentExpenseConceptId(array $index, string $name, ?string $category): array
    {
        $key = $this->normalizeForLookup($name);

        if (! isset($index[$key])) {
            return ['id' => null, 'error' => "\"{$name}\" no encontrado."];
        }

        $matches = $index[$key];

        if (count($matches) === 1) {
            return ['id' => $matches[0]['id'], 'error' => null];
        }

        if ($category === null) {
            $available = array_map(fn (array $m): string => $m['category'], $matches);

            return [
                'id' => null,
                'error' => "\"{$name}\" es ambiguo (existe en: ".implode(', ', $available).'). Especifica la columna concept_category.',
            ];
        }

        $normalizedCategory = $this->normalizeForLookup($category);
        foreach ($matches as $m) {
            if ($this->normalizeForLookup($m['category']) === $normalizedCategory) {
                return ['id' => $m['id'], 'error' => null];
            }
        }

        return [
            'id' => null,
            'error' => "no se encontró \"{$name}\" con concept_category \"{$category}\".",
        ];
    }

    private function normalizeForLookup(string $value): string
    {
        $value = mb_strtoupper(trim($value));
        $value = strtr($value, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'Ü' => 'U', 'Ñ' => 'N',
        ]);
        $value = preg_replace('/\s+/u', ' ', $value);

        return trim($value);
    }

    /**
     * @param  array<string, int>  $index
     */
    private function lookup(array $index, string $name): ?int
    {
        $key = mb_strtoupper(trim($name));

        return $index[$key] ?? null;
    }

    /**
     * @param  Collection<string, int>  $collection
     * @return array<string, int>
     */
    private function indexByName($collection): array
    {
        return $collection
            ->mapWithKeys(fn (int $id, string $name): array => [mb_strtoupper($name) => $id])
            ->all();
    }

    private function parseDate(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse(trim($value));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, string>  $record
     * @return array<string, string>
     */
    private function normalizeKeys(array $record): array
    {
        $normalized = [];
        foreach ($record as $key => $value) {
            $normalized[mb_strtolower(trim((string) $key))] = is_string($value) ? trim($value) : $value;
        }

        return $normalized;
    }

    /**
     * @param  list<string>  $skipped
     * @param  list<string>  $errors
     */
    private function report(string $resource, int $imported, array $skipped, array $errors): void
    {
        $this->command->info("[{$resource}] Importados: {$imported}");

        if (! empty($skipped)) {
            $this->command->warn('Omitidos:');
            foreach ($skipped as $message) {
                $this->command->warn("  • {$message}");
            }
        }

        if (! empty($errors)) {
            $this->command->error('Errores:');
            foreach ($errors as $message) {
                $this->command->error("  • {$message}");
            }
        }
    }
}
