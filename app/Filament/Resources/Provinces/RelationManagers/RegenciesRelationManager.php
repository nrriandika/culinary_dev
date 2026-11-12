<?php

namespace App\Filament\Resources\Provinces\RelationManagers;

use App\Filament\Resources\Regencies\RegencyResource;
use App\Filament\Support\RegionGuard;
use App\Models\Regency;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RegenciesRelationManager extends RelationManager
{
    protected static string $relationship = 'regencies';

    protected static ?string $title = 'Kabupaten/Kota';

    protected static ?string $modelLabel = 'kabupaten/kota';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components(RegencyResource::formComponents(withProvince: false));
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount(['culinaries', 'destinations']))
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')->label('Kode')->sortable()->searchable(),
                TextColumn::make('name')->label('Nama')->searchable()->sortable()->weight('medium'),
                TextColumn::make('type')->label('Jenis')->badge()->formatStateUsing(fn (string $state) => Regency::TYPES[$state] ?? $state)->color('gray'),
                TextColumn::make('culinaries_count')->label('Kuliner')->badge()->color('warning'),
                TextColumn::make('destinations_count')->label('Wisata')->badge()->color('success'),
            ])
            ->filters([
                SelectFilter::make('type')->label('Jenis')->options(Regency::TYPES),
            ])
            ->headerActions([
                CreateAction::make()->modalWidth('3xl'),
            ])
            ->recordActions([
                EditAction::make()->modalWidth('3xl'),
                RegionGuard::deleteAction(),
            ]);
    }
}
