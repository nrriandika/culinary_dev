<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'slug', 'description', 'image', 'latitude', 'longitude'])]
class Province extends Model
{
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function regencies(): HasMany
    {
        return $this->hasMany(Regency::class);
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
