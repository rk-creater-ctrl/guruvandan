@extends('layouts.app')

@section('content')
    <main class="content-page narrow-page">
        <div class="section-heading">
            <p class="eyebrow">First Login</p>
            <h1>Set your own password.</h1>
            <p>Your account was created with a temporary password. Choose a private password before continuing.</p>
        </div>
        <form class="tribute-form" method="POST" action="{{ route('password.change.save') }}">
            @csrf
            <label>New password<input type="password" name="password" required autocomplete="new-password"></label>
            <label>Confirm password<input type="password" name="password_confirmation" required autocomplete="new-password"></label>
            <button class="button primary" type="submit">Update password</button>
        </form>
    </main>
@endsection
