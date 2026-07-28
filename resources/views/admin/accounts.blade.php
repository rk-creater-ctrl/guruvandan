@extends('layouts.app')
@section('content')
<main class="content-page">
    <div class="section-heading"><p class="eyebrow">Super Admin</p><h1>Administrator accounts</h1><p>Create and manage trusted platform administrators without weakening Super Admin safeguards.</p></div>
    <section class="management-layout">
        <form class="tribute-form sticky-form" method="POST" action="{{ route('super-admin.admins.store') }}">
            @csrf
            <h2>Create administrator</h2>
            <label>Full name<input name="name" value="{{ old('name') }}" required></label>
            <label>Email<input type="email" name="email" value="{{ old('email') }}" required></label>
            <label>Phone<input name="phone" value="{{ old('phone') }}"></label>
            <label>Role<select name="role"><option value="admin">Admin</option><option value="super_admin">Super Admin</option></select></label>
            <label>Password<input type="password" name="password" required></label>
            <label>Confirm password<input type="password" name="password_confirmation" required></label>
            <label class="inline-check"><input type="checkbox" name="is_active" value="1" checked> Active account</label>
            <button class="button primary" type="submit">Create account</button>
        </form>
        <div>
            <form class="filter-bar compact-filters" method="GET">
                <label>Search<input name="q" value="{{ request('q') }}" placeholder="Name or email"></label>
                <label>Status<select name="status"><option value="">All</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></label>
                <button class="button secondary" type="submit">Filter</button>
            </form>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead><tr><th>Administrator</th><th>Role</th><th>Status</th><th>Activity</th><th>Actions</th></tr></thead>
                    <tbody>
                    @forelse ($accounts as $account)
                        <tr>
                            <td><strong>{{ $account->name }}</strong><small>{{ $account->email }}</small></td>
                            <td>{{ $account->display_role }}</td>
                            <td><span class="status-badge {{ $account->is_active ? 'approved' : 'rejected' }}">{{ $account->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td><a href="{{ route('super-admin.admins.activity', $account) }}">{{ $account->activity_logs_count }} events</a></td>
                            <td class="table-actions">
                                <details><summary class="button ghost">Edit</summary>
                                    <form class="inline-editor" method="POST" action="{{ route('super-admin.admins.update', $account) }}">@csrf @method('PUT')
                                        <input name="name" value="{{ $account->name }}" aria-label="Name" required>
                                        <input type="email" name="email" value="{{ $account->email }}" aria-label="Email" required>
                                        <input name="phone" value="{{ $account->phone }}" aria-label="Phone">
                                        <select name="role" aria-label="Role"><option value="admin" @selected($account->role->value === 'admin')>Admin</option><option value="super_admin" @selected($account->role->value === 'super_admin')>Super Admin</option></select>
                                        <label class="inline-check"><input type="checkbox" name="is_active" value="1" @checked($account->is_active)> Active</label>
                                        <button class="button secondary">Save</button>
                                    </form>
                                </details>
                                <details><summary class="button ghost">Reset password</summary><form class="inline-editor" method="POST" action="{{ route('super-admin.admins.password', $account) }}">@csrf @method('PATCH')<input type="password" name="password" placeholder="New password" required aria-label="New password"><input type="password" name="password_confirmation" placeholder="Confirm password" required aria-label="Confirm password"><button class="button secondary">Reset</button></form></details>
                                @if (! auth()->user()->is($account))
                                    <form method="POST" action="{{ route('super-admin.admins.toggle', $account) }}" data-confirm="Change this administrator's account status?">@csrf @method('PATCH')<button class="button ghost">{{ $account->is_active ? 'Deactivate' : 'Activate' }}</button></form>
                                    <form method="POST" action="{{ route('super-admin.admins.destroy', $account) }}" data-confirm="Permanently delete this administrator account?">@csrf @method('DELETE')<button class="button danger">Delete</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-state">No administrator accounts match these filters.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pagination-wrap">{{ $accounts->links() }}</div>
        </div>
    </section>
</main>
@endsection
