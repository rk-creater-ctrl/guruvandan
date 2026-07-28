@extends('layouts.app')

@section('content')
    <main class="content-page">
        <div class="section-heading">
            <p class="eyebrow">Admin Dashboard</p>
            <h1>Moderation, participation tracking, and reveal controls.</h1>
        </div>

        <section class="admin-grid">
            <article class="panel">
                <h2>Quick Actions</h2>
                <div class="action-stack">
                    <a class="button secondary" href="{{ route('admin.tributes') }}">Review Tributes</a>
                    <a class="button ghost" href="{{ route('admin.event') }}">Manage Event</a>
                    <form method="POST" action="{{ route('admin.certificates.generate') }}">
                        @csrf
                        <button class="button primary" type="submit">Generate Certificates</button>
                    </form>
                </div>
            </article>
            <article class="panel">
                <h2>Participation Statistics</h2>
                <div class="stats-list">
                    <div><strong>{{ $stats['teachers'] }}</strong><span>Total teachers</span></div>
                    <div><strong>{{ $stats['students'] }}</strong><span>Total students</span></div>
                    <div><strong>{{ $stats['tributes'] }}</strong><span>Total tributes</span></div>
                    <div><strong>{{ $stats['pending'] }}</strong><span>Pending submissions</span></div>
                    <div><strong>{{ $stats['approved'] }}</strong><span>Approved submissions</span></div>
                    <div><strong>{{ $stats['rejected'] }}</strong><span>Rejected submissions</span></div>
                </div>
            </article>
            <article class="panel wide">
                <h2>Recent Moderation Queue</h2>
                <table class="admin-table">
                    <thead>
                        <tr><th>Student</th><th>Teacher</th><th>Type</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($recent as $tribute)
                            <tr>
                                <td>{{ $tribute->student->name }}</td>
                                <td>{{ $tribute->teacher->user->name }}</td>
                                <td>{{ $tribute->tribute_type->label() }}</td>
                                <td>{{ ucfirst($tribute->status->value) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </article>
        </section>
    </main>
@endsection

