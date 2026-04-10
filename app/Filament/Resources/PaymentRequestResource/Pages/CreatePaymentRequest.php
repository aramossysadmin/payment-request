<?php

namespace App\Filament\Resources\PaymentRequestResource\Pages;

use App\Filament\Resources\PaymentRequestResource;
use App\Models\PaymentRequest;
use App\Services\ApprovalService;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentRequest extends CreateRecord
{
    protected static string $resource = PaymentRequestResource::class;

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
        /** @var PaymentRequest $paymentRequest */
        $paymentRequest = $this->record;

        app(ApprovalService::class)->createApprovals($paymentRequest);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
