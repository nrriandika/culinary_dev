<?php

namespace App\Filament\Support;

use App\Enums\CategoryType;
use App\Enums\PublishStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Komponen form yang dipakai ulang di beberapa resource.
 */
class Fields
{
    /**
     * Nama + slug. Slug otomatis dari nama saat membuat data baru.
     *
     * @return array<int, TextInput>
     */
    public static function nameAndSlug(string $label = 'Nama', string $nameField = 'name', bool $uniqueSlug = true): array
    {
        return [
            TextInput::make($nameField)
                ->label($label)
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function (Set $set, Get $get, ?string $state, string $operation): void {
                    if ($operation === 'create' || blank($get('slug'))) {
                        $set('slug', Str::slug((string) $state));
                    }
                }),

            TextInput::make('slug')
                ->label('Slug (URL)')
                ->helperText('Dipakai pada alamat halaman, mis. /kuliner/rendang')
                ->required()
                ->maxLength(255)
                ->alphaDash()
                ->when($uniqueSlug, fn (TextInput $input) => $input->unique(ignoreRecord: true)),
        ];
    }

    public static function category(CategoryType $type): Select
    {
        return Select::make('category_id')
            ->label('Kategori')
            ->relationship('category', 'name', fn (Builder $query) => $query->where('type', $type))
            ->searchable()
            ->preload()
            ->createOptionForm([
                TextInput::make('name')->label('Nama kategori')->required(),
            ])
            ->createOptionUsing(fn (array $data): int => \App\Models\Category::query()->create([
                'type' => $type,
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
            ])->getKey());
    }

    /**
     * Provinsi -> Kabupaten/Kota (bertingkat).
     *
     * @return array<int, Select>
     */
    public static function region(bool $regencyRequired = false): array
    {
        return [
            Select::make('province_id')
                ->label('Provinsi')
                ->relationship('province', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('regency_id', null)),

            Select::make('regency_id')
                ->label('Kabupaten/Kota')
                ->relationship(
                    'regency',
                    'name',
                    fn (Builder $query, Get $get) => $query->where('province_id', $get('province_id')),
                )
                ->searchable()
                ->preload()
                ->required($regencyRequired)
                ->disabled(fn (Get $get): bool => blank($get('province_id')))
                ->helperText(fn (Get $get): ?string => blank($get('province_id')) ? 'Pilih provinsi terlebih dahulu' : null),
        ];
    }

    /**
     * Latitude/longitude + peta Leaflet. Klik peta atau geser marker untuk mengisi koordinat.
     */
    public static function coordinates(string $heading = 'Koordinat Lokasi'): Section
    {
        return Section::make($heading)
            ->description('Klik pada peta atau geser marker untuk menentukan titik. Sistem koordinat WGS84 (EPSG:4326).')
            ->icon('heroicon-o-map-pin')
            ->schema([
                ViewField::make('map_picker')
                    ->view('filament.forms.map-picker')
                    ->dehydrated(false)
                    ->columnSpanFull(),

                Grid::make(2)->schema([
                    TextInput::make('latitude')
                        ->label('Latitude')
                        ->numeric()
                        ->minValue(-90)
                        ->maxValue(90)
                        ->step('any')
                        ->placeholder('-6.2088'),

                    TextInput::make('longitude')
                        ->label('Longitude')
                        ->numeric()
                        ->minValue(-180)
                        ->maxValue(180)
                        ->step('any')
                        ->placeholder('106.8456'),
                ]),
            ]);
    }

    public static function publication(): Section
    {
        return Section::make('Publikasi')
            ->icon('heroicon-o-globe-alt')
            ->schema([
                Select::make('status')
                    ->label('Status')
                    ->options(PublishStatus::class)
                    ->default(PublishStatus::Draft)
                    ->required()
                    ->native(false),

                DateTimePicker::make('published_at')
                    ->label('Tanggal publikasi')
                    ->helperText('Terisi otomatis saat pertama kali dipublikasikan')
                    ->native(false)
                    ->seconds(false),
            ]);
    }
}
