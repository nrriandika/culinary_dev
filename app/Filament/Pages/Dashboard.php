<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ContentMapWidget;
use App\Filament\Widgets\ContentStatsOverview;
use App\Filament\Widgets\LatestCulinariesTable;
use App\Filament\Widgets\LatestDestinationsTable;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    public function getTitle(): string | Htmlable
    {
        return 'Dashboard';
    }

    public function getWidgets(): array
    {
        return [
            AccountWidget::class,
            ContentStatsOverview::class,
            ContentMapWidget::class,
            LatestCulinariesTable::class,
            LatestDestinationsTable::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 2;
    }
}
