<?php

namespace App\Filament\Resources\Destinations;

use App\Enums\CategoryType;
use App\Filament\Resources\Destinations\Pages\CreateDestination;
use App\Filament\Resources\Destinations\Pages\EditDestination;
use App\Filament\Resources\Destinations\Pages\ListDestinations;
use App\Filament\Support\Fields;
use App\Filament\Support\PublishActions;
use App\Models\Destination;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DestinationResource extends Resource
{
    protected static ?string $model = Destination::class;

    protected static ?string $slug = 'wisata';

    protected static ?string $modelLabel = 'destinasi wisata';

    protected static ?string $pluralModelLabel = 'destinasi wisata';

    protected static ?string $navigationLabel = 'Wisata';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCamera;

    protected static string|UnitEnum|null $navigationGroup = 'Konten';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(2)
                    ->schema([
                        Section::make('Informasi Destinasi')
                            ->columns(2)
                            ->schema([
                                ...Fields::nameAndSlug('Nama destinasi'),
                                Fields::category(CategoryType::Destination),
                                Textarea::make('summary')
                                    ->label('Ringkasan')
                                    ->rows(2)
                                    ->maxLength(500)
                                    ->columnSpanFull(),
                                RichEditor::make('description')
                                    ->label('Deskripsi')
                                    ->fileAttachmentsDisk('public')
                                    ->fileAttachmentsDirectory('konten')
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Lokasi')
                            ->columns(2)
                            ->schema([
                                ...Fields::region(),
                                Textarea::make('address')->label('Alamat')->rows(2)->columnSpanFull(),
                            ]),

                        Fields::coordinates(),

                        Section::make('Informasi Pendukung')
                            ->columns(3)
                            ->collapsible()
                            ->schema([
                                TextInput::make('opening_hours')->label('Jam buka')->placeholder('08.00 – 17.00 WIB'),
                                TextInput::make('ticket_price')->label('Harga tiket')->placeholder('Rp 25.000'),
                                TextInput::make('contact')->label('Kontak')->placeholder('Telp / website'),
                            ]),
                    ]),

                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Fields::publication(),

                        Section::make('Foto')
                            ->schema([
                                FileUpload::make('image')
                                    ->label('Foto utama')
                                    ->image()
                                    ->imageEditor()
                                    ->disk('public')
                                    ->directory('wisata')
                                    ->maxSize(4096),
                                FileUpload::make('gallery')
                                    ->label('Galeri foto')
                                    ->image()
                                    ->multiple()
                                    ->reorderable()
                                    ->maxFiles(20)
                                    ->disk('public')
                                    ->directory('wisata/galeri')
                                    ->maxSize(4096),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['category', 'province', 'regency']))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                ImageColumn::make('image')->label('')->disk('public')->square()->size(44),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn (Destination $record): ?string => $record->category?->name),
                TextColumn::make('province.name')->label('Provinsi')->searchable()->sortable(),
                TextColumn::make('regency.name')->label('Kab/Kota')->searchable()->toggleable(),
                IconColumn::make('has_coordinates')
                    ->label('Koordinat')
                    ->state(fn (Destination $record): bool => filled($record->latitude) && filled($record->longitude))
                    ->boolean(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('updated_at')->label('Diperbarui')->since()->sortable()->toggleable(),
            ])
            ->filters([
                PublishActions::filter(),
                SelectFilter::make('province_id')->label('Provinsi')->relationship('province', 'name')->searchable()->preload(),
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name', fn (Builder $query) => $query->where('type', CategoryType::Destination))
                    ->preload(),
                TernaryFilter::make('coordinates')
                    ->label('Koordinat')
                    ->trueLabel('Sudah ada')
                    ->falseLabel('Belum ada')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('latitude')->whereNotNull('longitude'),
                        false: fn (Builder $q) => $q->where(fn (Builder $q) => $q->whereNull('latitude')->orWhereNull('longitude')),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    PublishActions::toggle(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ...PublishActions::bulk(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'summary', 'address', 'province.name'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDestinations::route('/'),
            'create' => CreateDestination::route('/create'),
            'edit' => EditDestination::route('/{record}/edit'),
        ];
    }
}
