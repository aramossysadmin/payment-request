<?php

namespace Database\Seeders\BulkImport;

use App\Models\InvestmentExpenseCategory;
use App\Models\InvestmentExpenseConcept;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class InvestmentExpenseConceptsSeeder extends Seeder
{
    private const CSV_FILENAME = 'investment_expense_concepts.csv';

    private const REQUIRED_HEADERS = ['nombre', 'categoria'];

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

        $categoriesIndex = $this->buildCategoriesIndex();

        $imported = 0;
        $skipped = [];
        $errors = [];

        DB::transaction(function () use ($reader, $categoriesIndex, &$imported, &$skipped, &$errors): void {
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

                $categoryName = mb_strtoupper(trim($row['categoria'] ?? ''));
                if ($categoryName === '') {
                    $errors[] = "Fila {$rowIndex}: \"{$name}\" no tiene categoría asignada.";

                    continue;
                }

                $superCategory = trim($row['super_categoria'] ?? '') !== ''
                    ? mb_strtoupper(trim($row['super_categoria']))
                    : null;

                $resolution = $this->resolveCategoryId($categoriesIndex, $categoryName, $superCategory);
                if ($resolution['error'] !== null) {
                    $errors[] = "Fila {$rowIndex}: \"{$name}\" {$resolution['error']}";

                    continue;
                }

                $categoryId = $resolution['id'];

                if (InvestmentExpenseConcept::withTrashed()
                    ->where('name', $name)
                    ->where('investment_expense_category_id', $categoryId)
                    ->exists()
                ) {
                    $skipped[] = "{$name} (ya existe en categoría {$categoryName})";

                    continue;
                }

                InvestmentExpenseConcept::create([
                    'name' => $name,
                    'investment_expense_category_id' => $categoryId,
                    'is_active' => true,
                ]);
                $imported++;
            }
        });

        $this->report('Gastos de Inversión', $imported, $skipped, $errors);
    }

    /**
     * Build an index of categories grouped by name to support disambiguation by super_category.
     *
     * @return array<string, array<int, array{id: int, super_category: ?string}>>
     */
    private function buildCategoriesIndex(): array
    {
        $index = [];
        InvestmentExpenseCategory::query()
            ->select(['id', 'name', 'super_category'])
            ->get()
            ->each(function (InvestmentExpenseCategory $category) use (&$index): void {
                $key = mb_strtoupper($category->name);
                $index[$key][] = [
                    'id' => (int) $category->id,
                    'super_category' => $category->super_category !== null
                        ? mb_strtoupper($category->super_category)
                        : null,
                ];
            });

        return $index;
    }

    /**
     * @param  array<string, array<int, array{id: int, super_category: ?string}>>  $index
     * @return array{id: ?int, error: ?string}
     */
    private function resolveCategoryId(array $index, string $categoryName, ?string $superCategory): array
    {
        if (! isset($index[$categoryName])) {
            return ['id' => null, 'error' => "referencia categoría inexistente: \"{$categoryName}\"."];
        }

        $matches = $index[$categoryName];

        if (count($matches) === 1) {
            return ['id' => $matches[0]['id'], 'error' => null];
        }

        if ($superCategory === null) {
            $available = array_map(
                fn (array $match): string => $match['super_category'] ?? '(sin super)',
                $matches
            );

            return [
                'id' => null,
                'error' => "categoría \"{$categoryName}\" es ambigua (existe en: ".implode(', ', $available).'). Especifica super_categoria.',
            ];
        }

        foreach ($matches as $match) {
            if ($match['super_category'] === $superCategory) {
                return ['id' => $match['id'], 'error' => null];
            }
        }

        return [
            'id' => null,
            'error' => "no se encontró categoría \"{$categoryName}\" con super_categoria \"{$superCategory}\".",
        ];
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
