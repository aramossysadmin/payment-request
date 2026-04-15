<?php

namespace App\Filament\Resources\InvestmentExpenseConceptResource\Pages;

use App\Filament\Resources\InvestmentExpenseConceptResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvestmentExpenseConcepts extends ListRecords
{
    protected static string $resource = InvestmentExpenseConceptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
