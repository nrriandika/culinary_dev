<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Spesifikasi CMS > Administrator: login dan pengelolaan akun admin dasar.
 * Hanya Super Admin yang dapat mengelola akun.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'administrator';

    protected static ?string $modelLabel = 'administrator';

    protected static ?string $pluralModelLabel = 'administrator';

    protected static ?string $navigationLabel = 'Administrator';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    /** Tidak boleh menghapus akun sendiri. */
    public static function canDelete(Model $record): bool
    {
        return static::canAccess() && $record->isNot(auth()->user());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Nama')->required()->maxLength(255),
                TextInput::make('email')->label('Email')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->rule('min:8')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText(fn (string $operation): ?string => $operation === 'edit' ? 'Kosongkan jika tidak ingin mengubah password' : null),
                Select::make('role')->label('Peran')->options(UserRole::class)->default(UserRole::Admin)->required()->native(false),
                Toggle::make('is_active')
                    ->label('Akun aktif')
                    ->helperText('Akun nonaktif tidak dapat login')
                    ->default(true)
                    // Cegah menonaktifkan akun sendiri
                    ->disabled(fn (?User $record): bool => $record?->is(auth()->user()) ?? false)
                    ->dehydrated(fn (?User $record): bool => ! ($record?->is(auth()->user()) ?? false)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable()->weight('medium')
                    ->description(fn (User $record): ?string => $record->is(auth()->user()) ? 'Akun Anda' : null),
                TextColumn::make('email')->label('Email')->searchable()->copyable(),
                TextColumn::make('role')->label('Peran')->badge()->color(fn (UserRole $state) => $state === UserRole::SuperAdmin ? 'primary' : 'gray'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('last_login_at')->label('Login terakhir')->since()->placeholder('Belum pernah')->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')->label('Peran')->options(UserRole::class),
                TernaryFilter::make('is_active')->label('Status akun'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
