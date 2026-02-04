@php
    $femaleWeights = collect($page->females)->pluck('weight', 'id');
    $maleWeights = collect($page->males)->pluck('weight', 'id');
@endphp

<div class="glass-card glass-table-wrapper">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="strike flex-grow-1"><span>Zapisane plany</span></div>
        <button type="button" class="btn btn-primary btn-sm" data-action="new-plan">Nowy plan</button>
    </div>

    <div class="table-responsive">
        <table class="table glass-table table-sm align-middle mb-0">
            <thead>
                <tr class="text-muted small">
                    <th>Nazwa planu</th>
                    <th class="text-center" style="width: 80px;">Rok</th>
                    <th class="text-center" style="width: 90px;">Liczba par</th>
                    <th style="width: 160px;">Ostatnia aktualizacja</th>
                    <th>Parowanie</th>
                    <th class="text-end" style="width: 180px;">Akcje</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($page->plans as $plan)
                    <tr>
                        <td>{{ $plan['name'] }}</td>
                        <td class="text-center">{{ $plan['planned_year'] ?? '-' }}</td>
                        <td class="text-center">{{ count($plan['pairs']) }}</td>
                        <td>{{ $plan['updated_at_label'] }}</td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach ($plan['pairs'] as $pair)
                                    <span class="badge text-bg-secondary">
                                        {{ $pair['female_name'] }} ({{ (int) ($femaleWeights[$pair['female_id']] ?? 0) }}g.)
                                        x
                                        {{ $pair['male_name'] }} ({{ (int) ($maleWeights[$pair['male_id']] ?? 0) }}g.)
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <button
                                    type="button"
                                    class="btn btn-outline-primary btn-sm"
                                    data-action="edit-plan"
                                    data-plan-id="{{ $plan['id'] }}"
                                    data-plan-name="{{ e($plan['name']) }}"
                                    data-plan-year="{{ $plan['planned_year'] }}"
                                    data-plan-pairs-b64="{{ base64_encode(json_encode($plan['pairs'], JSON_UNESCAPED_UNICODE)) }}"
                                >
                                    Edytuj
                                </button>

                                <form method="POST" action="{{ route('panel.litters-planning.realize', $plan['id']) }}" class="d-inline-flex">
                                    @csrf
                                    <input type="hidden" name="planned_year" value="{{ $plan['planned_year'] ?? $page->selectedSeason }}">
                                    <button type="submit" class="btn btn-outline-success btn-sm">Realizuj</button>
                                </form>

                                <form method="POST" action="{{ route('panel.litters-planning.destroy', $plan['id']) }}" class="d-inline-flex">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm(@js('Usun plan ' . $plan['name'] . '?'))">Usun</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-3">Brak zapisanych planow.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
