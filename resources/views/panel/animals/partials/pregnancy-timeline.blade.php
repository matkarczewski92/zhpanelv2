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
            @include('panel.animals.partials.pregnancy-timeline-items', [
                'timeline' => $timeline,
                'showAddButton' => true,
                'modalId' => 'pregnancyShedModal',
            ])
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
