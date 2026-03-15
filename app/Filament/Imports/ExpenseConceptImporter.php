<?php

namespace App\Filament\Imports;

use App\Models\ExpenseConcept;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class ExpenseConceptImporter extends Importer
{
    protected static ?string $model = ExpenseConcept::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Nombre')
                ->exampleHeader('Nombre')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->examples(['PAPELERIA', 'TRANSPORTE']),
        ];
    }

    public function resolveRecord(): ?ExpenseConcept
    {
        $name = mb_strtoupper(trim($this->data['name']));

        $existing = ExpenseConcept::withTrashed()->where('name', $name)->first();

        if ($existing) {
            if ($existing->trashed()) {
                throw new \Exception("El concepto \"{$name}\" fue eliminado previamente. Restáurelo desde el panel antes de importarlo.");
            }

            if (! $existing->is_active) {
                throw new \Exception("El concepto \"{$name}\" ya existe pero está inactivo. Actívelo desde el panel si desea utilizarlo.");
            }

            throw new \Exception("El concepto \"{$name}\" ya existe.");
        }

        return new ExpenseConcept;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'La importación de conceptos de gasto ha finalizado. Se importaron '.number_format($import->successful_rows).' '.str('registro')->plural($import->successful_rows).' exitosamente.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('registro')->plural($failedRowsCount).' fallaron al importarse.';
        }

        return $body;
    }
}
