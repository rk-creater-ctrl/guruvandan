@extends('layouts.app')

@section('content')
    <main class="content-page">
        <div class="section-heading">
            <p class="eyebrow">Teacher Profile</p>
            <h1>Edit your public profile.</h1>
            <p>Your username, account status, certificate, and moderation permissions are managed by the Super Admin.</p>
        </div>
        <form class="tribute-form" method="POST" action="{{ route('teacher.profile.update') }}" enctype="multipart/form-data" data-upload-form>
            @csrf @method('PUT')
            <div class="profile-preview-strip">
                <div class="cover-swatch" @if($teacher->cover_image_path) style="background-image:url('{{ asset('storage/'.$teacher->cover_image_path) }}')" @endif></div>
                @if($teacher->photo_path)
                    <img class="teacher-avatar large" src="{{ asset('storage/'.$teacher->photo_path) }}" alt="{{ $teacher->user->name }}">
                @else
                    <div class="avatar-badge large">{{ strtoupper(substr($teacher->user->name, 0, 2)) }}</div>
                @endif
            </div>
            <div class="form-grid two">
                <label>Display name<input name="name" value="{{ old('name', $teacher->user->name) }}" required></label>
                <label>Phone<input name="phone" value="{{ old('phone', $teacher->user->phone) }}"></label>
                <label>Floor or location<input name="location" value="{{ old('location', $teacher->location) }}"></label>
            </div>
            <label>Teacher profile note<textarea name="bio" rows="7">{{ old('bio', $teacher->bio) }}</textarea></label>
            <label>Guru Purnima thought or poem<textarea name="quote" rows="4">{{ old('quote', $teacher->quote) }}</textarea></label>
            <div class="form-grid two">
                <label>Replace profile picture<input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp" data-file-input></label>
                <label>Replace cover picture<input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp"></label>
            </div>
            <div class="file-preview" data-file-preview hidden></div>
            <button class="button ghost" type="button" data-remove-file hidden>Remove selected file</button>
            <label class="inline-check"><input type="checkbox" name="remove_cover_image" value="1"> Remove current cover image</label>
            <button class="button primary">Save my profile</button>
        </form>

        <section class="panel account-security-panel">
            <div>
                <p class="eyebrow">Account Security</p>
                <h2>Change your login password.</h2>
                <p class="form-help">Use a private password that is not shared with students or other staff members.</p>
            </div>
            <form class="moderation-form" method="POST" action="{{ route('teacher.profile.password') }}">
                @csrf @method('PATCH')
                <label>Current password
                    <span class="password-row">
                        <input type="password" name="current_password" autocomplete="current-password" required data-password-input>
                        <button class="button ghost" type="button" data-toggle-password>Show</button>
                    </span>
                    @error('current_password')<span class="field-error">{{ $message }}</span>@enderror
                </label>
                <div class="form-grid two">
                    <label>New password
                        <input type="password" name="password" autocomplete="new-password" required>
                        @error('password')<span class="field-error">{{ $message }}</span>@enderror
                    </label>
                    <label>Confirm new password
                        <input type="password" name="password_confirmation" autocomplete="new-password" required>
                    </label>
                </div>
                <button class="button secondary" type="submit">Update password</button>
            </form>
        </section>
    </main>
@endsection
