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
    </style>
@endpush

@section('content')
    @php
        $activeTab = request()->query('tab', 'planning');
        if (!in_array($activeTab, ['planning', 'plans', 'offspring'], true)) {
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
