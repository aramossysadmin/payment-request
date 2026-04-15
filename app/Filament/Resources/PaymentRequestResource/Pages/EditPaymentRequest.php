<?php

namespace App\Filament\Resources\PaymentRequestResource\Pages;

use App\Filament\Resources\PaymentRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPaymentRequest extends EditRecord
{
    protected static string $resource = PaymentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (isset($data['status'])) {
            $record->status = $data['status'];
            unset($data['status']);
        }

        $record->fill($data);
        $record->save();

        return $record;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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

    protected function getRedirectUrl(): ?string
    {
        return $this->getResource()::getUrl('index');
    }
}
