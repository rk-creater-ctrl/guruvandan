@extends('layouts.app')
@section('content')
<main class="content-page">
    <div class="dashboard-hero">
        <div><p class="eyebrow">Student Dashboard</p><h1>Your gratitude, beautifully preserved.</h1><p>Create thoughtful tributes, follow moderation progress, and revise feedback with a clear history.</p></div>
        <div class="reveal-chip {{ $isRevealed ? 'live' : '' }}"><span>{{ $isRevealed ? 'Tributes revealed' : 'Surprise mode active' }}</span>@if($event)<strong data-countdown="{{ $event->event_date->format('Y-m-d') }}T{{ substr($event->event_time ?: '09:00',0,5) }}:00+05:30">Calculating countdown...</strong>@endif</div>
    </div>
    <section class="dashboard-stats">
        <article><span>Total</span><strong>{{ $stats['total'] }}</strong></article><article><span>Pending</span><strong>{{ $stats['pending'] }}</strong></article><article><span>Approved</span><strong>{{ $stats['approved'] }}</strong></article><article><span>Needs revision</span><strong>{{ $stats['rejected'] }}</strong></article>
    </section>

    <section class="form-layout" id="give-tribute">
        <form class="tribute-form" method="POST" action="{{ route('student.tributes.store') }}" enctype="multipart/form-data" data-upload-form>@csrf
            <div class="section-heading compact"><p class="eyebrow">Give Guru Dakshina</p><h2>Create a new tribute</h2></div>
            <label>Select teacher<select name="teacher_id" id="tribute-teacher" required>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" data-name="{{ $teacher->user->name }}" @selected((string)old('teacher_id') === (string)$teacher->id)>{{ $teacher->user->name }} @if($teacher->designation)&bull; {{ $teacher->designation }}@endif</option>@endforeach</select></label>
            <div class="form-grid two"><label>Tribute type<select name="tribute_type" required>@foreach($tributeTypes as $type)<option value="{{ $type->value }}" @selected(old('tribute_type') === $type->value)>{{ $type->label() }}</option>@endforeach</select></label><label>Language<select name="language" required>@foreach($languages as $language)<option value="{{ $language->value }}" @selected(old('language') === $language->value)>{{ $language->label() }}</option>@endforeach</select></label></div>
            <label>Title<input name="title" value="{{ old('title') }}" maxlength="255" required></label>
            <label>Your message<textarea id="tribute-message" name="message" rows="7" maxlength="10000">{{ old('message') }}</textarea></label>
            <div class="upload-dropzone"><label for="tribute-media">Optional photo, drawing, audio, or video</label><input id="tribute-media" type="file" name="media" accept=".jpg,.jpeg,.png,.webp,.mp3,.wav,.m4a,.mp4,.webm" data-file-input><p>SVG and executable files are not accepted. Limits are set by the school.</p><div class="file-preview" data-file-preview hidden></div><button class="button ghost" type="button" data-remove-file hidden>Remove selected file</button><progress max="100" value="0" data-upload-progress hidden></progress></div>
            <button class="button primary" type="submit">Submit for approval</button>
        </form>

        <div class="assistant-panel" id="ai-assistant">
            <div class="section-heading compact"><p class="eyebrow">AI Message Assistant</p><h2>Turn a memory into a first draft.</h2></div>
            <label>Teacher<select id="ai-teacher-select">@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" data-name="{{ $teacher->user->name }}">{{ $teacher->user->name }} @if($teacher->designation)&bull; {{ $teacher->designation }}@endif</option>@endforeach</select></label>
            <label>Teacher name<input id="ai-teacher" readonly></label>
            <label>Memorable experience<textarea id="ai-memory" rows="4" maxlength="1500" placeholder="Describe one specific moment that stayed with you."></textarea></label>
            <div class="form-grid two"><label>Language<select id="ai-language"><option value="english">English</option><option value="hindi">Hindi</option><option value="hinglish">Hinglish</option></select></label><label>Content type<select id="ai-content-type"><option value="thank_you_message">Thank-you message</option><option value="letter">Letter</option><option value="poem">Poem</option><option value="short_speech">Short speech</option><option value="guru_purnima_wish">Guru Purnima wish</option></select></label></div>
            <label>Desired length<select id="ai-length"><option value="short">Short</option><option value="medium" selected>Medium</option><option value="long">Long</option></select></label>
            <div class="assistant-actions"><button class="button secondary" id="generate-message" type="button">Generate draft</button><button class="button ghost" id="retry-message" type="button" hidden>Retry</button></div>
            <textarea class="ai-output" id="ai-output" rows="10" aria-live="polite" placeholder="Your editable draft will appear here."></textarea>
            <div class="assistant-actions"><button class="button ghost" id="copy-ai-output" type="button">Copy</button><button class="button primary" id="insert-ai-output" type="button">Insert into tribute</button></div>
            <p class="form-help">AI suggestions are drafts. Please review them and keep your message personal.</p>
        </div>
    </section>

    <section class="highlight-section" id="my-tributes">
        <div class="section-heading"><p class="eyebrow">My Tributes</p><h2>Recent submissions and review feedback</h2></div>
        <section class="submission-list">
        @forelse($tributes as $tribute)
            <article class="submission-card">
                <div class="submission-head"><div><span class="pill">{{ $tribute->teacher->user->name }}</span><h2>{{ $tribute->title }}</h2></div><span class="status-badge {{ $tribute->status->value }}">{{ ucfirst($tribute->status->value) }}</span></div>
                <p>{{ $tribute->message }}</p>@include('partials.media', ['tribute' => $tribute])
                <small>{{ $tribute->tribute_type->label() }} &bull; {{ $tribute->language->label() }} &bull; Submitted {{ $tribute->created_at->format('d M Y') }}@if($tribute->resubmitted_at) &bull; Resubmitted {{ $tribute->resubmitted_at->format('d M Y') }}@endif</small>
                @if($tribute->status->value === 'pending')
                    <details><summary class="button ghost">Edit pending tribute</summary>
                        <form class="tribute-form compact-form" method="POST" action="{{ route('student.tributes.update', $tribute) }}" enctype="multipart/form-data">@csrf @method('PUT')
                            <input type="hidden" name="teacher_id" value="{{ $tribute->teacher_id }}"><input type="hidden" name="tribute_type" value="{{ $tribute->tribute_type->value }}"><input type="hidden" name="language" value="{{ $tribute->language->value }}">
                            <label>Title<input name="title" value="{{ $tribute->title }}" required></label><label>Message<textarea name="message" rows="5">{{ $tribute->message }}</textarea></label><label>Replace media<input type="file" name="media" accept=".jpg,.jpeg,.png,.webp,.mp3,.wav,.m4a,.mp4,.webm"></label><button class="button secondary">Save changes</button>
                        </form>
                    </details>
                    <form method="POST" action="{{ route('student.tributes.destroy', $tribute) }}" data-confirm="Delete this pending tribute and its media?">@csrf @method('DELETE')<button class="button danger">Delete pending tribute</button></form>
                @elseif($tribute->status->value === 'rejected')
                    <div class="rejection-note"><strong>Moderator feedback</strong><p>{{ $tribute->rejection_reason }}</p></div>
                    <details><summary class="button secondary">Correct and resubmit</summary>
                        <form class="tribute-form compact-form" method="POST" action="{{ route('student.tributes.resubmit', $tribute) }}" enctype="multipart/form-data">@csrf @method('PUT')
                            <input type="hidden" name="teacher_id" value="{{ $tribute->teacher_id }}"><input type="hidden" name="tribute_type" value="{{ $tribute->tribute_type->value }}"><input type="hidden" name="language" value="{{ $tribute->language->value }}">
                            <label>Corrected title<input name="title" value="{{ $tribute->title }}" required></label><label>Corrected message<textarea name="message" rows="6">{{ $tribute->message }}</textarea></label><label>Replace media if needed<input type="file" name="media" accept=".jpg,.jpeg,.png,.webp,.mp3,.wav,.m4a,.mp4,.webm"></label><button class="button primary">Resubmit for review</button>
                        </form>
                    </details>
                @endif
            </article>
        @empty<div class="empty-state"><h2>Your tribute journey starts here.</h2><p>Choose a teacher above and share one meaningful memory.</p></div>@endforelse
        </section><div class="pagination-wrap">{{ $tributes->links() }}</div>
    </section>
</main>
@endsection
