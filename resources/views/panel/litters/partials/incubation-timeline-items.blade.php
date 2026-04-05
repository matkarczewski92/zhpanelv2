@forelse (($timeline['items'] ?? []) as $item)
    <div class="pregnancy-progress-card">
        <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-2 mb-2">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <a href="{{ $item['show_url'] }}" class="pregnancy-progress-title">{{ $item['title'] }}</a>
                    @if (!empty($item['show_range']))
                        <span class="pregnancy-progress-range-inline">{{ $item['range_label'] }}</span>
                    @endif
                    @if (!empty($item['duration_badge']))
                        <span class="pregnancy-progress-duration">
                            <strong>{{ $item['duration_badge'] }}</strong>
                        </span>
                    @endif
                </div>
                @if (!empty($item['subtitle']))
                    <div class="text-muted small mt-1">{{ $item['subtitle'] }}</div>
                @endif
                @if (!empty($item['egg_stats_label']))
                    <div class="text-muted small mt-1">{{ $item['egg_stats_label'] }}</div>
                @endif
            </div>
        </div>

        @if (!empty($item['show_range']))
            <div class="pregnancy-progress-track-wrap">
                <div class="pregnancy-progress-track">
                    <div class="pregnancy-progress-fill" style="width: {{ $item['progress_percent'] }}%;"></div>

                    <button
                        type="button"
                        class="pregnancy-progress-marker pregnancy-progress-marker--start"
                        style="left: 0%;"
                        data-bs-toggle="tooltip"
                        data-bs-custom-class="pregnancy-tooltip"
                        data-bs-title="{{ $item['start_tooltip'] }}"
                        aria-label="{{ $item['start_tooltip'] }}"
                    >
                        <span class="pregnancy-progress-dot"></span>
                    </button>

                    @if ($item['planned_percent'] !== null)
                        <button
                            type="button"
                            class="pregnancy-progress-marker pregnancy-progress-marker--planned"
                            style="left: {{ $item['planned_percent'] }}%;"
                            data-bs-toggle="tooltip"
                            data-bs-custom-class="pregnancy-tooltip"
                            data-bs-title="{{ $item['planned_tooltip'] }}"
                            aria-label="{{ $item['planned_tooltip'] }}"
                        >
                            <span class="pregnancy-progress-dot"></span>
                        </button>
                    @endif

                    @if ($item['actual_percent'] !== null)
                        <button
                            type="button"
                            class="pregnancy-progress-marker pregnancy-progress-marker--actual"
                            style="left: {{ $item['actual_percent'] }}%;"
                            data-bs-toggle="tooltip"
                            data-bs-custom-class="pregnancy-tooltip"
                            data-bs-title="{{ $item['actual_tooltip'] }}"
                            aria-label="{{ $item['actual_tooltip'] }}"
                        >
                            <span class="pregnancy-progress-dot"></span>
                        </button>
                    @endif
                </div>
            </div>

            <div class="pregnancy-progress-events">
                <span class="pregnancy-progress-event pregnancy-progress-event--start">
                    Start inkubacji: {{ $item['start_date_label'] }}
                </span>

                <span class="pregnancy-progress-event pregnancy-progress-event--planned">
                    Plan konca inkubacji: {{ $item['planned_end_label'] }}
                </span>

                @if (!empty($item['actual_end_label']))
                    <span class="pregnancy-progress-event pregnancy-progress-event--actual">
                        Koniec inkubacji: {{ $item['actual_end_label'] }}
                    </span>
                @endif
            </div>

            <div class="text-muted small mt-2">
                {{ $item['end_delta_label'] ?? '' }}
            </div>
        @else
            <div class="text-muted small mt-2">
                Brak pelnego zakresu dat do narysowania progressbaru.
            </div>
        @endif
    </div>
@empty
    <div class="text-muted">Brak aktywnej inkubacji do wyswietlenia.</div>
@endforelse
