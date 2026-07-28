@extends('layouts.app')
@section('content')
@php
    $ritikTribute = $showContent
        ? $tributes->first(fn ($tribute) => $tribute->student?->name === 'Ritik Kushwaha')
        : null;
@endphp
<main class="content-page">
    <div class="dashboard-hero"><div><p class="eyebrow">Teacher Dashboard</p><h1>{{ $teacher->user->name }}'s tribute space</h1><p>{{ $teacher->designation }}@if($teacher->location) &bull; {{ $teacher->location }}@endif</p><a class="button ghost" href="{{ route('teacher.profile.edit') }}">Edit my profile</a></div><div class="reveal-chip {{ $showContent ? 'live' : '' }}"><span>{{ $showContent ? 'Tributes revealed' : 'Surprise mode active' }}</span><strong>{{ $showContent ? $tributes->count().' approved tributes' : 'Your wall remains private until reveal.' }}</strong></div></div>
    @if($ritikTribute)
        <dialog class="teacher-message-dialog" data-teacher-message-popup data-popup-key="teacher-message-{{ $teacher->id }}-{{ $ritikTribute->id }}">
            <div class="teacher-message-dialog__inner">
                <p class="eyebrow">A Guru Purnima Thought</p>
                <h2>{{ $ritikTribute->title }}</h2>
                <blockquote>{{ $ritikTribute->message }}</blockquote>
                <p class="teacher-message-dialog__sender">From {{ $ritikTribute->student->name }}</p>
                <div class="form-actions">
                    <button class="button primary" type="button" data-close-dialog>Read my tributes</button>
                </div>
            </div>
        </dialog>
    @endif
    <section class="dashboard-stats">
        <article><span>Total</span><strong>{{ $tributes->count() }}</strong></article><article><span>Messages</span><strong>{{ $counts['thank_you_message'] }}</strong></article><article><span>Poems</span><strong>{{ $counts['poem'] }}</strong></article><article><span>Letters</span><strong>{{ $counts['letter'] }}</strong></article><article><span>Photos</span><strong>{{ $counts['photo_memory'] + $counts['drawing'] + $counts['greeting_card'] }}</strong></article><article><span>Audio</span><strong>{{ $counts['audio_message'] }}</strong></article><article><span>Video</span><strong>{{ $counts['video_wish'] }}</strong></article>
    </section>
    @if(!$showContent)
        <article class="panel empty-state"><h2>A meaningful surprise is being prepared.</h2><p>Approved messages and media will appear here when the celebration is revealed.</p></article>
    @else
    <section class="tribute-layout">
        <div class="tribute-column">
            @if($featured->isNotEmpty())<section><div class="section-heading compact"><p class="eyebrow">Featured</p><h2>Tributes selected by the celebration team</h2></div>@foreach($featured as $tribute)<article class="submission-card featured-card"><h2>{{ $tribute->title }}</h2><p>{{ $tribute->message }}</p>@include('partials.media',['tribute'=>$tribute])<small>{{ $tribute->student->name }}</small></article>@endforeach</section>@endif
            <section><div class="section-heading compact"><p class="eyebrow">Recent</p><h2>Approved tributes</h2></div>@forelse($tributes as $tribute)<article class="submission-card"><div class="submission-head"><h2>{{ $tribute->title }}</h2><span class="pill">{{ $tribute->tribute_type->label() }}</span></div><p>{{ $tribute->message }}</p>@include('partials.media',['tribute'=>$tribute])<small>{{ $tribute->student->name }} &bull; {{ $tribute->student->studentProfile?->class_name }}</small></article>@empty<p class="empty-state">No approved tributes yet.</p>@endforelse</section>
        </div>
        <aside class="tribute-sidebar stack">
            <article class="panel"><h2>Reply with gratitude</h2><form class="moderation-form" method="POST" action="{{ route('teacher.reply.save') }}">@csrf<label>Thank-you message<textarea name="message" rows="6" required>{{ old('message', $teacher->reply?->message) }}</textarea></label><button class="button primary">Save reply</button></form></article>
            <article class="panel"><h2>Share your tribute page</h2><div class="qr-svg">{!! $qrSvg !!}</div><div class="form-actions"><a class="button secondary" href="{{ route('teachers.qr.download',$teacher) }}">Download QR</a><a class="button ghost" href="{{ route('teachers.qr.print',$teacher) }}" target="_blank">Print</a><button class="button ghost" type="button" data-share-url="{{ route('teachers.show',$teacher) }}">Share</button></div></article>
            <article class="panel"><h2>Certificate</h2>@if($teacher->certificate?->revoked_at)<p class="rejection-note">This certificate was revoked. Contact an administrator.</p>@else<a class="button secondary" href="{{ route('teacher.certificate.download') }}">Download PDF certificate</a>@endif</article>
            @if($event)<article class="panel"><h2>{{ $event->title }}</h2><p>{{ $event->event_date->format('d M Y') }} &bull; {{ \Carbon\Carbon::parse($event->event_time ?: '09:00')->format('h:i A') }}</p><p>{{ $event->venue }}</p><a href="{{ route('event') }}">View event schedule</a></article>@endif
        </aside>
    </section>
    @endif
</main>
@endsection
