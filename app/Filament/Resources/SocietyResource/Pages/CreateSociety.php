<?php

namespace App\Filament\Resources\SocietyResource\Pages;

use App\Filament\Resources\SocietyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSociety extends CreateRecord
{
    protected static string $resource = SocietyResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
