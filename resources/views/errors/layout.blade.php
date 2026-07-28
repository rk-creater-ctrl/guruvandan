@extends('layouts.app')

@section('title', $code.' | '.$platformSettings['platform_name'])

@section('content')
    <main class="content-page error-page">
        <section class="error-panel" role="alert" aria-labelledby="error-title">
            <span class="error-code">Error {{ $code }}</span>
            <h1 id="error-title">{{ $title }}</h1>
            <p>{{ $message }}</p>
            <div class="form-actions" style="justify-content:center;margin-top:24px">
                <a class="button primary" href="{{ route('home') }}">Go Home</a>
                <a class="button ghost" href="{{ route('teachers.index') }}">Explore Tributes</a>
            </div>
        </section>
    </main>
@endsection
