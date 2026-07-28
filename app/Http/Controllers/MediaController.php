<?php

namespace App\Http\Controllers;

use App\Models\TributeMedia;
use App\Services\RevealService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function show(Request $request, TributeMedia $media, RevealService $reveal): StreamedResponse
    {
        $media->loadMissing('tribute.teacher');
        $tribute = $media->tribute;
        $user = $request->user();
        $allowed = $user?->isAdmin()
            || $user?->id === $tribute->student_id
            || ($user?->isTeacher() && $user->teacherProfile?->id === $tribute->teacher_id && $tribute->status->value === 'approved' && $reveal->isRevealed())
            || ($tribute->status->value === 'approved' && $reveal->isRevealed());

        abort_unless($allowed, 403);
        abort_unless(Storage::disk($media->disk)->exists($media->path), 404);

        $downloadName = preg_replace('/[^A-Za-z0-9._ -]/', '_', $media->original_name) ?: 'tribute-media';

        return Storage::disk($media->disk)->response($media->path, $downloadName, [
            'Content-Type' => $media->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
