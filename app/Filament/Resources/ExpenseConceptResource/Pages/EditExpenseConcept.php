<?php

namespace App\Filament\Resources\ExpenseConceptResource\Pages;

use App\Filament\Resources\ExpenseConceptResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExpenseConcept extends EditRecord
{
    protected static string $resource = ExpenseConceptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->getResource()::getUrl('index');
    }
}
