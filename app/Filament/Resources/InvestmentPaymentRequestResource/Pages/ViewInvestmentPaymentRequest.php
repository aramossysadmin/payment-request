<?php

namespace App\Filament\Resources\InvestmentPaymentRequestResource\Pages;

use App\Filament\Resources\InvestmentPaymentRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInvestmentPaymentRequest extends ViewRecord
{
    protected static string $resource = InvestmentPaymentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
