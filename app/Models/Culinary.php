<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRegion;
use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'category_id', 'province_id', 'regency_id', 'name', 'slug', 'summary', 'description',
    'image', 'gallery', 'latitude', 'longitude', 'status', 'published_at',
])]
class Culinary extends Model
{
    use BelongsToRegion, HasPublishStatus;

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }
}
