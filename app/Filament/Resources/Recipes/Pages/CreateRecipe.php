<?php

namespace App\Filament\Resources\Recipes\Pages;

use App\Filament\Resources\Recipes\RecipeResource;
use App\Models\Culinary;
use Filament\Resources\Pages\CreateRecord;

class CreateRecipe extends CreateRecord
{
    protected static string $resource = RecipeResource::class;

    /** Mendukung ?culinary={id} dari tombol "Tambah Resep" di halaman kuliner. */
    protected function afterFill(): void
    {
        $culinaryId = request()->integer('culinary');

        if ($culinaryId && ($culinary = Culinary::query()->find($culinaryId))) {
            $this->data['culinary_id'] = $culinary->getKey();
            $this->data['title'] = 'Resep ' . $culinary->name;
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
