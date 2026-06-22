<?php

namespace App\Filament\Resources\InvestmentRequestResource\Pages;

use App\Filament\Resources\InvestmentRequestResource;
use App\Models\InvestmentRequest;
use App\Services\InvestmentApprovalService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateInvestmentRequest extends CreateRecord
{
    protected static string $resource = InvestmentRequestResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
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

    protected function handleRecordCreation(array $data): Model
    {
        $user = auth()->user();

        $record = new InvestmentRequest($data);
        $record->user_id = $user->id;

        // department_id viene del form si existe (multi-dpto), si no se autoasigna al principal.
        $record->department_id = ($data['department_id'] ?? null) ?: $user->department_id;
        $record->save();

        return $record;
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
