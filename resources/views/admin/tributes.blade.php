@extends('layouts.app')
@section('content')
<main class="content-page">
    <div class="section-heading"><p class="eyebrow">Tribute Moderation</p><h1>Review queue</h1><p>Pending tributes appear first. Search, preview, edit, approve, reject, feature, or remove unsafe submissions.</p></div>
    <form class="filter-panel" method="GET">
        <div class="filter-grid">
            <label>Search<input name="q" value="{{ request('q') }}" placeholder="Title or message"></label>
            <label>Teacher<select name="teacher"><option value="">All</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected((string)request('teacher') === (string)$teacher->id)>{{ $teacher->user->name }}</option>@endforeach</select></label>
            <label>Student<select name="student"><option value="">All</option>@foreach($students as $student)<option value="{{ $student->id }}" @selected((string)request('student') === (string)$student->id)>{{ $student->name }}</option>@endforeach</select></label>
            <label>Class<select name="class"><option value="">All</option>@foreach($classes as $class)<option @selected(request('class') === $class)>{{ $class }}</option>@endforeach</select></label>
            <label>Type<select name="type"><option value="">All</option>@foreach(\App\Enums\TributeType::cases() as $type)<option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>@endforeach</select></label>
            <label>Language<select name="language"><option value="">All</option>@foreach(\App\Enums\TributeLanguage::cases() as $language)<option value="{{ $language->value }}" @selected(request('language') === $language->value)>{{ $language->label() }}</option>@endforeach</select></label>
            <label>Status<select name="status"><option value="">All</option>@foreach(['pending','approved','rejected'] as $status)<option @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label>
            <label>Featured<select name="featured"><option value="">All</option><option value="yes" @selected(request('featured') === 'yes')>Featured</option><option value="no" @selected(request('featured') === 'no')>Not featured</option></select></label>
            <label>From<input type="date" name="date_from" value="{{ request('date_from') }}"></label>
            <label>To<input type="date" name="date_to" value="{{ request('date_to') }}"></label>
            <label>Order<select name="sort"><option value="newest">Newest</option><option value="oldest" @selected(request('sort') === 'oldest')>Oldest</option></select></label>
        </div>
        <div class="form-actions"><button class="button secondary">Apply filters</button><a class="button ghost" href="{{ route('admin.tributes') }}">Clear</a></div>
    </form>

    <form method="POST" action="{{ route('admin.tributes.bulk') }}" id="bulk-moderation">@csrf
        <div class="bulk-bar">
            <label class="inline-check"><input type="checkbox" data-select-all> Select page</label>
            <select name="action" aria-label="Bulk action" required><option value="">Bulk action</option><option value="approve">Approve</option><option value="reject">Reject</option></select>
            <input name="rejection_reason" placeholder="Reason required for bulk rejection" aria-label="Bulk rejection reason">
            <button class="button primary">Apply to selected</button>
        </div>
        <div class="table-wrap"><table class="admin-table"><thead><tr><th></th><th>Tribute</th><th>Student</th><th>Teacher</th><th>Type</th><th>Status</th><th>Featured</th><th>Actions</th></tr></thead><tbody>
        @forelse($tributes as $tribute)<tr>
            <td><input type="checkbox" name="tribute_ids[]" value="{{ $tribute->id }}" aria-label="Select {{ $tribute->title }}"></td>
            <td><strong>{{ $tribute->title }}</strong><small>{{ str($tribute->message)->limit(90) }}</small></td>
            <td>{{ $tribute->student->name }}<small>{{ $tribute->student->studentProfile?->class_name }}</small></td>
            <td>{{ $tribute->teacher->user->name }}</td><td>{{ $tribute->tribute_type->label() }}</td>
            <td><span class="status-badge {{ $tribute->status->value }}">{{ ucfirst($tribute->status->value) }}</span></td>
            <td>{{ $tribute->is_featured ? 'Yes' : 'No' }}</td>
            <td><a class="button secondary" href="{{ route('admin.tributes.show', $tribute) }}">Open preview</a></td>
        </tr>@empty<tr><td colspan="8" class="empty-state">No tributes match these filters.</td></tr>@endforelse
        </tbody></table></div>
    </form>
    <div class="pagination-wrap">{{ $tributes->links() }}</div>
</main>
@endsection
