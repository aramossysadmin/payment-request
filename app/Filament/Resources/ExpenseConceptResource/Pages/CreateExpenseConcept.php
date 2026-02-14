<?php

namespace App\Filament\Resources\ExpenseConceptResource\Pages;

use App\Filament\Resources\ExpenseConceptResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExpenseConcept extends CreateRecord
{
    protected static string $resource = ExpenseConceptResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
