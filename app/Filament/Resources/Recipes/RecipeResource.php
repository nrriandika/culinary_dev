<?php

namespace App\Filament\Resources\Recipes;

use App\Filament\Resources\Recipes\Pages\CreateRecipe;
use App\Filament\Resources\Recipes\Pages\EditRecipe;
use App\Filament\Resources\Recipes\Pages\ListRecipes;
use App\Filament\Support\Fields;
use App\Filament\Support\PublishActions;
use App\Models\Recipe;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class RecipeResource extends Resource
{
    protected static ?string $model = Recipe::class;

    protected static ?string $slug = 'resep';

    protected static ?string $modelLabel = 'resep';

    protected static ?string $pluralModelLabel = 'resep';

    protected static ?string $navigationLabel = 'Resep';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string | UnitEnum | null $navigationGroup = 'Konten';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->columnSpan(2)
                    ->schema([
                        Section::make('Informasi Resep')
                            ->columns(2)
                            ->schema([
                                Select::make('culinary_id')
                                    ->label('Kuliner')
                                    ->relationship('culinary', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                TextInput::make('title')
                                    ->label('Judul resep')
                                    ->placeholder('mis. Rendang Daging Sapi Khas Padang')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->label('Pengantar')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Bahan & Takaran')
                            ->icon('heroicon-o-list-bullet')
                            ->schema([
                                Repeater::make('ingredients')
                                    ->hiddenLabel()
                                    ->relationship()
                                    ->orderColumn('sort')
                                    ->table([
                                        Repeater\TableColumn::make('Bahan')->markAsRequired(),
                                        Repeater\TableColumn::make('Takaran')->width('110px'),
                                        Repeater\TableColumn::make('Satuan')->width('130px'),
                                        Repeater\TableColumn::make('Keterangan'),
                                    ])
                                    ->schema([
                                        TextInput::make('name')->required()->placeholder('Daging sapi'),
                                        TextInput::make('quantity')->placeholder('500'),
                                        TextInput::make('unit')->placeholder('gram')->datalist(['gram', 'kg', 'ml', 'liter', 'sdm', 'sdt', 'butir', 'buah', 'siung', 'lembar', 'batang', 'ruas', 'secukupnya']),
                                        TextInput::make('note')->placeholder('potong kotak'),
                                    ])
                                    ->addActionLabel('Tambah bahan')
                                    ->defaultItems(1),
                            ]),

                        Section::make('Langkah Memasak')
                            ->icon('heroicon-o-queue-list')
                            ->schema([
                                Repeater::make('steps')
                                    ->hiddenLabel()
                                    ->relationship()
                                    ->orderColumn('sort')
                                    ->itemLabel(fn (array $state): ?string => str($state['instruction'] ?? '')->limit(60)->toString() ?: null)
                                    ->schema([
                                        Textarea::make('instruction')->label('Instruksi')->rows(3)->required(),
                                        FileUpload::make('image')
                                            ->label('Foto langkah (opsional)')
                                            ->image()
                                            ->disk('public')
                                            ->directory('resep')
                                            ->maxSize(4096),
                                    ])
                                    ->collapsible()
                                    ->addActionLabel('Tambah langkah')
                                    ->defaultItems(1),
                            ]),
                    ]),

                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Fields::publication(),

                        Section::make('Detail')
                            ->schema([
                                TextInput::make('servings')->label('Porsi')->numeric()->minValue(1)->suffix('porsi'),
                                TextInput::make('prep_minutes')->label('Waktu persiapan')->numeric()->minValue(0)->suffix('menit'),
                                TextInput::make('cook_minutes')->label('Waktu memasak')->numeric()->minValue(0)->suffix('menit'),
                                Select::make('difficulty')->label('Tingkat kesulitan')->options(Recipe::DIFFICULTIES)->native(false),
                                Textarea::make('notes')->label('Tips / catatan')->rows(3),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('culinary.province')->withCount(['ingredients', 'steps']))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Judul')->searchable()->sortable()->weight('medium'),
                TextColumn::make('culinary.name')
                    ->label('Kuliner')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Recipe $record): ?string => $record->culinary?->province?->name),
                TextColumn::make('ingredients_count')->label('Bahan')->badge()->color('gray'),
                TextColumn::make('steps_count')->label('Langkah')->badge()->color('gray'),
                TextColumn::make('difficulty')->label('Kesulitan')->formatStateUsing(fn (?string $state) => Recipe::DIFFICULTIES[$state] ?? '-')->toggleable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('updated_at')->label('Diperbarui')->since()->sortable()->toggleable(),
            ])
            ->filters([
                PublishActions::filter(),
                SelectFilter::make('culinary_id')->label('Kuliner')->relationship('culinary', 'name')->searchable()->preload(),
                SelectFilter::make('difficulty')->label('Kesulitan')->options(Recipe::DIFFICULTIES),
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
        return ['title', 'culinary.name'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecipes::route('/'),
            'create' => CreateRecipe::route('/create'),
            'edit' => EditRecipe::route('/{record}/edit'),
        ];
    }
}
