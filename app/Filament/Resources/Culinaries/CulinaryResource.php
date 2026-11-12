<?php

namespace App\Filament\Resources\Culinaries;

use App\Enums\CategoryType;
use App\Filament\Resources\Culinaries\Pages\CreateCulinary;
use App\Filament\Resources\Culinaries\Pages\EditCulinary;
use App\Filament\Resources\Culinaries\Pages\ListCulinaries;
use App\Filament\Support\Fields;
use App\Filament\Support\PublishActions;
use App\Models\Culinary;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CulinaryResource extends Resource
{
    protected static ?string $model = Culinary::class;

    protected static ?string $slug = 'kuliner';

    protected static ?string $modelLabel = 'kuliner';

    protected static ?string $pluralModelLabel = 'kuliner';

    protected static ?string $navigationLabel = 'Kuliner';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedCake;

    protected static string | UnitEnum | null $navigationGroup = 'Konten';

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
                        Section::make('Informasi Kuliner')
                            ->columns(2)
                            ->schema([
                                ...Fields::nameAndSlug('Nama makanan'),
                                Fields::category(CategoryType::Culinary),
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

                        Section::make('Asal Daerah')
                            ->columns(2)
                            ->schema(Fields::region()),

                        Fields::coordinates('Titik Asal Kuliner'),
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
                                    ->directory('kuliner')
                                    ->maxSize(4096),
                                FileUpload::make('gallery')
                                    ->label('Galeri')
                                    ->image()
                                    ->multiple()
                                    ->reorderable()
                                    ->maxFiles(12)
                                    ->disk('public')
                                    ->directory('kuliner/galeri')
                                    ->maxSize(4096),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['category', 'province', 'regency'])->withCount('recipes'))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                ImageColumn::make('image')->label('')->disk('public')->square()->size(44),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn (Culinary $record): ?string => $record->category?->name),
                TextColumn::make('province.name')->label('Provinsi')->searchable()->sortable(),
                TextColumn::make('regency.name')->label('Kab/Kota')->searchable()->toggleable(),
                TextColumn::make('recipes_count')->label('Resep')->badge()->color('gray')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('updated_at')->label('Diperbarui')->since()->sortable()->toggleable(),
            ])
            ->filters([
                PublishActions::filter(),
                SelectFilter::make('province_id')->label('Provinsi')->relationship('province', 'name')->searchable()->preload(),
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name', fn (Builder $query) => $query->where('type', CategoryType::Culinary))
                    ->preload(),
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
        return ['name', 'summary', 'province.name'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCulinaries::route('/'),
            'create' => CreateCulinary::route('/create'),
            'edit' => EditCulinary::route('/{record}/edit'),
        ];
    }
}
