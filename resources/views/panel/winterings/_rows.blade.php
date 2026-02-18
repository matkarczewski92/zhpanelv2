@forelse ($rows as $row)
    <tr data-animal-id="{{ $row['animal_id'] }}">
        <td class="text-muted">{{ $row['animal_id'] }}</td>
        <td>
            <a href="{{ $row['profile_url'] }}" class="link-reset fw-semibold">
                @if (!empty($row['second_name']))
                    <span class="text-muted">"{{ $row['second_name'] }}"</span>
                @endif
                {!! $row['name_html'] !!}
            </a>
            <div class="small text-muted">
                {{ $row['type_name'] }} | {{ $row['category_name'] }} | {{ $row['sex_label'] }}
            </div>
        </td>
        <td>{{ $row['scheme'] }}</td>
        <td>{{ $row['season'] ?? '-' }}</td>
        <td>
            <div>{{ $row['current_stage'] }}</div>
            <div class="small {{ ($row['current_stage_start_is_real'] ?? false) ? 'text-success' : 'text-muted' }}">
                {{ $row['current_stage_start'] ?? '-' }}
            </div>
        </td>
        <td>
            @if (!empty($row['next_stage']))
                <div>{{ $row['next_stage'] }}</div>
                <div class="small text-muted">{{ $row['next_stage_hint'] ?? '-' }}</div>
            @else
                <span class="text-muted">-</span>
            @endif
        </td>
        <td class="{{ ($row['cycle_start_is_real'] ?? false) ? 'text-success' : 'text-muted' }}">
            {{ $row['cycle_start'] ?? '-' }}
        </td>
        <td class="{{ ($row['cycle_end_is_real'] ?? false) ? 'text-success' : 'text-muted' }}">
            {{ $row['cycle_end'] ?? '-' }}
        </td>
        <td class="text-end">
            @if (!empty($row['can_advance']) && !empty($row['advance_url']))
                <button
                    type="button"
                    class="btn btn-outline-success btn-sm js-wintering-advance"
                    data-url="{{ $row['advance_url'] }}"
                >
                    Kolejny etap
                </button>
            @else
                <span class="small text-muted">Brak</span>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="9" class="text-center text-muted">Brak aktywnych zimowan.</td>
    </tr>
@endforelse

