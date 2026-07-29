<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $platformSettings['platform_name'] . ' | Digital Guru Dakshina')</title>
    <meta name="description" content="@yield('meta_description', 'Digital Guru Dakshina, teacher tribute pages, certificates, messages, and Guru Purnima celebration.')">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @if (env('VERCEL') && file_exists(public_path('assets/css/styles.css')))
        <style>{!! file_get_contents(public_path('assets/css/styles.css')) !!}</style>
    @else
        <link rel="stylesheet" href="{{ asset('assets/css/styles.css') }}">
    @endif
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>
    <div class="page-shell">
        @php
            $navLinkClass = fn (string $pattern): string => request()->routeIs($pattern) ? 'is-active' : '';
        @endphp
        <header class="site-header">
            <a class="brand" href="{{ route('home') }}">
                <span class="brand-mark">GV</span>
                <span><strong>{{ $platformSettings['platform_name'] }}</strong><small>{{ $platformSettings['tagline'] }}</small></span>
            </a>
            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-navigation">
                <span>Menu</span>
                <i aria-hidden="true"></i>
            </button>
            <nav class="main-nav" id="main-navigation" aria-label="Primary navigation">
                @guest
                    <a class="{{ $navLinkClass('home') }}" href="{{ route('home') }}">Home</a>
                    <a class="{{ $navLinkClass('teachers.*') }}" href="{{ route('teachers.index') }}">Teachers</a>
                    <a class="{{ $navLinkClass('wall') }}" href="{{ route('wall') }}">Memory Wall</a>
                    <a class="{{ $navLinkClass('event') }}" href="{{ route('event') }}">Event</a>
                    <a class="{{ $navLinkClass('student.login') }}" href="{{ route('student.login') }}">Student Login</a>
                    <a class="{{ $navLinkClass('teacher.login') }}" href="{{ route('teacher.login') }}">Teacher Login</a>
                    <a class="{{ $navLinkClass('admin.login') }}" href="{{ route('admin.login') }}">Admin Login</a>
                    <a class="{{ $navLinkClass('register') }}" href="{{ route('register') }}">Give Guru Dakshina</a>
                @else
                    @if (auth()->user()->isStudent())
                        <a class="{{ $navLinkClass('student.dashboard') }}" href="{{ route('student.dashboard') }}">Dashboard</a>
                        <a href="{{ route('student.dashboard') }}#give-tribute">Give Tribute</a>
                        <a href="{{ route('student.dashboard') }}#my-tributes">My Tributes</a>
                        <a href="{{ route('student.dashboard') }}#ai-assistant">AI Assistant</a>
                        <a class="{{ $navLinkClass('teachers.*') }}" href="{{ route('teachers.index') }}">Teachers</a>
                        <a class="{{ $navLinkClass('wall') }}" href="{{ route('wall') }}">Memory Wall</a>
                    @elseif (auth()->user()->isTeacher())
                        <a class="{{ $navLinkClass('teacher.dashboard') }}" href="{{ route('teacher.dashboard') }}">Dashboard</a>
                        <a class="{{ $navLinkClass('teacher.profile.*') }}" href="{{ route('teacher.profile.edit') }}">Edit Profile</a>
                        @if (auth()->user()->teacherProfile)
                            <a class="{{ $navLinkClass('teachers.show') }}" href="{{ route('teachers.show', auth()->user()->teacherProfile) }}">My Tribute Page</a>
                        @endif
                        <a class="{{ $navLinkClass('teacher.certificate.*') }}" href="{{ route('teacher.certificate.download') }}">Certificate</a>
                        <a class="{{ $navLinkClass('event') }}" href="{{ route('event') }}">Event</a>
                    @else
                        <a class="{{ $navLinkClass('admin.dashboard') }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
                        <a class="{{ $navLinkClass('admin.tributes*') }}" href="{{ route('admin.tributes') }}">Tributes</a>
                        <a class="{{ $navLinkClass('admin.teachers*') }}" href="{{ route('admin.teachers') }}">Teachers</a>
                        <a class="{{ $navLinkClass('admin.students*') }}" href="{{ route('admin.students') }}">Students</a>
                        <a class="{{ $navLinkClass('admin.event*') }}" href="{{ route('admin.event') }}">Event</a>
                        <a class="{{ $navLinkClass('admin.certificates*') }}" href="{{ route('admin.certificates') }}">Certificates</a>
                        <a class="{{ $navLinkClass('admin.activity-logs') }}" href="{{ route('admin.activity-logs') }}">Activity Logs</a>
                        @if (auth()->user()->isSuperAdmin())
                            <a class="{{ $navLinkClass('super-admin.admins*') }}" href="{{ route('super-admin.admins') }}">Admin Accounts</a>
                            <a class="{{ $navLinkClass('admin.teachers*') }}" href="{{ route('admin.teachers') }}">Teacher Accounts</a>
                            <a class="{{ $navLinkClass('super-admin.settings') }}" href="{{ route('super-admin.settings') }}">Settings</a>
                        @endif
                    @endif
                @endguest
            </nav>
            <div class="auth-actions">
                @auth
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="button ghost" type="submit">Logout</button></form>
                @else
                    <a class="button primary" href="{{ route('register') }}">Give Guru Dakshina</a>
                @endauth
            </div>
        </header>

        @include('partials.flash')
        <div id="main-content">@yield('content')</div>

        <a class="creator-badge" href="{{ route('home') }}" aria-label="Made by Ritik">
            <span class="creator-badge__spark">R</span>
            <span>
                <small>Made by</small>
                <strong>Ritik</strong>
            </span>
        </a>

        <footer class="site-footer">
            <p>{{ $platformSettings['platform_name'] }} &bull; A Digital Guru Dakshina Platform for Guru Purnima.</p>
            <p class="footer-credit">Powered by <strong>Ritik</strong></p>
            <div class="footer-links"><a href="{{ route('teachers.index') }}">Teachers</a><a href="{{ route('wall') }}">Memory Wall</a><a href="{{ route('event') }}">Event</a></div>
        </footer>
    </div>
    <script>
        window.guruVandan = {
            aiEndpoint: @json(auth()->check() && auth()->user()->isStudent() ? route('student.ai.generate') : null),
            csrfToken: @json(csrf_token()),
        };
    </script>
    @if (env('VERCEL') && file_exists(public_path('assets/js/app.js')))
        <script>{!! file_get_contents(public_path('assets/js/app.js')) !!}</script>
    @else
        <script src="{{ asset('assets/js/app.js') }}"></script>
    @endif
</body>
</html>
