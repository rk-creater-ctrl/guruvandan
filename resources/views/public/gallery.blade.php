@extends('layouts.app')

@section('content')
    <main class="content-page teacher-gallery-page">
        <div class="section-heading">
            <p class="eyebrow">Teacher Gallery</p>
            <h1>Meet the gurus being honoured this Guru Purnima.</h1>
        </div>

        <form class="filter-bar" method="GET">
            <input type="text" name="q" placeholder="Search teacher name" value="{{ request('q') }}">
            <select name="designation">
                <option value="">All designations</option>
                @foreach ($designations as $designation)
                    <option value="{{ $designation }}" @selected(request('designation') === $designation)>{{ $designation }}</option>
                @endforeach
            </select>
            <select name="location">
                <option value="">All floors / locations</option>
                @foreach ($locations as $location)
                    <option value="{{ $location }}" @selected(request('location') === $location)>{{ $location }}</option>
                @endforeach
            </select>
            <button class="button secondary" type="submit">Filter Gallery</button>
        </form>

        <section class="teacher-grid">
            @foreach ($teachers as $teacher)
                <article class="teacher-card teacher-card-variant-{{ $teacher->id % 6 }}">
                    @if($teacher->photo_path)
                        <img class="teacher-avatar" src="{{ asset('storage/'.$teacher->photo_path) }}" alt="{{ $teacher->user->name }}">
                    @else
                        <div class="avatar-badge">{{ strtoupper(substr($teacher->user->name, 0, 2)) }}</div>
                    @endif
                    <div>
                        <h2>{{ $teacher->user->name }}</h2>
                        <p class="teacher-subtitle">{{ $teacher->designation ?: 'GuruVandan Teacher' }}@if($teacher->location) &bull; {{ $teacher->location }} @endif</p>
                        <p>{{ $teacher->short_intro ?: str($teacher->bio)->limit(150) }}</p>
                    </div>
                    <div class="teacher-meta">
                        <span>{{ $teacher->approved_tributes_count }} approved tributes</span>
                        <a class="button secondary" href="{{ route('teachers.show', $teacher) }}">View Tribute</a>
                    </div>
                </article>
            @endforeach
        </section>

        <div class="pagination-wrap">{{ $teachers->links('vendor.pagination.guruvandan') }}</div>
    </main>
@endsection
