<?php

namespace App\Filament\Resources\Media\Pages;

use App\Filament\Resources\Media\MediaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMedia extends ManageRecords
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Upload Foto')
                ->icon('heroicon-o-arrow-up-tray')
                ->mutateDataUsing(function (array $data): array {
                    $data['disk'] = 'public';
                    $data['uploaded_by'] = auth()->id();

                    return $data;
                }),
        ];
    }
}
