<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #2c1e15; padding: 30px; }
        .card { border: 3px solid #d7a844; padding: 40px; border-radius: 20px; text-align: center; }
        .card.floral { border-width: 8px; border-style: double; }
        .card.minimal { border: 1px solid #2c1e15; }
        h1 { font-size: 36px; margin-bottom: 12px; }
        .meta { margin-top: 24px; font-size: 14px; }
        .qr { margin: 24px auto 0; width: 140px; }
        .footer { margin-top: 26px; color: #765b43; font-size: 12px; }
        .revoked { color: #9d362b; border: 2px solid #9d362b; padding: 8px; font-weight: bold; }
        .school-logo { max-width: 80px; max-height: 80px; }
        .teacher-photo { width: 85px; height: 85px; border-radius: 50%; object-fit: cover; }
        .signature { max-width: 130px; max-height: 55px; display: block; margin: 18px auto 4px; }
    </style>
</head>
<body>
    <div class="card {{ $settings['certificate_template'] ?? 'classic' }}">
        @if(!empty($settings['school_logo_path']) && file_exists(storage_path('app/public/'.$settings['school_logo_path'])))
            <img class="school-logo" src="{{ storage_path('app/public/'.$settings['school_logo_path']) }}" alt="">
        @endif
        <p>{{ $settings['certificate_title'] ?? 'Certificate of Appreciation' }}</p>
        @if($teacher->photo_path && file_exists(storage_path('app/public/'.$teacher->photo_path)))
            <img class="teacher-photo" src="{{ storage_path('app/public/'.$teacher->photo_path) }}" alt="">
        @endif
        <h1>{{ $teacher->user->name }}</h1>
        <p>{{ $settings['certificate_text'] ?? 'With gratitude for guiding, inspiring, and shaping the lives of students.' }}</p>
        <p>{{ $settings['school_name'] ?? 'School' }}</p>
        @if(!empty($settings['principal_signature_path']) && file_exists(storage_path('app/public/'.$settings['principal_signature_path'])))
            <img class="signature" src="{{ storage_path('app/public/'.$settings['principal_signature_path']) }}" alt="">
        @endif
        <p>{{ $settings['certificate_signature_label'] ?? 'Principal' }}: {{ $settings['principal_name'] ?? 'Principal' }}</p>
        @if(!empty($settings['event_date']))<p>Guru Purnima: {{ \Carbon\Carbon::parse($settings['event_date'])->format('d F Y') }}</p>@endif
        <p>Certificate Number: {{ $certificate->certificate_number }}</p>
        <p>Verification URL: {{ $verificationUrl }}</p>
        <div class="qr">{!! $qrSvg !!}</div>
        @if($certificate->revoked_at)<p class="revoked">REVOKED</p>@endif
        <p class="footer">{{ $settings['certificate_footer'] ?? 'Presented with gratitude on Guru Purnima' }}</p>
    </div>
</body>
</html>
