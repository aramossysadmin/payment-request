<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Sincroniza la pivot `department_user`:
     * - El principal (`department_id`) entra siempre.
     * - Los adicionales (form field `additional_departments`, dehydrated=false) se agregan al sync.
     */
    protected function afterCreate(): void
    {
        $this->syncDepartments();
    }

    private function syncDepartments(): void
    {
        /** @var User $user */
        $user = $this->record;

        $additionalIds = collect($this->form->getRawState()['additional_departments'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->all();

        $principalId = $user->department_id ? [(int) $user->department_id] : [];

        $allIds = array_values(array_unique(array_merge($principalId, $additionalIds)));

        $user->departments()->sync($allIds);
    }
}
