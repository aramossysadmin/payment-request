<?php

namespace App\Filament\Resources\InvestmentPaymentRequestResource\Pages;

use App\Filament\Resources\InvestmentPaymentRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListInvestmentPaymentRequests extends ListRecords
{
    protected static string $resource = InvestmentPaymentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
