<?php

namespace App\Filament\Resources\InvestmentPaymentBatchResource\Pages;

use App\Filament\Resources\InvestmentPaymentBatchResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInvestmentPaymentBatch extends ViewRecord
{
    protected static string $resource = InvestmentPaymentBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
