@foreach ($tribute->media as $media)
    <figure class="media-preview">
        @if ($media->media_type === 'image')
            <button class="media-lightbox-trigger" type="button" data-lightbox-src="{{ route('media.show', $media) }}" aria-label="Open {{ $media->original_name }} full size">
                <img src="{{ route('media.show', $media) }}" alt="{{ $tribute->title }}" loading="lazy">
            </button>
        @elseif ($media->media_type === 'audio')
            <audio controls preload="metadata">
                <source src="{{ route('media.show', $media) }}" type="{{ $media->mime_type }}">
                Your browser cannot play this audio file. <a href="{{ route('media.show', $media) }}">Open the file</a>.
            </audio>
        @elseif ($media->media_type === 'video')
            <video controls preload="metadata" playsinline>
                <source src="{{ route('media.show', $media) }}" type="{{ $media->mime_type }}">
                Your browser cannot play this video. <a href="{{ route('media.show', $media) }}">Open the file</a>.
            </video>
        @endif
        <figcaption>{{ $media->original_name }} &bull; {{ number_format($media->size / 1024, 0) }} KB</figcaption>
    </figure>
@endforeach
