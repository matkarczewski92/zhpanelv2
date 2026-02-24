@extends('layouts.panel')

@section('title', 'Planowanie miotow')

@push('styles')
    <style>
        #litterPlanningSummaryModal .modal-dialog {
            max-height: calc(100vh - 2rem);
        }

        #litterPlanningSummaryModal .modal-content {
            max-height: calc(100vh - 2rem);
        }

        #litterPlanningSummaryModal .modal-body {
            overflow-y: auto;
            max-height: calc(100vh - 15rem);
        }

        #litterPlanningSummaryModal .modal-footer {
            position: sticky;
            bottom: 0;
            background: rgba(33, 37, 41, 0.98);
            z-index: 2;
        }

        #littersPlanningApp .connections-suggestions {
            top: calc(100% + 4px);
            left: 0;
            z-index: 1050;
            max-height: min(60vh, 360px);
            overflow-y: auto;
            padding: 0.25rem;
            border-radius: 0.5rem;
            border: 1px solid rgba(255, 255, 255, 0.18);
            background: rgba(20, 24, 31, 0.98);
            box-shadow: 0 16px 30px rgba(0, 0, 0, 0.35);
        }

        #littersPlanningApp .connections-autocomplete-host {
            overflow: visible;
        }

        #littersPlanningApp .connections-autocomplete-host .card-body {
            overflow: visible;
        }

        #littersPlanningApp .connections-suggestion-item {
            border: 0;
            border-radius: 0.4rem;
            margin: 0.1rem 0;
            text-align: left;
            color: #e9ecef;
            background: transparent;
            font-size: 0.92rem;
            line-height: 1.2rem;
        }

        #littersPlanningApp .connections-suggestion-item:hover,
        #littersPlanningApp .connections-suggestion-item:focus {
            color: #fff;
            background: rgba(13, 110, 253, 0.28);
            box-shadow: none;
        }

        #littersPlanningApp .connections-matched-box {
            padding: 0.5rem 0.65rem;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 0.55rem;
            background: rgba(255, 255, 255, 0.04);
        }

        #littersPlanningApp .connections-matched-count {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem 0.5rem;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: #9ec5fe;
            background: rgba(13, 110, 253, 0.22);
            border: 1px solid rgba(13, 110, 253, 0.45);
            border-radius: 999px;
        }

        #littersPlanningApp .connections-matched-row + .connections-matched-row {
            padding-top: 0.35rem;
            border-top: 1px dashed rgba(255, 255, 255, 0.15);
        }

        #littersPlanningApp .roadmap-step-realized {
            border-color: rgba(25, 135, 84, 0.65);
            background: rgba(25, 135, 84, 0.14);
        }

        #littersPlanningApp .roadmap-step-realized .roadmap-step-title {
            color: #8ef0ba;
        }

        #littersPlanningApp .roadmap-step-realized .connections-matched-count {
            color: #b8f7d7;
            background: rgba(25, 135, 84, 0.26);
            border-color: rgba(25, 135, 84, 0.58);
        }

        #littersPlanningApp .roadmap-step-status-form {
            margin: 0;
        }

        #littersPlanningApp .roadmaps-actions {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: nowrap;
        }

        #littersPlanningApp .roadmaps-action-form {
            display: inline-flex;
            align-items: center;
            margin: 0;
        }

        #littersPlanningApp .roadmaps-action-details {
            display: inline-flex;
            align-items: center;
            margin: 0;
        }

        #littersPlanningApp .roadmaps-action-summary {
            list-style: none;
            display: inline-flex;
            align-items: center;
            margin: 0;
        }

        #littersPlanningApp .roadmaps-action-summary::-webkit-details-marker {
            display: none;
        }

        #littersPlanningApp .roadmap-keeper-row td {
            background: rgba(255, 193, 7, 0.16);
            border-top-color: rgba(255, 193, 7, 0.35);
            border-bottom-color: rgba(255, 193, 7, 0.35);
        }

        #littersPlanningApp .roadmap-target-row td {
            box-shadow: inset 0 0 0 1px rgba(25, 135, 84, 0.55);
        }
    </style>
@endpush

@section('content')
    @php
        $activeTab = request()->query('tab', 'planning');
        if (!in_array($activeTab, ['planning', 'plans', 'offspring', 'possible-connections', 'connections', 'roadmap', 'roadmaps', 'roadmap-keepers'], true)) {
            $activeTab = 'planning';
        }
    @endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Planowanie miotow</h1>
            <p class="text-muted mb-0">Planowanie laczen, opracowane plany i mozliwe potomstwo z sezonu.</p>
        </div>
    </div>

    <div
        id="littersPlanningApp"
        class="d-flex flex-column gap-3"
        data-active-tab="{{ $activeTab }}"
        data-female-preview-url="{{ route('panel.litters-planning.female-preview', [], false) }}"
        data-summary-url="{{ route('panel.litters-planning.summary', [], false) }}"
    >
        <div class="glass-card">
            <div class="card-body pb-2">
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-light" data-tab-target="planning">Planowanie laczen</button>
                    <button type="button" class="btn btn-sm btn-outline-light" data-tab-target="plans">Opracowane plany</button>
                    <button type="button" class="btn btn-sm btn-outline-light" data-tab-target="offspring">Mozliwe potomstwo</button>
                    <button type="button" class="btn btn-sm btn-outline-light" data-tab-target="possible-connections">Mozliwe polaczenia</button>
                    <button type="button" class="btn btn-sm btn-outline-light" data-tab-target="connections">Wyszukiwarka polaczen</button>
                    <button type="button" class="btn btn-sm btn-outline-light" data-tab-target="roadmap">Roadmap</button>
                    <button type="button" class="btn btn-sm btn-outline-light" data-tab-target="roadmaps">Zapisane roadmapy</button>
                    <button type="button" class="btn btn-sm btn-outline-light" data-tab-target="roadmap-keepers">Do zostawienia</button>
                </div>
            </div>
        </div>

        <section data-tab-id="planning" class="@if($activeTab !== 'planning') d-none @endif">
            @include('panel.litters-planning._tab_planning', ['page' => $page])
        </section>

        <section data-tab-id="plans" class="@if($activeTab !== 'plans') d-none @endif">
            @include('panel.litters-planning._tab_plans', ['page' => $page])
        </section>

        <section data-tab-id="offspring" class="@if($activeTab !== 'offspring') d-none @endif">
            @include('panel.litters-planning._tab_offspring', ['page' => $page])
        </section>

        <section data-tab-id="possible-connections" class="@if($activeTab !== 'possible-connections') d-none @endif">
            @include('panel.litters-planning._tab_possible_connections', ['page' => $page])
        </section>

        <section data-tab-id="connections" class="@if($activeTab !== 'connections') d-none @endif">
            @include('panel.litters-planning._tab_connections', ['page' => $page])
        </section>

        <section data-tab-id="roadmap" class="@if($activeTab !== 'roadmap') d-none @endif">
            @include('panel.litters-planning._tab_roadmap', ['page' => $page])
        </section>

        <section data-tab-id="roadmaps" class="@if($activeTab !== 'roadmaps') d-none @endif">
            @include('panel.litters-planning._tab_roadmaps', ['page' => $page])
        </section>

        <section data-tab-id="roadmap-keepers" class="@if($activeTab !== 'roadmap-keepers') d-none @endif">
            @include('panel.litters-planning._tab_roadmap_keepers', ['page' => $page])
        </section>
    </div>

    <div
        class="modal fade"
        id="litterPlanningSummaryModal"
        tabindex="-1"
        aria-hidden="true"
        @if($errors->getBag('litterPlanningStore')->any()) data-open-on-load="1" @endif
    >
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content bg-dark text-light border-0">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Podsumowanie wybranych polaczen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                </div>

                <form method="POST" action="{{ route('panel.litters-planning.store') }}" id="litterPlanningSummaryForm" class="d-flex flex-column h-100">
                    @csrf
                    <input type="hidden" name="plan_id" value="{{ old('plan_id') }}" data-role="plan-id">
                    <input type="hidden" name="pairs_json" value="{{ old('pairs_json', '[]') }}" data-role="pairs-json-modal">

                    <div class="modal-body d-flex flex-column gap-3">
                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="summaryPlanName">Nazwa planu</label>
                                <input id="summaryPlanName" name="plan_name" class="form-control" value="{{ old('plan_name') }}" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="summaryPlanYear">Planowany rok</label>
                                <input id="summaryPlanYear" type="number" min="2000" max="2100" name="planned_year" class="form-control" value="{{ old('planned_year') }}">
                            </div>
                            <div class="col-12 col-md-2 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-danger w-100" data-action="clear-selected-pairs">Wyczysc wszystko</button>
                            </div>
                        </div>

                        <div data-role="summary-content">
                            @include('panel.litters-planning._summary_modal_body', ['summaryRows' => []])
                        </div>
                    </div>

                    <div class="modal-footer border-0">
                        <button type="submit" class="btn btn-primary">Zapisz plan</button>
                        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Zamknij</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
