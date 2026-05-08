<?php

namespace Database\Seeders\BulkImport;

use App\Models\Department;
use App\Models\InvestmentExpenseCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class InvestmentExpenseCategoriesSeeder extends Seeder
{
    private const CSV_FILENAME = 'investment_expense_categories.csv';

    private const REQUIRED_HEADERS = ['nombre', 'departamentos'];

    private const DEPARTMENT_SEPARATOR = '|';

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

        $departmentsByName = Department::query()
            ->pluck('id', 'name')
            ->mapWithKeys(fn (int $id, string $name): array => [mb_strtoupper($name) => $id])
            ->all();

        $imported = 0;
        $skipped = [];
        $errors = [];

        DB::transaction(function () use ($reader, $departmentsByName, &$imported, &$skipped, &$errors): void {
            foreach ($reader->getRecords() as $rowIndex => $record) {
                $row = $this->normalizeKeys($record);

                $name = mb_strtoupper(trim($row['nombre'] ?? ''));
                if ($name === '' || str_contains($name, '(EJEMPLO')) {
                    continue;
                }

                if (mb_strlen($name) > 255) {
                    $errors[] = "Fila {$rowIndex}: \"{$name}\" excede 255 caracteres.";

                    continue;
                }

                $superCategory = trim($row['super_categoria'] ?? '') !== ''
                    ? mb_strtoupper(trim($row['super_categoria']))
                    : null;

                if (InvestmentExpenseCategory::withTrashed()
                    ->where('name', $name)
                    ->where('super_category', $superCategory)
                    ->exists()
                ) {
                    $skipped[] = "{$name} / ".($superCategory ?? '(sin super categoría)').' (ya existe)';

                    continue;
                }

                $departmentNames = array_filter(array_map(
                    fn (string $value): string => mb_strtoupper(trim($value)),
                    explode(self::DEPARTMENT_SEPARATOR, $row['departamentos'] ?? '')
                ));

                if (empty($departmentNames)) {
                    $errors[] = "Fila {$rowIndex}: \"{$name}\" no tiene departamentos asignados.";

                    continue;
                }

                $departmentIds = [];
                $missingDepartments = [];
                foreach ($departmentNames as $departmentName) {
                    if (! isset($departmentsByName[$departmentName])) {
                        $missingDepartments[] = $departmentName;

                        continue;
                    }
                    $departmentIds[] = $departmentsByName[$departmentName];
                }

                if (! empty($missingDepartments)) {
                    $errors[] = "Fila {$rowIndex}: \"{$name}\" referencia departamentos inexistentes: ".implode(', ', $missingDepartments);

                    continue;
                }

                $category = InvestmentExpenseCategory::create([
                    'name' => $name,
                    'super_category' => $superCategory,
                    'is_active' => true,
                ]);

                $category->departments()->sync(array_unique($departmentIds));
                $imported++;
            }
        });

        $this->report('Categorías de Gastos de Inversión', $imported, $skipped, $errors);
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
