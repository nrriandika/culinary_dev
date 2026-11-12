<?php

namespace App\Filament\Resources\Culinaries\Pages;

use App\Filament\Resources\Culinaries\CulinaryResource;
use App\Filament\Resources\Recipes\RecipeResource;
use App\Filament\Support\PublishActions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCulinary extends EditRecord
{
    protected static string $resource = CulinaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addRecipe')
                ->label('Tambah Resep')
                ->icon('heroicon-o-book-open')
                ->color('gray')
                ->url(fn (): string => RecipeResource::getUrl('create', ['culinary' => $this->record->getKey()])),
            PublishActions::toggle()->after(fn () => $this->refreshFormData(['status', 'published_at'])),
            DeleteAction::make(),
        ];
    }
}
