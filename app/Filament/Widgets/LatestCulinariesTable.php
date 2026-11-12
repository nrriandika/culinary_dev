<?php

namespace App\Filament\Widgets;

use App\Models\Culinary;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestCulinariesTable extends TableWidget
{
    protected static ?int $sort = 3;

    protected static ?bool $isLazyLoaded = false;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Kuliner Terbaru')
            ->query(Culinary::query()->with('province')->latest('updated_at'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->columns([
                TextColumn::make('name')->label('Nama')->weight('medium'),
                TextColumn::make('province.name')->label('Provinsi'),
                TextColumn::make('status')->badge(),
                TextColumn::make('updated_at')->label('Diperbarui')->since(),
            ])
            ->emptyStateHeading('Belum ada data kuliner');
    }
}
