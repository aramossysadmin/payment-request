<?php

namespace App\Filament\Resources\InvestmentExpenseCategoryResource\Pages;

use App\Filament\Resources\InvestmentExpenseCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvestmentExpenseCategories extends ListRecords
{
    protected static string $resource = InvestmentExpenseCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
