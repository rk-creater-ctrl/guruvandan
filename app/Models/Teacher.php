<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id',
    'slug',
    'designation',
    'photo_path',
    'cover_image_path',
    'short_intro',
    'banner_title',
    'qualification',
    'years_experience',
    'joining_year',
    'location',
    'bio',
    'quote',
    'is_active',
    'is_public',
    'archived_at',
])]
class Teacher extends Model
{
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'years_experience' => 'integer',
            'joining_year' => 'integer',
            'archived_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tributes(): HasMany
    {
        return $this->hasMany(Tribute::class);
    }

    public function reply(): HasOne
    {
        return $this->hasOne(TeacherReply::class);
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class);
    }
}
