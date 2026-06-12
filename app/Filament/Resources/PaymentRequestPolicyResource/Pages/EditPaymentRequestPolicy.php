<?php

namespace App\Filament\Resources\PaymentRequestPolicyResource\Pages;

use App\Filament\Resources\PaymentRequestPolicyResource;
use App\Models\PaymentRequestPolicy;
use App\Services\PaymentRequestPolicyService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPaymentRequestPolicy extends EditRecord
{
    protected static string $resource = PaymentRequestPolicyResource::class;

    public function getRecord(): Model
    {
        return PaymentRequestPolicy::current();
    }

    protected function resolveRecord($key): Model
    {
        return PaymentRequestPolicy::current();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Si activamos submit_window_is_active por primera vez, marcar cancellation_activated_at.
        if (! empty($data['submit_window_is_active'])
            && empty($this->record->cancellation_activated_at)) {
            $data['cancellation_activated_at'] = now();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        PaymentRequestPolicyService::flushCache();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
