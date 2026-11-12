<?php

namespace App\Filament\Resources\Provinces;

use App\Filament\Resources\Provinces\Pages\CreateProvince;
use App\Filament\Resources\Provinces\Pages\EditProvince;
use App\Filament\Resources\Provinces\Pages\ListProvinces;
use App\Filament\Resources\Provinces\RelationManagers\RegenciesRelationManager;
use App\Filament\Support\Fields;
use App\Filament\Support\RegionGuard;
use App\Models\Province;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ProvinceResource extends Resource
{
    protected static ?string $model = Province::class;

    protected static ?string $slug = 'provinsi';

    protected static ?string $modelLabel = 'provinsi';

    protected static ?string $pluralModelLabel = 'provinsi';

    protected static ?string $navigationLabel = 'Provinsi';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedGlobeAsiaAustralia;

    protected static string | UnitEnum | null $navigationGroup = 'Wilayah';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(2)
                    ->schema([
                        Section::make('Profil Wilayah')
                            ->columns(2)
                            ->schema([
                                TextInput::make('code')
                                    ->label('Kode wilayah')
                                    ->helperText('2 digit kode Kemendagri, mis. 51 untuk Bali')
                                    ->required()
                                    ->length(2)
                                    ->numeric()
                                    ->unique(ignoreRecord: true),
                                ...Fields::nameAndSlug('Nama provinsi'),
                                RichEditor::make('description')
                                    ->label('Deskripsi singkat')
                                    ->toolbarButtons(['bold', 'italic', 'link', 'bulletList', 'orderedList', 'undo', 'redo'])
                                    ->columnSpanFull(),
                            ]),

                        Fields::coordinates('Titik Tengah Wilayah'),
                    ]),

                Section::make('Gambar')
                    ->columnSpan(1)
                    ->schema([
                        FileUpload::make('image')
                            ->hiddenLabel()
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('wilayah')
                            ->maxSize(4096),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount(['regencies', 'culinaries', 'destinations']))
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')->label('Kode')->sortable()->searchable()->width('80px'),
                ImageColumn::make('image')->label('')->disk('public')->square()->size(40),
                TextColumn::make('name')->label('Provinsi')->searchable()->sortable()->weight('medium'),
                TextColumn::make('regencies_count')->label('Kab/Kota')->badge()->color('gray')->sortable(),
                TextColumn::make('culinaries_count')->label('Kuliner')->badge()->color('warning')->sortable(),
                TextColumn::make('destinations_count')->label('Wisata')->badge()->color('success')->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                RegionGuard::deleteAction(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RegenciesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProvinces::route('/'),
            'create' => CreateProvince::route('/create'),
            'edit' => EditProvince::route('/{record}/edit'),
        ];
    }
}
