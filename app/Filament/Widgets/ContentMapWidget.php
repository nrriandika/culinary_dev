<?php

namespace App\Filament\Widgets;

use App\Filament\Support\MapData;
use Filament\Widgets\Widget;
use Livewire\Attributes\Renderless;

/**
 * Pratinjau peta di dashboard. Versi lengkap ada di halaman Peta Interaktif.
 */
class ContentMapWidget extends Widget
{
    protected static ?int $sort = 2;

    protected static ?bool $isLazyLoaded = false;

    protected string $view = 'filament.widgets.content-map-widget';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, mixed>
     */
    #[Renderless]
    public function loadMapData(): array
    {
        return MapData::build();
    }
}
