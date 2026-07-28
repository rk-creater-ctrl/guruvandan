@extends('layouts.app')

@section('content')
    <main class="content-page">
        <div class="section-heading">
            <p class="eyebrow">Digital Memory Wall</p>
            <h1>Approved memories from students across the school.</h1>
        </div>

        <form class="filter-bar" method="GET">
            <select name="teacher">
                <option value="">All teachers</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->slug }}" @selected(request('teacher') === $teacher->slug)>{{ $teacher->user->name }}</option>
                @endforeach
            </select>
            <input type="text" name="class" placeholder="Class filter" value="{{ request('class') }}">
            <select name="type">
                <option value="">All tribute types</option>
                @foreach (\App\Enums\TributeType::cases() as $type)
                    <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
            <select name="language">
                <option value="">All languages</option>
                @foreach (\App\Enums\TributeLanguage::cases() as $language)
                    <option value="{{ $language->value }}" @selected(request('language') === $language->value)>{{ $language->label() }}</option>
                @endforeach
            </select>
            <select name="sort">
                <option value="latest" @selected(request('sort') === 'latest')>Latest tributes</option>
                <option value="likes" @selected(request('sort') === 'likes')>Most appreciated</option>
            </select>
            <label class="inline-check"><input type="checkbox" name="featured" value="1" @checked(request('featured'))> Featured only</label>
            <button class="button secondary" type="submit">Apply Filters</button>
        </form>

        <section class="wall-grid">
            @foreach ($tributes as $tribute)
                <article class="wall-card">
                    <span class="pill">{{ $tribute->teacher->user->name }}</span>
                    <h2>{{ $tribute->title }}</h2>
                    <p>{{ $tribute->message }}</p>
                    <div class="wall-footer">
                        <small>{{ $tribute->student->name }} • {{ $tribute->student->studentProfile?->class_name }}</small>
                        <strong>{{ $tribute->likes_count }} appreciations</strong>
                    </div>
                    @auth
                        @if (auth()->user()->isStudent())
                            <form method="POST" action="{{ route('student.tributes.like', $tribute) }}">
                                @csrf
                                <button class="button ghost" type="submit">Appreciate</button>
                            </form>
                        @endif
                    @endauth
                </article>
            @endforeach
        </section>

        <div class="pagination-wrap">{{ $tributes->links() }}</div>
    </main>
@endsection

