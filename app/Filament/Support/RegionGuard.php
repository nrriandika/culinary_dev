<?php

namespace App\Filament\Support;

use App\Models\Province;
use App\Models\Regency;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;

/**
 * Cegah penghapusan wilayah yang masih dipakai konten kuliner/wisata.
 */
class RegionGuard
{
    public static function deleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->before(function (DeleteAction $action, Province | Regency $record): void {
                $used = $record->culinaries()->count() + $record->destinations()->count();

                if ($used > 0) {
                    Notification::make()
                        ->title('Wilayah tidak dapat dihapus')
                        ->body("Masih dipakai oleh {$used} data kuliner/wisata. Pindahkan atau hapus konten tersebut terlebih dahulu.")
                        ->danger()
                        ->send();

                    $action->cancel();
                }
            });
    }
}
