@extends('layouts.app')
@section('content')
<main class="content-page">
    <div class="section-heading"><p class="eyebrow">Student Management</p><h1>Student accounts</h1><p>Manage participation, account status, class details, and safe exports.</p></div>
    <section class="management-layout">
        <form class="tribute-form sticky-form" method="POST" action="{{ route('admin.students.store') }}">
            @csrf
            <h2>Add student</h2>
            <label>Full name<input name="name" value="{{ old('name') }}" required></label>
            <label>Username / Student ID<input name="username" value="{{ old('username') }}" pattern="[A-Za-z0-9._-]+" required></label>
            <label>Optional email<input type="email" name="email" value="{{ old('email') }}"></label>
            <label>Phone<input name="phone" value="{{ old('phone') }}"></label>
            <div class="form-grid two"><label>Class<input name="class_name" value="{{ old('class_name') }}" required></label><label>Section<input name="section" value="{{ old('section') }}"></label></div>
            <label>Roll number<input name="roll_number" value="{{ old('roll_number') }}"></label>
            <label>Password<input type="password" name="password" required></label>
            <label>Confirm password<input type="password" name="password_confirmation" required></label>
            <label class="inline-check"><input type="checkbox" name="must_change_password" value="1" checked> Force password change on first login</label>
            <label class="inline-check"><input type="checkbox" name="is_active" value="1" checked> Active account</label>
            <button class="button primary">Create student</button>
        </form>
        <div>
            <form class="filter-bar student-filters" method="GET">
                <label>Search<input name="q" value="{{ request('q') }}" placeholder="Name, username, class, email"></label>
                <label>Class<select name="class"><option value="">All</option>@foreach($classes as $class)<option @selected(request('class') === $class)>{{ $class }}</option>@endforeach</select></label>
                <label>Status<select name="status"><option value="">All</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Archived</option></select></label>
                <button class="button secondary">Filter</button>
                <a class="button ghost" href="{{ route('admin.students.export', request()->query()) }}">Export CSV</a>
            </form>
            <div class="table-wrap"><table class="admin-table"><thead><tr><th>Student</th><th>Class</th><th>Tributes</th><th>Approval</th><th>Status</th><th>Actions</th></tr></thead><tbody>
            @forelse($students as $student)<tr>
                <td><strong>{{ $student->name }}</strong><small>{{ $student->username }}{{ $student->email ? ' • '.$student->email : '' }}</small></td>
                <td>{{ $student->studentProfile?->class_name }} {{ $student->studentProfile?->section }}<small>Roll {{ $student->studentProfile?->roll_number ?: '-' }}</small></td>
                <td>{{ $student->tributes_count }}</td><td><small>{{ $student->approved_tributes_count }} approved &bull; {{ $student->pending_tributes_count }} pending &bull; {{ $student->rejected_tributes_count }} rejected</small></td>
                <td><span class="status-badge {{ $student->is_active ? 'approved' : 'rejected' }}">{{ $student->is_active ? 'Active' : 'Archived' }}</span></td>
                <td class="table-actions"><a class="button secondary" href="{{ route('admin.students.edit', $student) }}">View / Edit</a><form method="POST" action="{{ route('admin.students.toggle', $student) }}" data-confirm="Change this student's account status?">@csrf @method('PATCH')<button class="button ghost">{{ $student->is_active ? 'Archive' : 'Activate' }}</button></form></td>
            </tr>@empty<tr><td colspan="6" class="empty-state">No students match these filters.</td></tr>@endforelse
            </tbody></table></div><div class="pagination-wrap">{{ $students->links() }}</div>
        </div>
    </section>
</main>
@endsection
