@extends('layouts.app')

@section('content')
    @php
        $ritikTribute = $tributes->first(fn ($tribute) => $tribute->student?->name === 'Ritik Kushwaha');
        $ritikMessage = $ritikTribute
            ? str($ritikTribute->message)
                ->replace('With respect, Ritik Kushwaha.', '')
                ->replace('With respect, Ritik.', '')
                ->trim()
                ->toString()
            : null;
    @endphp
    <main class="content-page">
        <section class="teacher-hero" @if($teacher->cover_image_path) style="background-image:linear-gradient(90deg,rgba(53,36,22,.9),rgba(53,36,22,.4)),url('{{ asset('storage/'.$teacher->cover_image_path) }}')" @endif>
            <div class="teacher-hero-copy">
                <p class="eyebrow">Teacher Tribute Page</p>
                <h1>{{ $teacher->user->name }}</h1>
                <p class="teacher-subtitle">{{ $teacher->designation ?: 'Teacher' }}@if($teacher->location) • {{ $teacher->location }} @endif</p>
                <p>{{ $teacher->short_intro ?: $teacher->banner_title }}</p>
            </div>
            <div class="profile-chip">
                @if($teacher->photo_path)<img class="teacher-avatar large" src="{{ asset('storage/'.$teacher->photo_path) }}" alt="{{ $teacher->user->name }}">@else<div class="avatar-badge large">{{ strtoupper(substr($teacher->user->name, 0, 2)) }}</div>@endif
                <div>
                    <strong>{{ $teacher->user->name }}</strong>
                    <span>{{ $teacher->quote }}</span>
                </div>
            </div>
        </section>

        @if (! $showContent)
            <article class="panel">
                <h2>Tributes will be revealed on Guru Purnima.</h2>
                <p>This teacher page is in surprise reveal mode. Admins can preview approved content, but public visitors and teachers will see the full wall after reveal is enabled.</p>
            </article>
        @else
            <section class="stats-ribbon">
                <div><strong>{{ $tributes->count() }}</strong><span>approved tributes</span></div>
                <div><strong>{{ $counts['poem'] + $counts['letter'] + $counts['thank_you_message'] }}</strong><span>messages and letters</span></div>
                <div><strong>{{ $counts['audio_message'] + $counts['video_wish'] }}</strong><span>audio and video wishes</span></div>
                <div><strong>{{ $counts['drawing'] + $counts['photo_memory'] + $counts['greeting_card'] }}</strong><span>visual memories</span></div>
            </section>

            <section class="tribute-layout">
                <div class="tribute-column">
                    <article class="panel">
                        <h2>Teacher Profile</h2>
                        <p>{{ $teacher->bio }}</p>
                        @if($teacher->quote)<blockquote class="profile-thought">{{ $teacher->quote }}</blockquote>@endif
                    </article>
                    <article class="panel tribute-thought-panel">
                        <p class="eyebrow">{{ $ritikTribute ? $ritikTribute->tribute_type->label() : 'Guru Purnima Thought' }}</p>
                        <h2>{{ $ritikTribute ? $ritikTribute->title : 'A note of gratitude' }}</h2>
                        <p>{{ $ritikMessage ?: ($teacher->quote ?: 'A teacher lights the path long after the class is over.') }}</p>
                        <small>By Ritik</small>
                    </article>
                    <article class="panel">
                        <h2>Appreciation Wall</h2>
                        @forelse ($tributes as $tribute)
                            <div class="wall-entry">
                                <div class="wall-head">
                                    <strong>{{ $tribute->title }}</strong>
                                    <span>{{ $tribute->tribute_type->label() }} • {{ $tribute->language->label() }}</span>
                                </div>
                                <p>{{ $tribute->message }}</p>
                                @include('partials.media', ['tribute' => $tribute])
                                <small>{{ $tribute->student->name }}{{ $tribute->student->studentProfile ? ', ' . $tribute->student->studentProfile->class_name : '' }}</small>
                            </div>
                        @empty
                            <p>No approved tributes have been revealed yet.</p>
                        @endforelse
                    </article>
                    @if ($teacher->reply)
                        <article class="panel">
                            <h2>Teacher Reply</h2>
                            <p>{{ $teacher->reply->message }}</p>
                        </article>
                    @endif
                </div>
                <aside class="tribute-sidebar">
                    <article class="panel">
                        <h2>Shareable QR</h2>
                        <div class="qr-svg">{!! $qrSvg !!}</div>
                        <small>Scan to open {{ $teacher->user->name }}'s tribute page.</small>
                        <div class="form-actions"><a class="button ghost" href="{{ route('teachers.qr.download', $teacher) }}">Download SVG</a><a class="button ghost" href="{{ route('teachers.qr.print', $teacher) }}" target="_blank">Print QR</a></div>
                    </article>
                    @if ($teacher->certificate)
                        <article class="panel">
                            <h2>Digital Certificate</h2>
                            <p>Certificate ID: {{ $teacher->certificate->certificate_number }}</p>
                            <a class="button secondary" href="{{ route('certificates.verify', $teacher->certificate->verification_token) }}">Verify Certificate</a>
                        </article>
                    @endif
                    <article class="panel">
                        <h2>Media Highlights</h2>
                        <div class="media-tags">
                            @foreach ($tributes as $tribute)
                                <span>{{ $tribute->tribute_type->label() }}</span>
                            @endforeach
                        </div>
                    </article>
                </aside>
            </section>
        @endif
    </main>
@endsection
