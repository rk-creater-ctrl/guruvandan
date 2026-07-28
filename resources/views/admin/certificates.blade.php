@extends('layouts.app')
@section('content')
<main class="content-page">
    <div class="section-heading"><p class="eyebrow">Certificates</p><h1>Appreciation certificates</h1><p>Generate, preview, download, regenerate, revoke, and verify teacher certificates.</p></div>
    <div class="table-wrap"><table class="admin-table"><thead><tr><th>Teacher</th><th>Certificate</th><th>Status</th><th>Issued</th><th>Actions</th></tr></thead><tbody>
    @foreach($teachers as $teacher)<tr>
        <td><strong>{{ $teacher->user->name }}</strong><small>{{ $teacher->designation }}</small></td>
        <td>{{ $teacher->certificate?->certificate_number ?: 'Not generated' }}</td>
        <td><span class="status-badge {{ !$teacher->certificate ? 'pending' : ($teacher->certificate->revoked_at ? 'rejected' : 'approved') }}">{{ !$teacher->certificate ? 'Not generated' : ($teacher->certificate->revoked_at ? 'Revoked' : 'Valid') }}</span></td>
        <td>{{ optional($teacher->certificate?->generated_at)->format('d M Y') ?: '-' }}</td>
        <td class="table-actions">
            @if(!$teacher->certificate)<form method="POST" action="{{ route('admin.certificates.create', $teacher) }}">@csrf<button class="button primary">Generate</button></form>
            @else
                <a class="button ghost" href="{{ route('admin.certificates.preview', $teacher) }}" target="_blank">Preview / Print</a>
                <a class="button secondary" href="{{ route('admin.certificates.download', $teacher) }}">Download</a>
                <a class="button ghost" href="{{ route('admin.certificates.qr', $teacher->certificate) }}">QR SVG</a>
                <form method="POST" action="{{ route('admin.certificates.regenerate', $teacher) }}" data-confirm="Regenerate this certificate number while preserving its verification URL?">@csrf<button class="button ghost">Regenerate</button></form>
                @if(!$teacher->certificate->revoked_at)<form class="inline-revoke" method="POST" action="{{ route('admin.certificates.revoke', $teacher->certificate) }}" data-confirm="Revoke this certificate? Its public verification page will show revoked.">@csrf @method('PATCH')<input name="reason" placeholder="Revocation reason" required><button class="button danger">Revoke</button></form>@endif
                <a class="button ghost" href="{{ route('certificates.verify', $teacher->certificate->verification_token) }}" target="_blank">Verify</a>
            @endif
        </td>
    </tr>@endforeach
    </tbody></table></div><div class="pagination-wrap">{{ $teachers->links() }}</div>
</main>
@endsection
