@php
    $animal = $animal ?? null;
    $isCreate = $animal === null;
    $recentLitters = '';

    if (!empty($litters)) {
        $labels = [];
        foreach ($litters->take(5) as $litter) {
            $labels[] = '#' . $litter->id . ' ' . $litter->litter_code;
        }
        $recentLitters = implode(', ', $labels);
    }
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="name">Nazwa</label>
        <input
            id="name"
            name="name"
            type="text"
            class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $animal->name ?? '') }}"
            required
        />
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="second_name">Druga nazwa</label>
        <input
            id="second_name"
            name="second_name"
            type="text"
            class="form-control @error('second_name') is-invalid @enderror"
            value="{{ old('second_name', $animal->second_name ?? '') }}"
        />
        @error('second_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="sex">Płeć</label>
        <select
            id="sex"
            name="sex"
            class="form-select @error('sex') is-invalid @enderror"
            required
        >
            @foreach ($sexOptions ?? [] as $value => $label)
                <option value="{{ $value }}" @selected((int) old('sex', $animal->sex ?? 0) === (int) $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('sex')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="date_of_birth">Data urodzenia</label>
        <x-form.date-input
            id="date_of_birth"
            name="date_of_birth"
            :value="$animal?->date_of_birth"
            :default-today="$isCreate"
            class="@error('date_of_birth') is-invalid @enderror"
            required
        />
        @error('date_of_birth')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="feed_interval">Interwał karmienia</label>
        <input
            id="feed_interval"
            name="feed_interval"
            type="number"
            class="form-control @error('feed_interval') is-invalid @enderror"
            value="{{ old('feed_interval', $animal->feed_interval ?? '') }}"
        />
        @error('feed_interval')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="animal_type_id">Typ</label>
        <select
            id="animal_type_id"
            name="animal_type_id"
            class="form-select @error('animal_type_id') is-invalid @enderror"
        >
            <option value="">--</option>
            @foreach ($types as $type)
                <option
                    value="{{ $type->id }}"
                    @selected(old('animal_type_id', $animal->animal_type_id ?? null) == $type->id)
                >
                    {{ $type->name }}
                </option>
            @endforeach
        </select>
        @error('animal_type_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="animal_category_id">Kategoria</label>
        <select
            id="animal_category_id"
            name="animal_category_id"
            class="form-select @error('animal_category_id') is-invalid @enderror"
        >
            <option value="">--</option>
            @foreach ($categories as $category)
                <option
                    value="{{ $category->id }}"
                    @selected(old('animal_category_id', $animal->animal_category_id ?? null) == $category->id)
                >
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        @error('animal_category_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="feed_id">Domyślna karma</label>
        <select
            id="feed_id"
            name="feed_id"
            class="form-select @error('feed_id') is-invalid @enderror"
        >
            <option value="">--</option>
            @foreach ($feeds as $feed)
                <option
                    value="{{ $feed->id }}"
                    @selected(old('feed_id', $animal->feed_id ?? null) == $feed->id)
                >
                    {{ $feed->name }}
                </option>
            @endforeach
        </select>
        @error('feed_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="litter_id">Miot (ID)</label>
        <input
            id="litter_id"
            name="litter_id"
            type="number"
            class="form-control @error('litter_id') is-invalid @enderror"
            value="{{ old('litter_id', $animal->litter_id ?? '') }}"
        />
        @if ($recentLitters)
            <div class="form-text">Ostatnie: {{ $recentLitters }}</div>
        @endif
        @error('litter_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="public_profile_tag">Tag profilu</label>
        <input
            id="public_profile_tag"
            name="public_profile_tag"
            type="text"
            class="form-control @error('public_profile_tag') is-invalid @enderror"
            value="{{ old('public_profile_tag', $animal->public_profile_tag ?? '') }}"
        />
        @error('public_profile_tag')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="web_gallery">Web gallery</label>
        <input
            id="web_gallery"
            name="web_gallery"
            type="number"
            class="form-control @error('web_gallery') is-invalid @enderror"
            value="{{ old('web_gallery', $animal->web_gallery ?? '') }}"
        />
        @error('web_gallery')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-12">
        <div class="form-check">
            <input type="hidden" name="public_profile" value="0">
            <input
                class="form-check-input"
                type="checkbox"
                id="public_profile"
                name="public_profile"
                value="1"
                @checked(old('public_profile', $animal->public_profile ?? 0))
            />
            <label class="form-check-label" for="public_profile">Profil publiczny</label>
        </div>
        @error('public_profile')
            <div class="text-danger small">{{ $message }}</div>
        @enderror
    </div>
</div>
