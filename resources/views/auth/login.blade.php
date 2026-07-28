@extends('layouts.app')

@php
    $portal = $portal ?? 'admin';
    $config = [
        'student' => ['title' => 'Student Login', 'eyebrow' => 'Student Portal', 'route' => route('student.login.submit'), 'field' => 'Username or Student ID', 'name' => 'username', 'type' => 'text', 'hint' => 'Use the username or student ID assigned by the school.'],
        'teacher' => ['title' => 'Teacher Login', 'eyebrow' => 'Guru Portal', 'route' => route('teacher.login.submit'), 'field' => 'Username', 'name' => 'username', 'type' => 'text', 'hint' => 'Teachers sign in with their unique username, not email.'],
        'admin' => ['title' => 'Admin Login', 'eyebrow' => 'Administration', 'route' => route('admin.login.submit'), 'field' => 'Email', 'name' => 'email', 'type' => 'email', 'hint' => 'Admins and Super Admins use the administration portal.'],
    ][$portal];
@endphp

@section('content')
    <main class="content-page narrow-page">
        <section class="login-panel">
            <div class="login-art">
                <span class="brand-mark">GV</span>
                <p class="eyebrow">{{ $config['eyebrow'] }}</p>
                <h1>{{ $config['title'] }}</h1>
                <p>{{ $config['hint'] }}</p>
            </div>
            <form class="tribute-form" method="POST" action="{{ $config['route'] }}">
                @csrf
                <label>{{ $config['field'] }}
                    <input type="{{ $config['type'] }}" name="{{ $config['name'] }}" value="{{ old($config['name']) }}" autocomplete="{{ $portal === 'admin' ? 'email' : 'username' }}" required>
                    @error($config['name'])<span class="field-error">{{ $message }}</span>@enderror
                </label>
                <label>Password
                    <span class="password-row"><input type="password" name="password" autocomplete="current-password" required data-password-input><button class="button ghost" type="button" data-toggle-password>Show</button></span>
                    @error('password')<span class="field-error">{{ $message }}</span>@enderror
                </label>
                <label class="inline-check"><input type="checkbox" name="remember" value="1"> Remember me</label>
                <button class="button primary" type="submit">Login</button>
                <p class="form-help">Forgot password? Please contact the school office or Super Admin for a secure reset.</p>
                <div class="form-actions">
                    <a class="button ghost" href="{{ route('home') }}">Back to website</a>
                    @if($portal !== 'student')<a class="button ghost" href="{{ route('student.login') }}">Student Login</a>@endif
                    @if($portal !== 'teacher')<a class="button ghost" href="{{ route('teacher.login') }}">Teacher Login</a>@endif
                    @if($portal !== 'admin')<a class="button ghost" href="{{ route('admin.login') }}">Admin Login</a>@endif
                </div>
            </form>
        </section>
    </main>
@endsection
