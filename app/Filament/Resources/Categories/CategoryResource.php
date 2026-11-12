<?php

namespace App\Filament\Resources\Categories;

use App\Enums\CategoryType;
use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Filament\Support\Fields;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $slug = 'kategori';

    protected static ?string $modelLabel = 'kategori';

    protected static ?string $pluralModelLabel = 'kategori';

    protected static ?string $navigationLabel = 'Kategori';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedTag;

    protected static string | UnitEnum | null $navigationGroup = 'Konten';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        [$name, $slug] = Fields::nameAndSlug('Nama kategori', uniqueSlug: false);

        return $schema
            ->columns(1)
            ->components([
                Select::make('type')
                    ->label('Jenis')
                    ->options(CategoryType::class)
                    ->required()
                    ->native(false),
                $name,
                // Slug unik per jenis (kuliner/wisata)
                $slug->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('type', $get('type')),
                ),
                Textarea::make('description')->label('Deskripsi')->rows(2)->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount(['culinaries', 'destinations']))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable()->weight('medium'),
                TextColumn::make('type')->label('Jenis')->badge()->color(fn (CategoryType $state) => $state === CategoryType::Culinary ? 'warning' : 'success'),
                TextColumn::make('slug')->color('gray')->toggleable(),
                TextColumn::make('contents_count')
                    ->label('Jumlah konten')
                    ->state(fn (Category $record): int => $record->type === CategoryType::Culinary
                        ? $record->culinaries_count
                        : $record->destinations_count)
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('type')->label('Jenis')->options(CategoryType::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->modalDescription('Konten dengan kategori ini tidak ikut terhapus, kategorinya akan dikosongkan.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategories::route('/'),
        ];
    }
}
