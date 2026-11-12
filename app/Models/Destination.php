<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRegion;
use App\Models\Concerns\HasPublishStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'category_id', 'province_id', 'regency_id', 'name', 'slug', 'summary', 'description',
    'address', 'opening_hours', 'ticket_price', 'contact',
    'image', 'gallery', 'latitude', 'longitude', 'status', 'published_at',
])]
class Destination extends Model
{
    use BelongsToRegion, HasPublishStatus;
}
