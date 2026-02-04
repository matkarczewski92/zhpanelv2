@php
    $errorBag = $errors->getBag('litterPlanningStore');
@endphp

<div class="glass-card mb-3">
    <div class="card-header">
        <div class="strike"><span>Planowanie laczen</span></div>
    </div>
    <div class="card-body d-flex flex-column gap-3">
        <div class="row g-2">
            <div class="col-12 col-lg-10">
                <label class="form-label small text-muted mb-1" for="planningFemaleSelect">Samica</label>
                <select id="planningFemaleSelect" class="form-select" data-role="planning-female">
                    <option value="">-- wybierz --</option>
                    @foreach ($page->females as $female)
                        <option
                            value="{{ $female['id'] }}"
                            data-type="{{ $female['animal_type_id'] }}"
                            data-color="{{ $female['color'] }}"
                            data-weight="{{ $female['weight'] }}"
                            data-name="{{ $female['name'] }}"
                            data-display-name="{{ $female['display_name'] ?? ('(' . $female['weight'] . 'g.) ' . $female['name']) }}"
                            @if($female['is_used']) data-used="1" @endif
                            class="text-{{ $female['color'] }}"
                        >
                            @if($female['is_used'])v @endif {{ $female['display_name'] ?? ('(' . $female['weight'] . 'g.) ' . $female['name']) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-lg-2 d-flex align-items-end">
                <button type="button" class="btn btn-primary w-100" data-action="open-summary-modal">
                    Podsumowanie
                    <span class="badge text-bg-light ms-1" data-role="selected-pairs-count">0</span>
                </button>
            </div>
        </div>

        <div data-role="planning-results">
            @include('panel.litters-planning._planning_results', ['femaleId' => null, 'rows' => []])
        </div>

        @if ($errorBag->any())
            <div class="small text-danger">
                @foreach ($errorBag->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
    </div>
</div>
