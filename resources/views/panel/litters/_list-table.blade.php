@props([
    'rows',
    'emptyMessage' => 'Brak danych.',
])

<div class="table-responsive">
    <table class="table glass-table table-sm align-middle mb-0">
        <thead>
            <tr class="text-muted small">
                <th>Kod miotu</th>
                <th>Sezon</th>
                <th>Data laczenia</th>
                <th>Data zniosu</th>
                <th>Data klucia</th>
                <th>Samiec</th>
                <th>Samica</th>
                <th>Status</th>
                <th class="text-end">Akcje</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>
                        <a href="{{ $row['show_url'] }}" class="link-light text-decoration-none fw-semibold">
                            {{ $row['litter_code'] }}
                        </a>
                    </td>
                    <td>{{ $row['season'] ?: '-' }}</td>
                    <td>{{ $row['connection_date'] ?: '-' }}</td>
                    <td>
                        @if ($row['laying_date'])
                            {{ $row['laying_date'] }}
                        @elseif($row['estimated_laying_date'])
                            <span class="text-muted">plan. {{ $row['estimated_laying_date'] }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if ($row['hatching_date'])
                            {{ $row['hatching_date'] }}
                        @elseif($row['estimated_hatching_date'])
                            <span class="text-muted">plan. {{ $row['estimated_hatching_date'] }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if ($row['male_parent_id'])
                            <a href="{{ route('panel.animals.show', $row['male_parent_id']) }}" class="link-light text-decoration-none">
                                #{{ $row['male_parent_id'] }} {{ $row['male_parent_name'] }}
                            </a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if ($row['female_parent_id'])
                            <a href="{{ route('panel.animals.show', $row['female_parent_id']) }}" class="link-light text-decoration-none">
                                #{{ $row['female_parent_id'] }} {{ $row['female_parent_name'] }}
                            </a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge text-bg-secondary">{{ $row['status_label'] }}</span>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="{{ $row['show_url'] }}" class="btn btn-link text-light p-0 me-2" aria-label="Podglad">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ $row['edit_url'] }}" class="btn btn-link text-light p-0" aria-label="Edytuj">
                            <i class="bi bi-pencil-square"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-3">{{ $emptyMessage }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="card-body border-top border-opacity-10 border-light">
    {{ $rows->links() }}
</div>

