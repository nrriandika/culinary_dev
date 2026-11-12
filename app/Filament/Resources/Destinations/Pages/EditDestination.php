<?php

namespace App\Filament\Resources\Destinations\Pages;

use App\Filament\Resources\Destinations\DestinationResource;
use App\Filament\Support\PublishActions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDestination extends EditRecord
{
    protected static string $resource = DestinationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PublishActions::toggle()->after(fn () => $this->refreshFormData(['status', 'published_at'])),
            DeleteAction::make(),
        ];
    }
}
