<?php

namespace App\Filament\Resources\StrategyResource\Pages;

use App\Filament\Resources\StrategyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStrategy extends EditRecord
{
    protected static string $resource = StrategyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    /**
     * Solo `is_active` es editable: cualquier otro campo se descarta aunque llegue en el payload.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return ['is_active' => (bool) ($data['is_active'] ?? false)];
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->getResource()::getUrl('index');
    }
}
