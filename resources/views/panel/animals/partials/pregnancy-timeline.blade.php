@php
    $timeline = $profile->pregnancyTimeline;
@endphp

@if (!empty($timeline['visible']))
    <div class="card cardopacity mb-3 pregnancy-timeline-card" id="pregnancyTimelineCard">
        <div class="card-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
            <div>
                <div class="fw-semibold">Rozrod / ciaza</div>
                <div class="text-muted small">{{ $timeline['selected_season_label'] ?? '' }}</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="small text-muted" for="pregnancySeasonSelect">Sezon</label>
                <select
                    id="pregnancySeasonSelect"
                    class="form-select form-select-sm pregnancy-season-select"
                    data-current-url="{{ url()->current() }}"
                >
                    @foreach ($timeline['season_options'] ?? [] as $season)
                        <option
                            value="{{ $season['key'] }}"
                            @selected(($timeline['selected_season_key'] ?? '') === $season['key'])
                        >
                            {{ $season['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="card-body">
            @forelse (($timeline['items'] ?? []) as $item)
                <div class="pregnancy-progress-card">
                    <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-2 mb-2">
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <a href="{{ $item['show_url'] }}" class="pregnancy-progress-title">{{ $item['title'] }}</a>
                                <span class="badge {{ !empty($item['is_completed']) ? 'text-bg-success' : 'text-bg-warning' }}">
                                    {{ $item['status_label'] }}
                                </span>
                            </div>
                            @if (!empty($item['subtitle']))
                                <div class="text-muted small mt-1">{{ $item['subtitle'] }}</div>
                            @endif
                        </div>
                        <button
                            type="button"
                            class="btn btn-outline-light btn-sm rounded-circle d-inline-flex align-items-center justify-content-center pregnancy-add-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#pregnancyShedModal"
                            data-pregnancy-shed-open="1"
                            data-litter-id="{{ $item['litter_id'] }}"
                            data-litter-title="{{ $item['title'] }}"
                            data-pregnancy-season="{{ $timeline['selected_season_key'] ?? '' }}"
                            aria-label="Dodaj wylinke ciazowa"
                        >
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>

                    <div class="pregnancy-progress-meta">
                        <span><strong>Laczenie:</strong> {{ $item['start_date_label'] }}</span>
                        <span><strong>Plan zniosu:</strong> {{ $item['planned_laying_label'] }}</span>
                        @if (!empty($item['actual_laying_label']))
                            <span><strong>Znios:</strong> {{ $item['actual_laying_label'] }}</span>
                        @endif
                    </div>

                    @if (!empty($item['show_range']))
                        <div class="pregnancy-progress-track-wrap">
                            <div class="pregnancy-progress-track">
                                <div class="pregnancy-progress-fill" style="width: {{ $item['progress_percent'] }}%;"></div>

                                <div class="pregnancy-progress-marker pregnancy-progress-marker--start" style="left: 0%;">
                                    <span class="pregnancy-progress-dot"></span>
                                </div>

                                @if ($item['planned_percent'] !== null)
                                    <div class="pregnancy-progress-marker pregnancy-progress-marker--planned" style="left: {{ $item['planned_percent'] }}%;">
                                        <span class="pregnancy-progress-dot"></span>
                                    </div>
                                @endif

                                @foreach ($item['sheds'] as $shed)
                                    <div class="pregnancy-progress-marker pregnancy-progress-marker--shed" style="left: {{ $shed['position_percent'] }}%;">
                                        <span class="pregnancy-progress-dot"></span>
                                    </div>
                                @endforeach

                                @if ($item['actual_percent'] !== null)
                                    <div class="pregnancy-progress-marker pregnancy-progress-marker--actual" style="left: {{ $item['actual_percent'] }}%;">
                                        <span class="pregnancy-progress-dot"></span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="pregnancy-progress-events">
                            <span class="pregnancy-progress-event pregnancy-progress-event--start">
                                Laczenie: {{ $item['start_date_label'] }}
                            </span>

                            @foreach ($item['sheds'] as $index => $shed)
                                <span class="pregnancy-progress-event pregnancy-progress-event--shed">
                                    Wylinka {{ $index + 1 }}: {{ $shed['date_label'] }}
                                </span>
                            @endforeach

                            <span class="pregnancy-progress-event pregnancy-progress-event--planned">
                                Plan zniosu: {{ $item['planned_laying_label'] }}
                            </span>

                            @if (!empty($item['actual_laying_label']))
                                <span class="pregnancy-progress-event pregnancy-progress-event--actual">
                                    Znios: {{ $item['actual_laying_label'] }}
                                </span>
                            @endif
                        </div>

                        <div class="text-muted small mt-2">
                            Zakres: {{ $item['range_label'] }}
                            @if (!empty($item['actual_extends_timeline']))
                                <span class="ms-2">Koniec osi wydluzony do realnego zniosu.</span>
                            @endif
                        </div>
                    @else
                        <div class="text-muted small mt-2">
                            Brak pelnego zakresu dat do narysowania progressbaru.
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-muted">Brak sezonow do wyswietlenia.</div>
            @endforelse
        </div>
    </div>

    <div class="modal fade" id="pregnancyShedModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content photobg">
                <div class="modal-header">
                    <h5 class="modal-title">Dodaj wylinke ciazowa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                </div>
                <form method="POST" action="{{ $timeline['store_url'] ?? route('panel.animals.pregnancy-sheds.store', $profile->animal['id']) }}">
                    @csrf
                    <input type="hidden" name="litter_id" id="pregnancyShedLitterId" value="{{ old('litter_id') }}">
                    <input type="hidden" name="pregnancy_season" id="pregnancyShedSeason" value="{{ old('pregnancy_season', $timeline['selected_season_key'] ?? '') }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <div class="text-muted small">Miot</div>
                            <div class="fw-semibold" id="pregnancyShedLitterTitle">{{ old('litter_id') ? ('Litter #' . old('litter_id')) : 'Wybierz progressbar' }}</div>
                        </div>
                        <div>
                            <label class="form-label" for="pregnancyShedDate">Data wylinki</label>
                            <input
                                type="date"
                                id="pregnancyShedDate"
                                name="shed_date"
                                value="{{ old('shed_date', now()->format('Y-m-d')) }}"
                                class="form-control @if($errors->getBag('pregnancyShed')->has('shed_date')) is-invalid @endif"
                                required
                            >
                            @if ($errors->getBag('pregnancyShed')->has('shed_date'))
                                <div class="invalid-feedback">
                                    {{ $errors->getBag('pregnancyShed')->first('shed_date') }}
                                </div>
                            @endif
                            @if ($errors->getBag('pregnancyShed')->has('litter_id'))
                                <div class="invalid-feedback d-block">
                                    {{ $errors->getBag('pregnancyShed')->first('litter_id') }}
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Anuluj</button>
                        <button type="submit" class="btn btn-primary">Dodaj</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
