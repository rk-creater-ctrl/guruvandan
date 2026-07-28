<?php

namespace App\Models;

use App\Enums\TributeLanguage;
use App\Enums\TributeStatus;
use App\Enums\TributeType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'student_id',
    'teacher_id',
    'tribute_type',
    'title',
    'message',
    'original_message',
    'language',
    'status',
    'rejection_reason',
    'approved_by',
    'approved_at',
    'moderator_edited_at',
    'resubmitted_at',
    'is_featured',
])]
class Tribute extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tribute_type' => TributeType::class,
            'language' => TributeLanguage::class,
            'status' => TributeStatus::class,
            'approved_at' => 'datetime',
            'moderator_edited_at' => 'datetime',
            'resubmitted_at' => 'datetime',
            'is_featured' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function media(): HasMany
    {
        return $this->hasMany(TributeMedia::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(TributeLike::class);
    }
}
