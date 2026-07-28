@extends('layouts.app')
@section('content')
<main class="content-page">
    <div class="section-heading"><p class="eyebrow">Teacher Management</p><h1>Teacher directory</h1><p>Profiles with tribute and certificate status at a glance.</p></div>
    <section class="management-layout">
        @if(auth()->user()->isSuperAdmin())
            <form class="tribute-form sticky-form" method="POST" action="{{ route('admin.teachers.store') }}" enctype="multipart/form-data" data-upload-form>
                @csrf
                <h2>Add teacher account</h2>
                <label>Name<input name="name" value="{{ old('name') }}" required></label>
                <label>Username<input name="username" value="{{ old('username') }}" pattern="[A-Za-z0-9._-]+" required></label>
                <label>Optional email<input type="email" name="email" value="{{ old('email') }}"></label>
                <label>Phone<input name="phone" value="{{ old('phone') }}"></label>
                <label>Slug<input name="slug" value="{{ old('slug') }}" placeholder="seema-maam" required></label>
                <label>Designation<input name="designation" value="{{ old('designation') }}"></label>
                <label>Floor or location<input name="location" value="{{ old('location') }}"></label>
                <label>Short introduction<input name="short_intro" value="{{ old('short_intro') }}"></label>
                <label>Profile photo<input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp" data-file-input></label>
                <div class="file-preview" data-file-preview hidden></div><button class="button ghost" type="button" data-remove-file hidden>Remove selected file</button>
                <label>Temporary password<input type="password" name="password" required></label>
                <label>Confirm password<input type="password" name="password_confirmation" required></label>
                <label class="inline-check"><input type="checkbox" name="must_change_password" value="1" checked> Force password change on first login</label>
                <label class="inline-check"><input type="checkbox" name="is_active" value="1" checked> Active profile and login</label>
                <label class="inline-check"><input type="checkbox" name="is_public" value="1" checked> Public profile visible</label>
                <button class="button primary" type="submit">Create teacher</button>
            </form>
        @else
            <aside class="panel sticky-form"><h2>Teacher profiles</h2><p>Admins can edit teacher profile content. Teacher login usernames and passwords are managed by Super Admin only.</p></aside>
        @endif
        <div>
            <form class="filter-bar compact-filters" method="GET">
                <label>Search<input name="q" value="{{ request('q') }}" placeholder="Name, username, designation"></label>
                <label>Designation<select name="designation"><option value="">All</option>@foreach($designations as $designation)<option @selected(request('designation') === $designation)>{{ $designation }}</option>@endforeach</select></label>
                <label>Location<select name="location"><option value="">All</option>@foreach($locations as $location)<option @selected(request('location') === $location)>{{ $location }}</option>@endforeach</select></label>
                <label>Status<select name="status"><option value="">All</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Archived</option></select></label>
                <button class="button secondary">Filter</button>
            </form>
            <div class="table-wrap"><table class="admin-table"><thead><tr><th>Teacher</th><th>Profile</th><th>Tributes</th><th>Certificate</th><th>Status</th><th>Actions</th></tr></thead><tbody>
            @forelse ($teachers as $teacher)
                <tr>
                    <td><strong>{{ $teacher->user?->name }}</strong><small>{{ $teacher->user?->username }}</small></td>
                    <td>{{ $teacher->designation ?: 'Teacher' }}<small>{{ $teacher->location ?: 'No location set' }}</small></td>
                    <td>{{ $teacher->tributes_count }}</td>
                    <td>{{ $teacher->certificate ? ($teacher->certificate->revoked_at ? 'Revoked' : 'Generated') : 'Not generated' }}</td>
                    <td><span class="status-badge {{ $teacher->is_active ? 'approved' : 'rejected' }}">{{ $teacher->is_active ? 'Active' : 'Archived' }}</span></td>
                    <td class="table-actions">
                        <a class="button ghost" href="{{ route('teachers.show', $teacher) }}" target="_blank">Preview</a>
                        <a class="button secondary" href="{{ route('admin.teachers.edit', $teacher) }}">Edit</a>
                        <form method="POST" action="{{ route('admin.teachers.toggle', $teacher) }}" data-confirm="{{ $teacher->is_active ? 'Archive this teacher and disable login?' : 'Reactivate this teacher?' }}">@csrf @method('PATCH')<button class="button ghost">{{ $teacher->is_active ? 'Archive' : 'Activate' }}</button></form>
                    </td>
                </tr>
            @empty<tr><td colspan="6" class="empty-state">No teachers match these filters.</td></tr>@endforelse
            </tbody></table></div>
            <div class="pagination-wrap">{{ $teachers->links() }}</div>
        </div>
    </section>
</main>
@endsection
