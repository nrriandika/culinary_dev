<?php

namespace App\Filament\Resources\Culinaries\Pages;

use App\Enums\PublishStatus;
use App\Filament\Resources\Culinaries\CulinaryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCulinaries extends ListRecords
{
    protected static string $resource = CulinaryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua'),
            'published' => Tab::make('Published')->modifyQueryUsing(fn (Builder $q) => $q->where('status', PublishStatus::Published)),
            'draft' => Tab::make('Draft')->modifyQueryUsing(fn (Builder $q) => $q->where('status', PublishStatus::Draft)),
        ];
    }
}
