@extends('layouts.app')
@section('content')
<main class="content-page">
    <div class="section-heading"><p class="eyebrow">Account Activity</p><h1>{{ $account->name }}</h1><p>{{ $account->email }}</p></div>
    <div class="table-wrap"><table class="admin-table"><thead><tr><th>Date</th><th>Action</th><th>Details</th></tr></thead><tbody>
    @forelse ($logs as $log)<tr><td>{{ $log->created_at->format('d M Y, h:i A') }}</td><td>{{ str($log->action)->replace('_', ' ')->title() }}</td><td><code>{{ json_encode($log->meta, JSON_UNESCAPED_UNICODE) }}</code></td></tr>
    @empty<tr><td colspan="3" class="empty-state">No activity recorded for this account yet.</td></tr>@endforelse
    </tbody></table></div><div class="pagination-wrap">{{ $logs->links() }}</div>
</main>
@endsection
