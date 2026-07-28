@extends('layouts.app')
@section('content')
<main class="content-page">
    <div class="section-heading"><p class="eyebrow">Event Management</p><h1>Guru Purnima event</h1><p>Manage event details and an independently editable, ordered schedule.</p></div>
    <section class="management-layout">
        <form class="tribute-form" method="POST" action="{{ route('admin.event.save') }}">@csrf
            <h2>Event details</h2>
            <label>Event title<input name="title" value="{{ old('title', $event?->title) }}" required></label>
            <label>Description<textarea name="description" rows="4">{{ old('description', $event?->description) }}</textarea></label>
            <div class="form-grid two"><label>Event date<input type="date" name="event_date" value="{{ old('event_date', optional($event?->event_date)->format('Y-m-d')) }}" required></label><label>Event time<input type="time" name="event_time" value="{{ old('event_time', $event?->event_time) }}"></label></div>
            <label>Venue<input name="venue" value="{{ old('venue', $event?->venue) }}"></label>
            <label>Chief guest<input name="chief_guest" value="{{ old('chief_guest', $event?->chief_guest) }}"></label>
            <label>Livestream URL<input type="url" name="livestream_url" value="{{ old('livestream_url', $event?->livestream_url) }}"></label>
            <button class="button primary">Save event details</button>
        </form>
        @if($event)
        <form class="tribute-form" method="POST" action="{{ route('admin.event.schedules.store', $event) }}">@csrf
            <h2>Add schedule item</h2>
            <div class="form-grid two"><label>Time<input type="time" name="start_time" required></label><label>Order<input type="number" name="sort_order" min="0" value="{{ $event->schedules->count() }}" required></label></div>
            <label>Title<input name="title" required></label><label>Description<textarea name="detail" rows="3"></textarea></label>
            <div class="form-grid two"><label>Speaker<input name="speaker"></label><label>Location<input name="location"></label></div>
            <label class="inline-check"><input type="checkbox" name="is_enabled" value="1" checked> Visible publicly</label>
            <button class="button secondary">Add schedule item</button>
        </form>
        @endif
    </section>
    @if($event)
    <section class="highlight-section"><div class="section-heading"><p class="eyebrow">Schedule</p><h2>Ordered programme</h2></div>
        <div class="schedule-admin-list">
        @forelse($event->schedules as $item)
            <article class="panel schedule-admin-item">
                <div><strong>{{ \Carbon\Carbon::parse($item->start_time)->format('h:i A') }} &bull; {{ $item->title }}</strong><p>{{ $item->detail }}</p><small>{{ $item->speaker }} {{ $item->location ? 'at '.$item->location : '' }} &bull; {{ $item->is_enabled ? 'Visible' : 'Hidden' }}</small></div>
                <div class="table-actions"><span class="status-badge {{ $item->is_enabled ? 'approved' : 'pending' }}">Order {{ $item->sort_order }}</span><button class="button ghost" type="button" data-edit-schedule="{{ $item->id }}">Edit</button><button class="button danger" type="submit" form="delete-schedule-{{ $item->id }}">Delete</button></div>
            </article>
            <dialog id="schedule-dialog-{{ $item->id }}"><form class="tribute-form" method="POST" action="{{ route('admin.event.schedules.update', $item) }}">@csrf @method('PUT')<h2>Edit schedule item</h2><label>Time<input type="time" name="start_time" value="{{ substr($item->start_time,0,5) }}" required></label><label>Title<input name="title" value="{{ $item->title }}" required></label><label>Description<textarea name="detail">{{ $item->detail }}</textarea></label><label>Speaker<input name="speaker" value="{{ $item->speaker }}"></label><label>Location<input name="location" value="{{ $item->location }}"></label><label>Order<input type="number" name="sort_order" value="{{ $item->sort_order }}" required></label><label class="inline-check"><input type="checkbox" name="is_enabled" value="1" @checked($item->is_enabled)> Visible publicly</label><div class="form-actions"><button class="button primary">Save</button><button class="button ghost" type="button" data-close-dialog>Cancel</button></div></form></dialog>
        @empty<p class="empty-state">No schedule items yet.</p>@endforelse
        </div>
        <form method="POST" action="{{ route('admin.event.schedules.reorder', $event) }}">@csrf @method('PATCH')
            @foreach($event->schedules as $item)<input type="hidden" name="schedule_ids[]" value="{{ $item->id }}">@endforeach
            <button class="button secondary">Normalize displayed order</button>
        </form>
        @foreach($event->schedules as $item)<form id="delete-schedule-{{ $item->id }}" method="POST" action="{{ route('admin.event.schedules.destroy', $item) }}" data-confirm="Delete this schedule item?">@csrf @method('DELETE')</form>@endforeach
    </section>
    @endif
</main>
@endsection
