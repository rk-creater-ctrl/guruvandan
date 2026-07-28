<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tribute_id', 'media_type', 'disk', 'path', 'thumbnail_path', 'original_name', 'mime_type', 'size', 'duration_seconds'])]
class TributeMedia extends Model
{
    use HasFactory;

    public function tribute(): BelongsTo
    {
        return $this->belongsTo(Tribute::class);
    }

    protected function casts(): array
    {
        return ['duration_seconds' => 'integer'];
    }
}
