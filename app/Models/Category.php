<?php

namespace App\Models;

use App\Enums\CategoryType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['type', 'name', 'slug', 'description'])]
class Category extends Model
{
    protected function casts(): array
    {
        return [
            'type' => CategoryType::class,
        ];
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
