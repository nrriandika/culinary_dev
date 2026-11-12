<?php

namespace App\Filament\Support;

use App\Filament\Resources\Culinaries\CulinaryResource;
use App\Filament\Resources\Destinations\DestinationResource;
use App\Filament\Resources\Provinces\ProvinceResource;
use App\Models\Category;
use App\Models\Culinary;
use App\Models\Destination;
use App\Models\Province;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Menyusun data titik untuk peta interaktif (kuliner, wisata, pusat provinsi).
 * Hanya data yang memiliki koordinat yang dikirim ke peta.
 */
class MapData
{
    /**
     * @return array{
     *     culinaries: list<array<string, mixed>>,
     *     destinations: list<array<string, mixed>>,
     *     provinces: list<array<string, mixed>>,
     *     categories: list<array{id: int, type: string, name: string}>,
     *     provinceOptions: list<array{id: int, name: string}>
     * }
     */
    public static function build(): array
    {
        $culinaries = Culinary::query()
            ->withCoordinates()
            ->with(['category:id,name', 'province:id,name', 'regency:id,name'])
            ->withCount('recipes')
            ->orderBy('name')
            ->get()
            ->map(fn (Culinary $culinary): array => [
                ...self::baseFeature($culinary, CulinaryResource::getUrl('edit', ['record' => $culinary])),
                'recipes_count' => $culinary->recipes_count,
            ]);

        $destinations = Destination::query()
            ->withCoordinates()
            ->with(['category:id,name', 'province:id,name', 'regency:id,name'])
            ->orderBy('name')
            ->get()
            ->map(fn (Destination $destination): array => [
                ...self::baseFeature($destination, DestinationResource::getUrl('edit', ['record' => $destination])),
                'address' => $destination->address,
                'opening_hours' => $destination->opening_hours,
                'ticket_price' => $destination->ticket_price,
            ]);

        $provinces = Province::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->withCount(['regencies', 'culinaries', 'destinations'])
            ->orderBy('name')
            ->get()
            ->map(fn (Province $province): array => [
                'id' => $province->id,
                'name' => $province->name,
                'code' => $province->code,
                'province_id' => $province->id,
                'province' => $province->name,
                'summary' => Str::limit(strip_tags((string) $province->description), 180),
                'image' => self::imageUrl($province->image),
                'lat' => $province->latitude,
                'lng' => $province->longitude,
                'regencies_count' => $province->regencies_count,
                'culinaries_count' => $province->culinaries_count,
                'destinations_count' => $province->destinations_count,
                'edit_url' => ProvinceResource::getUrl('edit', ['record' => $province]),
            ]);

        return [
            'culinaries' => $culinaries->values()->all(),
            'destinations' => $destinations->values()->all(),
            'provinces' => $provinces->values()->all(),
            'categories' => Category::query()
                ->orderBy('name')
                ->get(['id', 'type', 'name'])
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'type' => $category->type->value,
                    'name' => $category->name,
                ])
                ->all(),
            'provinceOptions' => Province::query()->orderBy('name')->get(['id', 'name'])->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function baseFeature(Culinary|Destination $model, string $editUrl): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'category_id' => $model->category_id,
            'category' => $model->category?->name,
            'province_id' => $model->province_id,
            'province' => $model->province?->name,
            'regency' => $model->regency?->name,
            'status' => $model->status->value,
            'status_label' => $model->status->getLabel(),
            'summary' => Str::limit((string) ($model->summary ?: strip_tags((string) $model->description)), 180),
            'image' => self::imageUrl($model->image),
            'lat' => $model->latitude,
            'lng' => $model->longitude,
            'edit_url' => $editUrl,
        ];
    }

    private static function imageUrl(?string $path): ?string
    {
        return filled($path) ? Storage::disk('public')->url($path) : null;
    }
}
