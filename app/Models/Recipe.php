<?php

namespace App\Models;

use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'culinary_id', 'title', 'description', 'servings', 'prep_minutes', 'cook_minutes',
    'difficulty', 'notes', 'status', 'published_at',
])]
class Recipe extends Model
{
    use HasPublishStatus;

    public const DIFFICULTIES = [
        'mudah' => 'Mudah',
        'sedang' => 'Sedang',
        'sulit' => 'Sulit',
    ];

    public function culinary(): BelongsTo
    {
        return $this->belongsTo(Culinary::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class)->orderBy('sort');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(RecipeStep::class)->orderBy('sort');
    }
}
