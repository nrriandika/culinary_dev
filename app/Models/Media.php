<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['title', 'disk', 'path', 'original_name', 'mime_type', 'size', 'alt', 'folder', 'uploaded_by'])]
class Media extends Model
{
    public const FOLDERS = [
        'kuliner' => 'Kuliner',
        'wisata' => 'Wisata',
        'wilayah' => 'Wilayah',
        'umum' => 'Umum',
    ];

    protected static function booted(): void
    {
        // Ganti file: hapus file lama dari storage.
        static::updating(function (Media $media): void {
            if ($media->isDirty('path') && filled($old = $media->getOriginal('path'))) {
                Storage::disk($media->getOriginal('disk') ?? 'public')->delete($old);
            }
        });

        // Hapus record: hapus juga filenya.
        static::deleted(function (Media $media): void {
            Storage::disk($media->disk)->delete($media->path);
        });

        // Lengkapi metadata file setiap kali path berubah.
        static::saving(function (Media $media): void {
            if ($media->isDirty('path') && Storage::disk($media->disk)->exists($media->path)) {
                $media->mime_type = Storage::disk($media->disk)->mimeType($media->path) ?: null;
                $media->size = Storage::disk($media->disk)->size($media->path);
            }
        });
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
