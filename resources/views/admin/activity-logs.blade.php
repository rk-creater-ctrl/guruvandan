@extends('layouts.app')
@section('content')
<main class="content-page">
    <div class="section-heading"><p class="eyebrow">Audit Trail</p><h1>Activity logs</h1><p>Search important administrative, account, tribute, certificate, reveal, and event actions.</p></div>
    <form class="filter-bar" method="GET"><label>Search<input name="q" value="{{ request('q') }}" placeholder="Action, user, email"></label><label>Action<select name="action"><option value="">All actions</option>@foreach($actions as $action)<option @selected(request('action') === $action)>{{ $action }}</option>@endforeach</select></label><label>From<input type="date" name="date_from" value="{{ request('date_from') }}"></label><label>To<input type="date" name="date_to" value="{{ request('date_to') }}"></label><button class="button secondary">Filter</button></form>
    <div class="table-wrap"><table class="admin-table"><thead><tr><th>When</th><th>Actor</th><th>Action</th><th>Record</th><th>Safe metadata</th></tr></thead><tbody>
    @forelse($logs as $log)<tr><td>{{ $log->created_at->format('d M Y, h:i:s A') }}</td><td>{{ $log->user?->name ?: 'System / guest' }}<small>{{ $log->user?->email }}</small></td><td>{{ str($log->action)->replace('_',' ')->title() }}</td><td>{{ class_basename($log->subject_type ?: '-') }} #{{ $log->subject_id ?: '-' }}</td><td><code>{{ str(json_encode($log->meta, JSON_UNESCAPED_UNICODE))->limit(180) }}</code></td></tr>
    @empty<tr><td colspan="5" class="empty-state">No audit activity matches these filters.</td></tr>@endforelse
    </tbody></table></div><div class="pagination-wrap">{{ $logs->links() }}</div>
</main>
@endsection
