<?php

namespace App\Models\Concerns;

use App\Models\Category;
use App\Models\Province;
use App\Models\Regency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Relasi & atribut bersama konten berlokasi (kuliner, wisata).
 */
trait BelongsToRegion
{
    public function initializeBelongsToRegion(): void
    {
        $this->mergeCasts([
            'latitude' => 'float',
            'longitude' => 'float',
            'gallery' => 'array',
        ]);
    }

    public function scopeWithCoordinates(Builder $query): Builder
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function regency(): BelongsTo
    {
        return $this->belongsTo(Regency::class);
    }
}
