@extends('layouts.app')
@section('content')
<main class="content-page">
    <div class="section-heading"><p class="eyebrow">Student Profile</p><h1>{{ $student->name }}</h1><p>{{ $student->tributes_count }} total &bull; {{ $student->approved_tributes_count }} approved &bull; {{ $student->pending_tributes_count }} pending &bull; {{ $student->rejected_tributes_count }} rejected</p></div>
    <section class="management-layout">
        <form class="tribute-form" method="POST" action="{{ route('admin.students.update', $student) }}">@csrf @method('PUT')
            <label>Full name<input name="name" value="{{ old('name', $student->name) }}" required></label>
            <label>Username / Student ID<input name="username" value="{{ old('username', $student->username) }}" pattern="[A-Za-z0-9._-]+" required></label>
            <label>Optional email<input type="email" name="email" value="{{ old('email', $student->email) }}"></label>
            <label>Phone<input name="phone" value="{{ old('phone', $student->phone) }}"></label>
            <div class="form-grid two"><label>Class<input name="class_name" value="{{ old('class_name', $student->studentProfile?->class_name) }}" required></label><label>Section<input name="section" value="{{ old('section', $student->studentProfile?->section) }}"></label></div>
            <label>Roll number<input name="roll_number" value="{{ old('roll_number', $student->studentProfile?->roll_number) }}"></label>
            <label class="inline-check"><input type="checkbox" name="is_active" value="1" @checked($student->is_active)> Active account</label>
            <label class="inline-check"><input type="checkbox" name="must_change_password" value="1" @checked($student->must_change_password)> Force password change on next login</label>
            <button class="button primary">Save student</button>
        </form>
        <aside class="stack">
            <article class="panel"><h2>Reset password</h2><form class="moderation-form" method="POST" action="{{ route('admin.students.password', $student) }}">@csrf @method('PATCH')<label>New password<input type="password" name="password" required></label><label>Confirm password<input type="password" name="password_confirmation" required></label><button class="button ghost">Reset password</button></form></article>
            <article class="panel danger-zone"><h2>Delete or archive</h2><p>Students with tributes are archived to preserve authorship history.</p><form method="POST" action="{{ route('admin.students.delete', $student) }}" data-confirm="Delete this student? Tribute history will trigger safe archival.">@csrf @method('DELETE')<button class="button danger">Delete safely</button></form></article>
        </aside>
    </section>
    <section class="highlight-section"><div class="section-heading"><p class="eyebrow">Submission History</p><h2>Tributes from this student</h2></div>
        <div class="table-wrap"><table class="admin-table"><thead><tr><th>Tribute</th><th>Teacher</th><th>Status</th><th>Submitted</th></tr></thead><tbody>
        @forelse($tributes as $tribute)<tr><td><a href="{{ route('admin.tributes.show', $tribute) }}">{{ $tribute->title }}</a></td><td>{{ $tribute->teacher->user->name }}</td><td>{{ ucfirst($tribute->status->value) }}</td><td>{{ $tribute->created_at->format('d M Y') }}</td></tr>@empty<tr><td colspan="4" class="empty-state">No tributes submitted.</td></tr>@endforelse
        </tbody></table></div><div class="pagination-wrap">{{ $tributes->links() }}</div>
    </section>
</main>
@endsection
