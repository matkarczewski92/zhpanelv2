<div class="photobar photobg mb-3" id="panelPhotobar">
@if ($profile->photos['has_photos'])
    <div class="photobar-track" data-photos='@json(collect($profile->photos['items'])->values()->map(fn($p) => ["url" => $p['url'], "thumb" => $p['thumb_url'] ?? $p['url'], "alt" => $p['label'] ?? "Zdjęcie"]))'>
        @foreach ($profile->photos['items'] as $index => $photo)
            <button
                type="button"
                class="photobar-thumb {{ $photo['is_main'] ? 'is-main' : '' }}"
                data-gallery-index="{{ $index }}"
                data-gallery-full="{{ $photo['url'] }}"
                data-gallery-alt="{{ $photo['label'] }}"
            >
                <img src="{{ $photo['url'] }}" alt="{{ $photo['label'] }}" />
            </button>
        @endforeach
    </div>
@else
    <div class="photobar-empty text-muted">Brak zdjęć w galerii.</div>
@endif
</div>
