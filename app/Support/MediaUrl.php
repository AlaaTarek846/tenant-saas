<?php

namespace App\Support;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;

class MediaUrl
{
    public static function fromMedia(?Media $media): ?string
    {
        if (! $media?->file_name) {
            return null;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk($media->disk ?? 'public');

        return $disk->url($media->file_name);
    }
}
