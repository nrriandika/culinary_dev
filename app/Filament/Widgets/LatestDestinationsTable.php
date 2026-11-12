<?php

namespace App\Filament\Widgets;

use App\Models\Destination;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestDestinationsTable extends TableWidget
{
    protected static ?int $sort = 4;

    protected static ?bool $isLazyLoaded = false;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Wisata Terbaru')
            ->query(Destination::query()->with('province')->latest('updated_at'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->columns([
                TextColumn::make('name')->label('Nama')->weight('medium'),
                TextColumn::make('province.name')->label('Provinsi'),
                TextColumn::make('status')->badge(),
                TextColumn::make('updated_at')->label('Diperbarui')->since(),
            ])
            ->emptyStateHeading('Belum ada data wisata');
    }
}
