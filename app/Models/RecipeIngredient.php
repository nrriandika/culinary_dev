<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['recipe_id', 'name', 'quantity', 'unit', 'note', 'sort'])]
class RecipeIngredient extends Model
{
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
