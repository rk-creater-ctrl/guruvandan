@extends('layouts.app')

@php
    $approvedCount = \App\Models\Tribute::where('status', 'approved')->count();
    $teacherTotal = \App\Models\Teacher::where('is_active', true)->where('is_public', true)->count();
    $eventDate = $event?->event_date?->format('d M Y');
@endphp

@section('content')
    <main class="home-page">
        <section class="gv-hero">
            <div class="gv-hero__content">
                <p class="eyebrow">Guru Purnima {{ optional($event?->event_date)->format('Y') ?? now()->format('Y') }}</p>
                <h1>GuruVandan</h1>
                <p class="gv-hero__tagline">A heartfelt digital tribute to the teachers who shaped our confidence, character, and curiosity.</p>
                <div class="gv-hero__actions">
                    @auth
                        @if (auth()->user()->isStudent())
                            <a class="button primary" href="{{ route('student.dashboard') }}">Give Guru Dakshina</a>
                        @else
                            <a class="button primary" href="{{ route('dashboard') }}">Open Dashboard</a>
                        @endif
                    @else
                        <a class="button primary" href="{{ route('register') }}">Give Guru Dakshina</a>
                    @endauth
                    <a class="button secondary" href="{{ route('teachers.index') }}">View Our Gurus</a>
                </div>
                @if ($event)
                    <div class="gv-countdown" data-countdown="{{ $event->event_date->format('Y-m-d') }} {{ $event->event_time }}">
                        <span>{{ $eventDate }}{{ $event->event_time ? ' at '.substr($event->event_time, 0, 5) : '' }}</span>
                        <strong>Loading celebration timer...</strong>
                    </div>
                @endif
            </div>
            <div class="gv-hero__visual" aria-label="Guru Purnima tribute artwork">
                <div class="mandala-ring"></div>
                <div class="lamp-scene">
                    <span class="lamp-flame"></span>
                    <span class="lamp-bowl"></span>
                    <span class="lamp-base"></span>
                </div>
                <div class="hero-note note-one"><strong>{{ $teacherTotal }}</strong><span>gurus honoured</span></div>
                <div class="hero-note note-two"><strong>{{ $approvedCount }}</strong><span>approved memories</span></div>
            </div>
        </section>

        <section class="home-stat-band" aria-label="GuruVandan participation statistics">
            <article><span>Teachers</span><strong>{{ $teacherTotal }}</strong></article>
            <article><span>Approved Tributes</span><strong>{{ $approvedCount }}</strong></article>
            <article><span>Reveal Mode</span><strong>{{ $isRevealed ? 'Live' : 'Private' }}</strong></article>
            <article><span>Certificates</span><strong>QR Verified</strong></article>
        </section>

        <section class="home-split">
            <div>
                <p class="eyebrow">Why Digital Guru Dakshina</p>
                <h2>Because gratitude should feel personal, preserved, and worthy of the teacher receiving it.</h2>
            </div>
            <div class="home-meaning-copy">
                <p>GuruVandan turns student memories into a moderated, reveal-ready tribute experience. Instead of a physical gift, every student contributes something more lasting: a message, poem, drawing, photo, voice note, or video wish.</p>
                <p>Every teacher receives a dedicated tribute page, a verified certificate, a shareable QR code, and a private dashboard to read the love gathered for Guru Purnima.</p>
            </div>
        </section>

        <section class="teacher-showcase">
            <div class="section-heading">
                <p class="eyebrow">Our Gurus</p>
                <h2>Teacher tribute pages, each with its own memories, certificate, and QR.</h2>
            </div>
            <div class="teacher-spotlight-grid">
                @foreach ($teachers as $teacher)
                    <article class="teacher-spotlight teacher-card-variant-{{ $teacher->id % 6 }}">
                        @if($teacher->photo_path)
                            <img class="teacher-avatar" src="{{ asset('storage/'.$teacher->photo_path) }}" alt="{{ $teacher->user->name }}">
                        @else
                            <div class="avatar-badge">{{ strtoupper(substr($teacher->user->name, 0, 2)) }}</div>
                        @endif
                        <div>
                            <h3>{{ $teacher->user->name }}</h3>
                            <p>{{ $teacher->designation ?: 'Teacher' }}@if($teacher->location) · {{ $teacher->location }}@endif</p>
                        </div>
                        <a href="{{ route('teachers.show', $teacher) }}">View Tribute</a>
                    </article>
                @endforeach
            </div>
            <div class="center-action">
                <a class="button secondary" href="{{ route('teachers.index') }}">Explore All Teacher Tributes</a>
            </div>
        </section>

        <section class="ceremony-panel">
            <div class="ceremony-copy">
                <p class="eyebrow">Celebration</p>
                <h2>{{ $event?->title ?? 'Guru Purnima Celebration' }}</h2>
                <p>{{ $event?->description ?? 'A school-wide celebration of gratitude, mentorship, and student memories.' }}</p>
                <dl class="event-facts">
                    <div><dt>Date</dt><dd>{{ $eventDate ?: 'To be announced' }}</dd></div>
                    <div><dt>Venue</dt><dd>{{ $event?->venue ?: 'School Campus' }}</dd></div>
                    <div><dt>Chief Guest</dt><dd>{{ $event?->chief_guest ?: 'To be announced' }}</dd></div>
                </dl>
            </div>
            <div class="ceremony-timeline">
                @forelse($event?->schedules?->take(4) ?? collect() as $schedule)
                    <article>
                        <time>{{ substr((string) $schedule->start_time, 0, 5) }}</time>
                        <div><strong>{{ $schedule->title }}</strong><span>{{ $schedule->detail }}</span></div>
                    </article>
                @empty
                    <article><time>--:--</time><div><strong>Schedule coming soon</strong><span>The event timetable will appear here after it is configured.</span></div></article>
                @endforelse
            </div>
        </section>

        <section class="tribute-journal">
            <div class="journal-quote">
                <p>“A teacher’s guidance becomes a quiet voice students carry for life.”</p>
                <span>GuruVandan Memory Wall</span>
            </div>
            <div class="journal-list">
                <p class="eyebrow">Recent approved tributes</p>
                @forelse ($featuredTributes as $tribute)
                    <article>
                        <strong>{{ $tribute->title }}</strong>
                        <p>{{ str($tribute->message)->limit(150) }}</p>
                        <small>{{ $tribute->student->name }} · {{ $tribute->tribute_type->label() }}</small>
                    </article>
                @empty
                    <article>
                        <strong>Tributes are being prepared</strong>
                        <p>Approved student memories will appear here once reveal mode is active.</p>
                    </article>
                @endforelse
            </div>
        </section>

        <section class="home-cta">
            <p class="eyebrow">Offer Gratitude</p>
            <h2>Write one message your teacher will remember.</h2>
            <p>Share a thank-you note, poem, drawing, photo memory, audio wish, or short video for Guru Purnima.</p>
            <div class="gv-hero__actions">
                @auth
                    <a class="button primary" href="{{ auth()->user()->isStudent() ? route('student.dashboard') : route('dashboard') }}">Start Now</a>
                @else
                    <a class="button primary" href="{{ route('register') }}">Create Student Tribute</a>
                @endauth
                <a class="button ghost" href="{{ route('wall') }}">View Memory Wall</a>
            </div>
        </section>
    </main>
@endsection
