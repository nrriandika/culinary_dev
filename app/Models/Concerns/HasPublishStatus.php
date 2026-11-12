<?php

namespace App\Models\Concerns;

use App\Enums\PublishStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Status publikasi konten (draft | published).
 * published_at diisi otomatis saat pertama kali dipublikasikan.
 */
trait HasPublishStatus
{
    public static function bootHasPublishStatus(): void
    {
        static::saving(function (Model $model): void {
            if ($model->status === PublishStatus::Published && blank($model->published_at)) {
                $model->published_at = now();
            }
        });
    }

    public function initializeHasPublishStatus(): void
    {
        $this->mergeCasts([
            'status' => PublishStatus::class,
            'published_at' => 'datetime',
        ]);

        $this->attributes['status'] ??= PublishStatus::Draft->value;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PublishStatus::Published);
    }

    public function isPublished(): bool
    {
        return $this->status === PublishStatus::Published;
    }
}
