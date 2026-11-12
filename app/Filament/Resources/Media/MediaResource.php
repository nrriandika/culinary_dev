<?php

namespace App\Filament\Resources\Media;

use App\Filament\Resources\Media\Pages\ManageMedia;
use App\Models\Media;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Number;
use UnitEnum;

/**
 * Spesifikasi CMS > Media Manager: upload, mengganti, dan menghapus foto.
 */
class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static ?string $slug = 'media';

    protected static ?string $modelLabel = 'media';

    protected static ?string $pluralModelLabel = 'media';

    protected static ?string $navigationLabel = 'Media Manager';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string | UnitEnum | null $navigationGroup = 'Media';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                FileUpload::make('path')
                    ->label('File foto')
                    ->helperText('Saat mengedit, unggah foto baru untuk mengganti foto lama (file lama otomatis dihapus).')
                    ->image()
                    ->imageEditor()
                    ->required()
                    ->disk('public')
                    ->directory(fn (Get $get): string => 'media/' . ($get('folder') ?: 'umum'))
                    ->storeFileNamesIn('original_name')
                    ->maxSize(8192)
                    ->columnSpanFull(),
                TextInput::make('title')->label('Judul')->required()->maxLength(255),
                Select::make('folder')->label('Folder')->options(Media::FOLDERS)->default('umum')->required()->native(false)->live(),
                TextInput::make('alt')->label('Teks alternatif (alt)')->helperText('Deskripsi singkat gambar untuk SEO & aksesibilitas')->maxLength(255)->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('uploader'))
            ->defaultSort('created_at', 'desc')
            ->contentGrid(['md' => 3, 'xl' => 4])
            ->paginated([12, 24, 48])
            ->defaultPaginationPageOption(12)
            ->columns([
                Stack::make([
                    ImageColumn::make('path')
                        ->label('')
                        ->disk('public')
                        ->imageHeight(160)
                        ->extraImgAttributes(['class' => 'w-full object-cover rounded-lg', 'style' => 'width:100%']),
                    TextColumn::make('title')->label('Judul')->weight('medium')->searchable()->sortable(),
                    Panel::make([
                        Stack::make([
                            TextColumn::make('folder')->badge()->formatStateUsing(fn (string $state) => Media::FOLDERS[$state] ?? $state),
                            TextColumn::make('size')
                                ->formatStateUsing(fn (?int $state) => $state ? Number::fileSize($state, precision: 1) : '-')
                                ->color('gray')
                                ->size('xs'),
                            TextColumn::make('uploader.name')->prefix('Oleh ')->color('gray')->size('xs'),
                        ])->space(1),
                    ]),
                ])->space(2),
            ])
            ->filters([
                SelectFilter::make('folder')->label('Folder')->options(Media::FOLDERS),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Buka')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Media $record): string => $record->url(), shouldOpenInNewTab: true),
                EditAction::make()->label('Ganti'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMedia::route('/'),
        ];
    }
}
