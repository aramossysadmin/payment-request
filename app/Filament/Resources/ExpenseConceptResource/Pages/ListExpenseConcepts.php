<?php

namespace App\Filament\Resources\ExpenseConceptResource\Pages;

use App\Filament\Imports\ExpenseConceptImporter;
use App\Filament\Resources\ExpenseConceptResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExpenseConcepts extends ListRecords
{
    protected static string $resource = ExpenseConceptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ImportAction::make()
                ->importer(ExpenseConceptImporter::class)
                ->label('Importar CSV')
                ->color('success'),
            Actions\CreateAction::make(),
        ];
    }
}
