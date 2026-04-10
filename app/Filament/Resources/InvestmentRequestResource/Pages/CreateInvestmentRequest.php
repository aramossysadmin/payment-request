<?php

namespace App\Filament\Resources\InvestmentRequestResource\Pages;

use App\Filament\Resources\InvestmentRequestResource;
use App\Models\InvestmentRequest;
use App\Services\InvestmentApprovalService;
use Filament\Resources\Pages\CreateRecord;

class CreateInvestmentRequest extends CreateRecord
{
    protected static string $resource = InvestmentRequestResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['department_id'] = auth()->user()->department_id;

        if (isset($data['advance_documents']) && is_array($data['advance_documents'])) {
            $data['advance_documents'] = array_values(array_filter(
                $data['advance_documents'],
                fn ($doc): bool => is_string($doc) && $doc !== '',
            ));

            if (empty($data['advance_documents'])) {
                $data['advance_documents'] = null;
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var InvestmentRequest $investmentRequest */
        $investmentRequest = $this->record;

        app(InvestmentApprovalService::class)->createApprovals($investmentRequest);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
