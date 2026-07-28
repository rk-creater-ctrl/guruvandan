<?php

namespace App\Services;

use App\Models\Tribute;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaUploadService
{
    public function store(Tribute $tribute, UploadedFile $file, string $mediaType): void
    {
        $detectedKind = explode('/', (string) $file->getMimeType(), 2)[0];
        if (in_array($detectedKind, ['image', 'audio', 'video'], true)) {
            $mediaType = $detectedKind;
        }

        $path = $file->storeAs(
            'tributes/'.$tribute->id,
            Str::uuid().'.'.strtolower($file->getClientOriginalExtension()),
            'local'
        );

        $tribute->media()->create([
            'media_type' => $mediaType,
            'disk' => 'local',
            'path' => $path,
            'original_name' => Str::limit(basename($file->getClientOriginalName()), 240, ''),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize(),
        ]);
    }

    public function deleteAll(Tribute $tribute): void
    {
        $tribute->media->each(function ($media): void {
            Storage::disk($media->disk)->delete(array_filter([$media->path, $media->thumbnail_path]));
            $media->delete();
        });
    }
}
