<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CategoryType: string implements HasLabel
{
    case Culinary = 'culinary';
    case Destination = 'destination';

    public function getLabel(): string
    {
        return match ($this) {
            self::Culinary => 'Kuliner',
            self::Destination => 'Wisata',
        };
    }
}
