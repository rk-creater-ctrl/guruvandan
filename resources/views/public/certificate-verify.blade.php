@extends('layouts.app')
@section('content')
<main class="content-page">
    <section class="certificate-card {{ $certificate->revoked_at ? 'certificate-revoked' : '' }}">
        <span class="eyebrow">Certificate Verification</span>
        <div class="verification-seal {{ $certificate->revoked_at ? 'revoked' : 'valid' }}">{{ $certificate->revoked_at ? 'Revoked' : 'Valid' }}</div>
        <h1>{{ $certificate->teacher->user->name }}</h1>
        <p>{{ $certificate->revoked_at ? 'This certificate is no longer valid.' : 'This certificate is authentic and valid.' }}</p>
        <div class="certificate-meta">
            <div><strong>Certificate Number</strong><span>{{ $certificate->certificate_number }}</span></div>
            <div><strong>School</strong><span>{{ $platformSettings['school_name'] }}</span></div>
            <div><strong>Issue Date</strong><span>{{ optional($certificate->generated_at)->format('d M Y') }}</span></div>
            <div><strong>Event Date</strong><span>{{ optional($event?->event_date)->format('d M Y') ?: 'Guru Purnima' }}</span></div>
            <div><strong>Verified At</strong><span>{{ $verifiedAt->format('d M Y, h:i A') }}</span></div>
            <div><strong>Status</strong><span>{{ $certificate->revoked_at ? 'Revoked' : 'Valid' }}</span></div>
        </div>
        @if($certificate->revoked_at)<p class="rejection-note">Revoked {{ $certificate->revoked_at->format('d M Y') }}. {{ $certificate->revocation_reason }}</p>@endif
    </section>
</main>
@endsection
