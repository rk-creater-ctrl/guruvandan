@extends('layouts.app')
@section('content')
<main class="content-page">
    <div class="section-heading"><p class="eyebrow">Teacher Profile</p><h1>Edit {{ $teacher->user->name }}</h1><p>{{ $teacher->tributes_count }} tributes &bull; Certificate {{ $teacher->certificate ? 'available' : 'not generated' }}</p></div>
    <section class="management-layout">
        <form class="tribute-form" method="POST" action="{{ route('admin.teachers.update', $teacher) }}" enctype="multipart/form-data" data-upload-form>
            @csrf @method('PUT')
            <div class="form-grid two">
                <label>Name<input name="name" value="{{ old('name', $teacher->user->name) }}" required></label>
                @if(auth()->user()->isSuperAdmin())
                    <label>Username<input name="username" value="{{ old('username', $teacher->user->username) }}" pattern="[A-Za-z0-9._-]+" required></label>
                @endif
                <label>Optional email<input type="email" name="email" value="{{ old('email', $teacher->user->email) }}"></label>
                <label>Phone<input name="phone" value="{{ old('phone', $teacher->user->phone) }}"></label>
                <label>Slug<input name="slug" value="{{ old('slug', $teacher->slug) }}" required></label>
                <label>Designation<input name="designation" value="{{ old('designation', $teacher->designation) }}"></label>
                <label>Floor or location<input name="location" value="{{ old('location', $teacher->location) }}"></label>
                <label>Qualification<input name="qualification" value="{{ old('qualification', $teacher->qualification) }}"></label>
                <label>Years of experience<input type="number" min="0" max="80" name="years_experience" value="{{ old('years_experience', $teacher->years_experience) }}"></label>
                <label>Joining year<input type="number" min="1950" max="{{ date('Y') + 1 }}" name="joining_year" value="{{ old('joining_year', $teacher->joining_year) }}"></label>
            </div>
            <label>Short introduction<input name="short_intro" value="{{ old('short_intro', $teacher->short_intro) }}"></label>
            <label>Biography<textarea name="bio" rows="6">{{ old('bio', $teacher->bio) }}</textarea></label>
            <label>Quote<textarea name="quote" rows="3">{{ old('quote', $teacher->quote) }}</textarea></label>
            <div class="form-grid two">
                <label>Replace profile image<input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp" data-file-input></label>
                <label>Replace cover image<input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp"></label>
            </div>
            <div class="file-preview" data-file-preview hidden></div><button class="button ghost" type="button" data-remove-file hidden>Remove selected file</button>
            <label class="inline-check"><input type="checkbox" name="remove_cover_image" value="1"> Remove cover image</label>
            <label class="inline-check"><input type="checkbox" name="is_active" value="1" @checked($teacher->is_active)> Active profile</label>
            <label class="inline-check"><input type="checkbox" name="is_public" value="1" @checked($teacher->is_public)> Public profile visible</label>
            <button class="button primary">Save teacher</button>
        </form>
        <aside class="stack">
            <article class="panel"><h2>Public page</h2><p>Review exactly how the teacher profile and revealed tributes appear.</p><a class="button secondary" href="{{ route('teachers.show', $teacher) }}" target="_blank">Preview tribute page</a></article>
            @if(auth()->user()->isSuperAdmin())
                <article class="panel"><h2>Reset login password</h2><form class="moderation-form" method="POST" action="{{ route('admin.teachers.password', $teacher) }}">@csrf @method('PATCH')<label>New temporary password<input type="password" name="password" required></label><label>Confirm password<input type="password" name="password_confirmation" required></label><button class="button ghost">Reset password</button></form></article>
            @endif
            <article class="panel danger-zone"><h2>Delete or archive</h2><p>Teachers with tributes or certificates are archived automatically. Only unused records are permanently deleted.</p><form method="POST" action="{{ route('admin.teachers.delete', $teacher) }}" data-confirm="Delete this teacher? Historical records will cause a safe archive instead.">@csrf @method('DELETE')<button class="button danger">Delete safely</button></form></article>
        </aside>
    </section>
</main>
@endsection
