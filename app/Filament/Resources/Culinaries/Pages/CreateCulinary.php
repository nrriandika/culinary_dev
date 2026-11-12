<?php

namespace App\Filament\Resources\Culinaries\Pages;

use App\Filament\Resources\Culinaries\CulinaryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCulinary extends CreateRecord
{
    protected static string $resource = CulinaryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
