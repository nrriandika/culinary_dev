<?php

namespace App\Filament\Resources\Regencies;

use App\Filament\Resources\Regencies\Pages\ManageRegencies;
use App\Filament\Support\Fields;
use App\Filament\Support\RegionGuard;
use App\Models\Regency;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class RegencyResource extends Resource
{
    protected static ?string $model = Regency::class;

    protected static ?string $slug = 'kabupaten-kota';

    protected static ?string $modelLabel = 'kabupaten/kota';

    protected static ?string $pluralModelLabel = 'kabupaten/kota';

    protected static ?string $navigationLabel = 'Kabupaten/Kota';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string | UnitEnum | null $navigationGroup = 'Wilayah';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Form dipakai juga oleh RegenciesRelationManager (tanpa pilihan provinsi).
     *
     * @return array<int, mixed>
     */
    public static function formComponents(bool $withProvince = true): array
    {
        return [
            Grid::make(2)->schema(array_values(array_filter([
                $withProvince
                    ? Select::make('province_id')->label('Provinsi')->relationship('province', 'name')->searchable()->preload()->required()
                    : null,
                Select::make('type')->label('Jenis')->options(Regency::TYPES)->default('kabupaten')->required()->native(false),
                TextInput::make('code')
                    ->label('Kode wilayah')
                    ->helperText('4 digit kode Kemendagri, mis. 5171')
                    ->required()
                    ->length(4)
                    ->numeric()
                    ->unique(ignoreRecord: true),
                ...Fields::nameAndSlug('Nama kabupaten/kota'),
                Textarea::make('description')->label('Deskripsi singkat')->rows(3)->columnSpanFull(),
                FileUpload::make('image')->label('Gambar')->image()->disk('public')->directory('wilayah')->maxSize(4096)->columnSpanFull(),
            ]))),
            Fields::coordinates('Titik Tengah Wilayah'),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components(static::formComponents());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('province')->withCount(['culinaries', 'destinations']))
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')->label('Kode')->sortable()->searchable(),
                TextColumn::make('name')->label('Nama')->searchable()->sortable()->weight('medium'),
                TextColumn::make('type')->label('Jenis')->badge()->formatStateUsing(fn (string $state) => Regency::TYPES[$state] ?? $state)->color('gray'),
                TextColumn::make('province.name')->label('Provinsi')->searchable()->sortable(),
                TextColumn::make('culinaries_count')->label('Kuliner')->badge()->color('warning'),
                TextColumn::make('destinations_count')->label('Wisata')->badge()->color('success'),
            ])
            ->filters([
                SelectFilter::make('province_id')->label('Provinsi')->relationship('province', 'name')->searchable()->preload(),
                SelectFilter::make('type')->label('Jenis')->options(Regency::TYPES),
            ])
            ->recordActions([
                EditAction::make()->modalWidth('3xl'),
                RegionGuard::deleteAction(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRegencies::route('/'),
        ];
    }
}
