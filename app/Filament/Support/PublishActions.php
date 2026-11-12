<?php

namespace App\Filament\Support;

use App\Enums\PublishStatus;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Spesifikasi CMS > Publikasi Konten: status draft & published.
 */
class PublishActions
{
    public static function toggle(): Action
    {
        return Action::make('togglePublish')
            ->label(fn (Model $record): string => $record->status === PublishStatus::Published ? 'Jadikan Draft' : 'Publikasikan')
            ->icon(fn (Model $record): string => $record->status === PublishStatus::Published ? 'heroicon-o-eye-slash' : 'heroicon-o-paper-airplane')
            ->color(fn (Model $record): string => $record->status === PublishStatus::Published ? 'gray' : 'success')
            ->requiresConfirmation()
            ->action(function (Model $record): void {
                $record->update([
                    'status' => $record->status === PublishStatus::Published ? PublishStatus::Draft : PublishStatus::Published,
                ]);

                Notification::make()->title('Status diperbarui')->success()->send();
            });
    }

    /** @return array<int, BulkAction> */
    public static function bulk(): array
    {
        return [
            BulkAction::make('publish')
                ->label('Publikasikan')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn (Collection $records) => self::setStatus($records, PublishStatus::Published))
                ->deselectRecordsAfterCompletion(),

            BulkAction::make('unpublish')
                ->label('Jadikan Draft')
                ->icon('heroicon-o-eye-slash')
                ->requiresConfirmation()
                ->action(fn (Collection $records) => self::setStatus($records, PublishStatus::Draft))
                ->deselectRecordsAfterCompletion(),
        ];
    }

    public static function filter(): SelectFilter
    {
        return SelectFilter::make('status')
            ->label('Status')
            ->options(PublishStatus::class);
    }

    private static function setStatus(Collection $records, PublishStatus $status): void
    {
        // Lewat model (bukan mass update) agar published_at ikut terisi.
        $records->each(fn (Model $record) => $record->update(['status' => $status]));

        Notification::make()
            ->title($records->count() . ' data diubah menjadi ' . $status->getLabel())
            ->success()
            ->send();
    }
}
