<?php

namespace App\Filament\Resources\ExpenseConceptResource\Pages;

use App\Filament\Resources\ExpenseConceptResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExpenseConcepts extends ListRecords
{
    protected static string $resource = ExpenseConceptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
