<?php

namespace App\Filament\Resources\InvestmentExpenseCategoryResource\Pages;

use App\Filament\Resources\InvestmentExpenseCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInvestmentExpenseCategory extends EditRecord
{
    protected static string $resource = InvestmentExpenseCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
