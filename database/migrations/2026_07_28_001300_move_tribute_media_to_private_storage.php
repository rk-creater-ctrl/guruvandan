<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tribute_media')->where('disk', 'public')->orderBy('id')->each(function ($media): void {
            if (Storage::disk('public')->exists($media->path)) {
                Storage::disk('local')->put($media->path, Storage::disk('public')->get($media->path));
                Storage::disk('public')->delete($media->path);
            }

            DB::table('tribute_media')->where('id', $media->id)->update(['disk' => 'local']);
        });
    }

    public function down(): void
    {
        // Private media is intentionally not moved back to a publicly addressable disk.
    }
};
