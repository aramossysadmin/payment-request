<?php

namespace App\Filament\Resources\InvestmentPaymentBatchResource\Pages;

use App\Filament\Resources\InvestmentPaymentBatchResource;
use Filament\Resources\Pages\ListRecords;

class ListInvestmentPaymentBatches extends ListRecords
{
    protected static string $resource = InvestmentPaymentBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
