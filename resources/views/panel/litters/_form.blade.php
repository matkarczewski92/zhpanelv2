@props([
    'form',
    'action',
    'method' => 'POST',
    'submitLabel' => 'Zapisz',
    'litter' => null,
    'prefill' => [],
    'errorBag' => 'default',
])

@php
    $current = $litter ?? [];
    $errorSource = $errorBag === 'default' ? $errors : $errors->getBag($errorBag);
@endphp

<form method="POST" action="{{ $action }}" class="d-flex flex-column gap-2">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <label class="small text-muted mb-0">Rodzaj miotu</label>
    <select class="form-select form-select-sm @if($errorSource->has('category')) is-invalid @endif" name="category" required>
        @foreach ($form->categories as $category)
            <option
                value="{{ $category['value'] }}"
                @selected((string) old('category', $current['category'] ?? '') === (string) $category['value'])
            >
                {{ $category['label'] }}
            </option>
        @endforeach
    </select>

    <label class="small text-muted mb-0">Kod miotu</label>
    <input
        type="text"
        class="form-control form-control-sm @if($errorSource->has('litter_code')) is-invalid @endif"
        name="litter_code"
        value="{{ old('litter_code', $current['code'] ?? '') }}"
        required
    >

    <label class="small text-muted mb-0">Sezon</label>
    <input
        type="number"
        min="0"
        class="form-control form-control-sm @if($errorSource->has('season')) is-invalid @endif"
        name="season"
        value="{{ old('season', $current['season'] ?? '') }}"
    >

    <label class="small text-muted mb-0">Samiec</label>
    <select class="form-select form-select-sm @if($errorSource->has('parent_male')) is-invalid @endif" name="parent_male" required>
        <option value="">Wybierz samca</option>
        @foreach ($form->maleParents as $animal)
            <option
                value="{{ $animal['id'] }}"
                @selected((string) old('parent_male', $prefill['parent_male'] ?? ($current['parent_male']['id'] ?? '')) === (string) $animal['id'])
            >
                #{{ $animal['id'] }} {{ $animal['name'] }}
            </option>
        @endforeach
    </select>

    <label class="small text-muted mb-0">Samica</label>
    <select class="form-select form-select-sm @if($errorSource->has('parent_female')) is-invalid @endif" name="parent_female" required>
        <option value="">Wybierz samice</option>
        @foreach ($form->femaleParents as $animal)
            <option
                value="{{ $animal['id'] }}"
                @selected((string) old('parent_female', $prefill['parent_female'] ?? ($current['parent_female']['id'] ?? '')) === (string) $animal['id'])
            >
                #{{ $animal['id'] }} {{ $animal['name'] }}
            </option>
        @endforeach
    </select>

    <label class="small text-muted mb-0">Planowana data laczenia</label>
    <input
        type="date"
        class="form-control form-control-sm @if($errorSource->has('planned_connection_date')) is-invalid @endif"
        name="planned_connection_date"
        value="{{ old('planned_connection_date', $current['planned_connection_date'] ?? '') }}"
    >

    @if (!empty($current))
        <label class="small text-muted mb-0">Data laczenia</label>
        <input
            type="date"
            class="form-control form-control-sm @if($errorSource->has('connection_date')) is-invalid @endif"
            name="connection_date"
            value="{{ old('connection_date', $current['connection_date'] ?? '') }}"
        >

        <label class="small text-muted mb-0">Data zniosu</label>
        <input
            type="date"
            class="form-control form-control-sm @if($errorSource->has('laying_date')) is-invalid @endif"
            name="laying_date"
            value="{{ old('laying_date', $current['laying_date'] ?? '') }}"
        >

        <label class="small text-muted mb-0">Data klucia</label>
        <input
            type="date"
            class="form-control form-control-sm @if($errorSource->has('hatching_date')) is-invalid @endif"
            name="hatching_date"
            value="{{ old('hatching_date', $current['hatching_date'] ?? '') }}"
        >

        <div class="row g-2">
            <div class="col-12 col-md-4">
                <label class="small text-muted mb-0">Jaja zniesione</label>
                <input
                    type="number"
                    min="0"
                    class="form-control form-control-sm @if($errorSource->has('laying_eggs_total')) is-invalid @endif"
                    name="laying_eggs_total"
                    value="{{ old('laying_eggs_total', $current['laying_eggs_total'] ?? 0) }}"
                >
            </div>
            <div class="col-12 col-md-4">
                <label class="small text-muted mb-0">Jaja do inkubacji</label>
                <input
                    type="number"
                    min="0"
                    class="form-control form-control-sm @if($errorSource->has('laying_eggs_ok')) is-invalid @endif"
                    name="laying_eggs_ok"
                    value="{{ old('laying_eggs_ok', $current['laying_eggs_ok'] ?? 0) }}"
                >
            </div>
            <div class="col-12 col-md-4">
                <label class="small text-muted mb-0">Wyklute</label>
                <input
                    type="number"
                    min="0"
                    class="form-control form-control-sm @if($errorSource->has('hatching_eggs')) is-invalid @endif"
                    name="hatching_eggs"
                    value="{{ old('hatching_eggs', $current['hatching_eggs'] ?? 0) }}"
                >
            </div>
        </div>

    @endif

    @if ($errorSource->any())
        <div class="small text-danger">
            @foreach ($errorSource->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <button type="submit" class="btn btn-primary btn-sm">{{ $submitLabel }}</button>
</form>
