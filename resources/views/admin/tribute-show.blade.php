@extends('layouts.app')
@section('content')
<main class="content-page">
    <div class="section-heading"><p class="eyebrow">Moderation Preview</p><h1>{{ $tribute->title }}</h1><p>Submitted {{ $tribute->created_at->format('d M Y, h:i A') }}</p></div>
    <section class="tribute-layout">
        <div class="tribute-column">
            <article class="panel"><div class="detail-grid"><div><strong>Student</strong><span>{{ $tribute->student->name }} &bull; {{ $tribute->student->studentProfile?->class_name }} {{ $tribute->student->studentProfile?->section }}</span></div><div><strong>Teacher</strong><span>{{ $tribute->teacher->user->name }} @if($tribute->teacher->designation)&bull; {{ $tribute->teacher->designation }}@endif</span></div><div><strong>Type</strong><span>{{ $tribute->tribute_type->label() }}</span></div><div><strong>Language</strong><span>{{ $tribute->language->label() }}</span></div></div></article>
            <article class="panel"><h2>Complete tribute</h2><p class="tribute-copy">{{ $tribute->message }}</p>@include('partials.media', ['tribute' => $tribute])</article>
            @if($tribute->original_message)<article class="panel muted-panel"><h2>Original student text</h2><p>{{ $tribute->original_message }}</p></article>@endif
        </div>
        <aside class="tribute-sidebar">
            <form class="tribute-form" method="POST" action="{{ route('admin.tributes.moderate', $tribute) }}">@csrf @method('PATCH')
                <h2>Moderation decision</h2>
                <label>Title<input name="title" value="{{ old('title', $tribute->title) }}" required></label>
                <label>Message<textarea name="message" rows="10">{{ old('message', $tribute->message) }}</textarea></label>
                <label>Status<select name="status"><option value="approved" @selected($tribute->status->value === 'approved')>Approve</option><option value="rejected" @selected($tribute->status->value === 'rejected')>Reject</option></select></label>
                <label>Rejection reason<textarea name="rejection_reason" rows="3">{{ old('rejection_reason', $tribute->rejection_reason) }}</textarea></label>
                <label class="inline-check"><input type="checkbox" name="is_featured" value="1" @checked($tribute->is_featured)> Featured tribute</label>
                <button class="button primary">Save moderation</button>
            </form>
            <article class="panel danger-zone"><h2>Unsafe content</h2><p>Deletion permanently removes the tribute and its private media.</p><form method="POST" action="{{ route('admin.tributes.destroy', $tribute) }}" data-confirm="Permanently delete this tribute and every attached file?">@csrf @method('DELETE')<button class="button danger">Delete unsafe content</button></form></article>
        </aside>
    </section>
</main>
@endsection
