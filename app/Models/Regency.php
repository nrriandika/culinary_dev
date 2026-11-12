<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['province_id', 'code', 'type', 'name', 'slug', 'description', 'image', 'latitude', 'longitude'])]
class Regency extends Model
{
    public const TYPES = [
        'kabupaten' => 'Kabupaten',
        'kota' => 'Kota',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function culinaries(): HasMany
    {
        return $this->hasMany(Culinary::class);
    }

    public function destinations(): HasMany
    {
        return $this->hasMany(Destination::class);
    }
}
