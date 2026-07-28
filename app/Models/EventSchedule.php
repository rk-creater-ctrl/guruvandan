<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['event_id', 'start_time', 'title', 'detail', 'speaker', 'location', 'is_enabled', 'sort_order'])]
class EventSchedule extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['is_enabled' => 'boolean', 'sort_order' => 'integer'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
