<?php

namespace App\Filament\Resources\Recipes\Pages;

use App\Filament\Resources\Recipes\RecipeResource;
use App\Filament\Support\PublishActions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRecipe extends EditRecord
{
    protected static string $resource = RecipeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PublishActions::toggle()->after(fn () => $this->refreshFormData(['status', 'published_at'])),
            DeleteAction::make(),
        ];
    }
}
