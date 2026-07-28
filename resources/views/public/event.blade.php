@extends('layouts.app')

@php
    $eventDate = \Carbon\Carbon::parse('2026-07-29');
    $eventTime = substr($event->event_time ?: '09:00', 0, 5);
@endphp

@section('content')
    <main class="content-page event-page">
        <section class="event-intro">
            <p class="eyebrow">Guru Purnima Celebration</p>
            <h1>Honouring the gurus who guide every student forward.</h1>
            <p>Guru Purnima is a day of gratitude, respect, and remembrance. Through GuruVandan, students offer Digital Guru Dakshina with messages, poems, memories, creative wishes, and heartfelt blessings for their teachers.</p>
        </section>

        <section class="event-summary">
            <article class="event-date-card" data-countdown="{{ $eventDate->format('Y-m-d') }}T{{ $eventTime }}:00+05:30" data-countdown-end="{{ $eventDate->format('Y-m-d') }}T{{ \Carbon\Carbon::parse($eventTime)->addHours(4)->format('H:i') }}:00+05:30">
                <span>Celebration Date</span>
                <strong>29 July 2026</strong>
                <small>Countdown loading...</small>
            </article>
            <article>
                <span>Venue</span>
                <strong>School</strong>
                <small>GuruVandan tribute reveal and teacher felicitation</small>
            </article>
        </section>

        <section class="guru-meaning-panel">
            <div>
                <p class="eyebrow">Meaning of the Day</p>
                <h2>A celebration of knowledge, discipline, kindness, and guidance.</h2>
            </div>
            <p>On Guru Purnima, students remember the teachers who helped them learn, grow, and believe in themselves. This page brings together the spirit of the celebration with a simple school event, a digital memory wall, and personal tribute pages for every teacher.</p>
        </section>

        <section class="timeline">
            @forelse ($event->schedules as $item)
                <article class="timeline-item">
                    <span>{{ \Carbon\Carbon::parse($item->start_time)->format('h:i A') }}</span>
                    <div>
                        <h2>{{ $item->title }}</h2>
                        <p>{{ $item->detail }}</p>
                    </div>
                </article>
            @empty
                <article class="timeline-item">
                    <span>GuruVandan</span>
                    <div>
                        <h2>Tribute reveal and teacher blessings</h2>
                        <p>The school schedule will be shared soon.</p>
                    </div>
                </article>
            @endforelse
        </section>
    </main>
@endsection
