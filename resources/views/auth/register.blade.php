@extends('layouts.app')

@section('content')
    <main class="content-page narrow-page">
        <div class="section-heading">
            <p class="eyebrow">Student Registration</p>
            <h1>Start your Digital Guru Dakshina.</h1>
        </div>
        <form class="tribute-form" method="POST" action="{{ route('register') }}">
            @csrf
            <label>Name
                <input type="text" name="name" value="{{ old('name') }}" required>
            </label>
            <label>Username or Student ID
                <input type="text" name="username" value="{{ old('username') }}" pattern="[A-Za-z0-9._-]+" required>
            </label>
            <label>Email
                <input type="email" name="email" value="{{ old('email') }}">
            </label>
            <label>Phone
                <input type="text" name="phone" value="{{ old('phone') }}">
            </label>
            <label>Class
                <input type="text" name="class_name" value="{{ old('class_name') }}" required>
            </label>
            <label>Section
                <input type="text" name="section" value="{{ old('section') }}">
            </label>
            <label>Password
                <input type="password" name="password" required>
            </label>
            <label>Confirm Password
                <input type="password" name="password_confirmation" required>
            </label>
            <button class="button primary" type="submit">Create Student Account</button>
        </form>
    </main>
@endsection
