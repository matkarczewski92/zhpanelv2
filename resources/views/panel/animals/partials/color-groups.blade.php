<div class="card cardopacity mb-3">
    <div class="card-header">Grupy kolorystyczne</div>
    <div class="card-body">
        @if (count($profile->colorGroups['options'] ?? []) === 0)
            <p class="text-muted mb-0 small">Brak aktywnych grup kolorystycznych w ustawieniach portalu.</p>
        @else
            <form method="POST" action="{{ $profile->colorGroups['update_url'] }}">
                @csrf
                @error('color_group_ids')
                    <div class="alert alert-danger py-2 small">{{ $message }}</div>
                @enderror
                <div class="color-group-toggles mb-3">
                    @foreach ($profile->colorGroups['options'] as $group)
                        @php
                            $inputId = 'animalColorGroup' . $group['id'];
                        @endphp
                        <input
                            class="color-group-toggle__input"
                            type="checkbox"
                            id="{{ $inputId }}"
                            name="color_group_ids[]"
                            value="{{ $group['id'] }}"
                            @checked(in_array($group['id'], $profile->colorGroups['selected_ids'] ?? [], true))
                        >
                        <label class="color-group-toggle" for="{{ $inputId }}">
                            {{ $group['name'] }}
                        </label>
                    @endforeach
                </div>
                <button class="btn btn-primary btn-sm w-100" type="submit">Zapisz grupy</button>
            </form>
        @endif
    </div>
</div>
