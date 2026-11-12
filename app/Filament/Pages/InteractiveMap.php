<?php

namespace App\Filament\Pages;

use App\Filament\Support\MapData;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Renderless;

/**
 * Spesifikasi: Peta Interaktif Indonesia — titik lokasi wisata dan wilayah asal kuliner.
 * Batasan: marker + filter wilayah (tanpa polygon interaktif / navigasi rute).
 */
class InteractiveMap extends Page
{
    protected static ?string $slug = 'peta';

    protected static ?string $title = 'Peta Interaktif';

    protected static ?string $navigationLabel = 'Peta Interaktif';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?int $navigationSort = -1;

    protected string $view = 'filament.pages.interactive-map';

    protected Width|string|null $maxContentWidth = Width::Full;

    /**
     * Dipanggil dari peta (Alpine) via `$wire.loadMapData()`.
     *
     * @return array<string, mixed>
     */
    #[Renderless]
    public function loadMapData(): array
    {
        return MapData::build();
    }
}
