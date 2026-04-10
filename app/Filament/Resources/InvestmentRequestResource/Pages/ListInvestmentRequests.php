<?php

namespace App\Filament\Resources\InvestmentRequestResource\Pages;

use App\Filament\Resources\InvestmentRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvestmentRequests extends ListRecords
{
    protected static string $resource = InvestmentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
