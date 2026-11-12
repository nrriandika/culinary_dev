<?php

namespace App\Filament\Widgets;

use App\Enums\PublishStatus;
use App\Models\Culinary;
use App\Models\Destination;
use App\Models\Province;
use App\Models\Regency;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Spesifikasi CMS > Dashboard: "Ringkasan jumlah kuliner, wisata, dan wilayah".
 */
class ContentStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    // Query ringan: render langsung bersama halaman, tanpa request Livewire tambahan.
    protected static ?bool $isLazyLoaded = false;

    protected ?string $heading = 'Ringkasan Konten';

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $culinaryPublished = Culinary::query()->where('status', PublishStatus::Published)->count();
        $culinaryTotal = Culinary::query()->count();

        $destinationPublished = Destination::query()->where('status', PublishStatus::Published)->count();
        $destinationTotal = Destination::query()->count();

        $provinces = Province::query()->count();
        $regencies = Regency::query()->count();

        return [
            Stat::make('Kuliner', number_format($culinaryTotal, 0, ',', '.'))
                ->description(sprintf('%d published · %d draft', $culinaryPublished, $culinaryTotal - $culinaryPublished))
                ->descriptionIcon(Heroicon::OutlinedCake)
                ->color('warning'),

            Stat::make('Destinasi Wisata', number_format($destinationTotal, 0, ',', '.'))
                ->description(sprintf('%d published · %d draft', $destinationPublished, $destinationTotal - $destinationPublished))
                ->descriptionIcon(Heroicon::OutlinedMapPin)
                ->color('primary'),

            Stat::make('Wilayah', number_format($provinces, 0, ',', '.') . ' provinsi')
                ->description(number_format($regencies, 0, ',', '.') . ' kabupaten/kota')
                ->descriptionIcon(Heroicon::OutlinedGlobeAsiaAustralia)
                ->color('info'),
        ];
    }
}
