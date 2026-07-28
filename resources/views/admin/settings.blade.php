@extends('layouts.app')
@section('content')
<main class="content-page">
    <div class="section-heading"><p class="eyebrow">Super Admin Settings</p><h1>Platform configuration</h1><p>School identity, celebration, certificates, AI behavior, reveal timing, and upload safety limits.</p></div>
    <form class="settings-form" method="POST" action="{{ route('super-admin.settings.save') }}" enctype="multipart/form-data">@csrf
        <section class="settings-section"><div><p class="eyebrow">School</p><h2>School identity</h2></div><div class="form-grid two">
            <label>Platform name<input name="platform_name" value="{{ old('platform_name', $settings->get('platform_name', 'GuruVandan')) }}" required></label>
            <label>Tagline<input name="platform_tagline" value="{{ old('platform_tagline', $settings->get('platform_tagline', 'Honour the teachers who shaped your journey.')) }}" required></label>
            <label>School name<input name="school_name" value="{{ old('school_name', $settings->get('school_name', 'SAVVY MOTHER INTERNATIONAL SCHOOL')) }}" required></label>
            <label>School email<input type="email" name="school_email" value="{{ old('school_email', $settings->get('school_email')) }}"></label>
            <label>School phone<input name="school_phone" value="{{ old('school_phone', $settings->get('school_phone')) }}"></label>
            <label>Principal name<input name="principal_name" value="{{ old('principal_name', $settings->get('principal_name', 'Dr. Meera Khanna')) }}" required></label>
            <label class="full">School address<textarea name="school_address" rows="3">{{ old('school_address', $settings->get('school_address')) }}</textarea></label>
            <label>School logo<input type="file" name="school_logo" accept=".jpg,.jpeg,.png,.webp"></label>
            <label>Principal signature<input type="file" name="principal_signature" accept=".jpg,.jpeg,.png,.webp"></label>
        </div></section>
        <section class="settings-section"><div><p class="eyebrow">Guru Purnima</p><h2>Celebration and reveal</h2></div><div class="form-grid two">
            <label>Event title<input name="event_title" value="{{ old('event_title', $settings->get('event_title', 'Guru Purnima Celebration')) }}" required></label>
            <label>Event date<input type="date" name="event_date" value="{{ old('event_date', $settings->get('event_date', now()->addMonth()->format('Y-m-d'))) }}" required></label>
            <label>Event time<input type="time" name="event_time" value="{{ old('event_time', $settings->get('event_time', '09:00')) }}"></label>
            <label>Venue<input name="event_venue" value="{{ old('event_venue', $settings->get('event_venue')) }}"></label>
            <label>Chief guest<input name="chief_guest" value="{{ old('chief_guest', $settings->get('chief_guest')) }}"></label>
            <label class="full">Celebration message<textarea name="celebration_message" rows="3">{{ old('celebration_message', $settings->get('celebration_message')) }}</textarea></label>
            <label class="inline-check"><input type="checkbox" name="reveal_enabled" value="1" @checked(old('reveal_enabled', $settings->get('reveal_enabled'))) > Reveal enabled now</label>
            <label>Scheduled reveal<input type="datetime-local" name="reveal_at" value="{{ old('reveal_at', $settings->get('reveal_at') ? \Carbon\Carbon::parse($settings->get('reveal_at'))->format('Y-m-d\TH:i') : '') }}"></label>
        </div></section>
        <section class="settings-section"><div><p class="eyebrow">Certificate</p><h2>Certificate wording</h2></div><div class="form-grid two">
            <label>Certificate title<input name="certificate_title" value="{{ old('certificate_title', $settings->get('certificate_title', 'Certificate of Appreciation')) }}" required></label>
            <label>Signature label<input name="certificate_signature_label" value="{{ old('certificate_signature_label', $settings->get('certificate_signature_label', 'Principal')) }}"></label>
            <label>Template style<select name="certificate_template"><option value="classic" @selected(old('certificate_template', $settings->get('certificate_template','classic')) === 'classic')>Classic gold</option><option value="floral" @selected(old('certificate_template', $settings->get('certificate_template')) === 'floral')>Floral celebration</option><option value="minimal" @selected(old('certificate_template', $settings->get('certificate_template')) === 'minimal')>Minimal formal</option></select></label>
            <label class="full">Appreciation text<textarea name="certificate_text" rows="4" required>{{ old('certificate_text', $settings->get('certificate_text', 'With gratitude for guiding, inspiring, and shaping the lives of students.')) }}</textarea></label>
            <label class="full">Footer text<input name="certificate_footer" value="{{ old('certificate_footer', $settings->get('certificate_footer', 'Presented with gratitude on Guru Purnima')) }}"></label>
        </div></section>
        <section class="settings-section"><div><p class="eyebrow">AI Assistant</p><h2>Provider health</h2><p>The API key remains only in <code>.env</code>.</p></div><div class="form-grid two">
            <div class="setting-status"><strong>Provider</strong><span>{{ config('services.gemini.api_key') ? 'Gemini configured' : 'Local fallback provider' }}</span></div>
            <div class="setting-status"><strong>API key</strong><span>{{ config('services.gemini.api_key') ? 'Configured securely' : 'Not configured' }}</span></div>
            <div class="setting-status"><strong>Model</strong><span>{{ config('services.gemini.model') }}</span></div>
            <label>Requests per minute<input type="number" name="ai_rate_limit" min="1" max="60" value="{{ old('ai_rate_limit', $settings->get('ai_rate_limit', 10)) }}" required></label>
            <label class="inline-check"><input type="checkbox" name="ai_enabled" value="1" @checked(old('ai_enabled', $settings->get('ai_enabled', true)))> AI assistant enabled</label>
            <label class="inline-check"><input type="checkbox" name="ai_fallback_enabled" value="1" @checked(old('ai_fallback_enabled', $settings->get('ai_fallback_enabled', true)))> Local fallback enabled</label>
        </div></section>
        @php($allowedUploads = old('upload_allowed_types', json_decode($settings->get('upload_allowed_types', '[]'), true) ?: ['jpg','jpeg','png','webp','mp3','wav','m4a','mp4','webm']))
        <section class="settings-section"><div><p class="eyebrow">Uploads</p><h2>Private media limits</h2><p>Choose from the safe allowlist. SVG and executable formats are always blocked.</p></div><div class="form-grid three">
            <label>Image limit (KB)<input type="number" name="upload_image_kb" value="{{ old('upload_image_kb', $settings->get('upload_image_kb', 5120)) }}" required></label>
            <label>Audio limit (KB)<input type="number" name="upload_audio_kb" value="{{ old('upload_audio_kb', $settings->get('upload_audio_kb', 12288)) }}" required></label>
            <label>Video limit (KB)<input type="number" name="upload_video_kb" value="{{ old('upload_video_kb', $settings->get('upload_video_kb', 51200)) }}" required></label>
            <div class="full upload-type-grid">@foreach(['jpg','jpeg','png','webp','mp3','wav','m4a','mp4','webm'] as $extension)<label class="inline-check"><input type="checkbox" name="upload_allowed_types[]" value="{{ $extension }}" @checked(in_array($extension, $allowedUploads, true))> .{{ $extension }}</label>@endforeach</div>
        </div></section>
        <div class="settings-save"><button class="button primary">Save platform settings</button></div>
    </form>
</main>
@endsection
