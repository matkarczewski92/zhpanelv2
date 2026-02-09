@php
    $preview = is_array($importPreview ?? null) ? $importPreview : null;
@endphp

@if ($preview)
    @php
        $summary = (array) ($preview['summary'] ?? []);
        $sections = (array) ($preview['sections'] ?? []);
    @endphp
    <div class="card cardopacity mb-3 border border-warning border-opacity-50">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <strong>Podgląd importu ustawień</strong>
                <div class="small text-muted">Wczytano: {{ $preview['generated_at'] ?? '-' }}</div>
            </div>
            <div class="small">
                <span class="badge text-bg-success">Nowe: {{ (int) ($summary['new'] ?? 0) }}</span>
                <span class="badge text-bg-warning text-dark">Różne: {{ (int) ($summary['different'] ?? 0) }}</span>
                <span class="badge text-bg-secondary">Takie same: {{ (int) ($summary['same'] ?? 0) }}</span>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.settings.transfer.apply') }}" class="d-flex flex-column gap-3">
                @csrf

                @foreach ($sections as $sectionKey => $section)
                    @php
                        $rows = (array) ($section['rows'] ?? []);
                        $fields = (array) ($section['fields'] ?? []);
                    @endphp
                    @if (!empty($rows))
                        <div class="border rounded p-2">
                            <div class="fw-semibold mb-2">{{ $section['label'] ?? $sectionKey }}</div>
                            <div class="table-responsive">
                                <table class="table table-dark table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 110px;">Status</th>
                                            @foreach ($fields as $field)
                                                <th>{{ $field['label'] ?? ($field['key'] ?? '') }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($rows as $i => $row)
                                            @php
                                                $status = (string) ($row['status'] ?? 'new');
                                                $data = (array) ($row['data'] ?? []);
                                            @endphp
                                            <tr>
                                                <td>
                                                    @if ($status === 'new')
                                                        <span class="badge text-bg-success">Nowe</span>
                                                    @elseif ($status === 'different')
                                                        <span class="badge text-bg-warning text-dark">Różne</span>
                                                    @else
                                                        <span class="badge text-bg-secondary">Takie same</span>
                                                    @endif
                                                </td>
                                                @foreach ($fields as $field)
                                                    @php
                                                        $key = (string) ($field['key'] ?? '');
                                                        $type = (string) ($field['type'] ?? 'text');
                                                        $value = $data[$key] ?? ($type === 'bool' ? false : '');
                                                        if ($type === 'csv' && is_array($value)) {
                                                            $value = implode(',', $value);
                                                        }
                                                    @endphp
                                                    <td>
                                                        @if ($type === 'textarea')
                                                            <textarea
                                                                class="form-control form-control-sm bg-dark text-light"
                                                                rows="2"
                                                                name="rows[{{ $sectionKey }}][{{ $i }}][{{ $key }}]"
                                                            >{{ (string) $value }}</textarea>
                                                        @elseif ($type === 'bool')
                                                            <input type="hidden" name="rows[{{ $sectionKey }}][{{ $i }}][{{ $key }}]" value="0">
                                                            <input
                                                                type="checkbox"
                                                                class="form-check-input"
                                                                name="rows[{{ $sectionKey }}][{{ $i }}][{{ $key }}]"
                                                                value="1"
                                                                @checked((bool) $value)
                                                            >
                                                        @else
                                                            <input
                                                                type="{{ $type === 'number' ? 'number' : 'text' }}"
                                                                step="any"
                                                                class="form-control form-control-sm bg-dark text-light"
                                                                name="rows[{{ $sectionKey }}][{{ $i }}][{{ $key }}]"
                                                                value="{{ (string) $value }}"
                                                            >
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                @endforeach

                <div class="d-flex flex-wrap gap-2 justify-content-end">
                    <button type="submit" formaction="{{ route('admin.settings.transfer.reject') }}" class="btn btn-outline-secondary">
                        Odrzuć
                    </button>
                    <button type="submit" name="mode" value="merge" class="btn btn-outline-primary">
                        Zatwierdź różnice
                    </button>
                    <button type="submit" name="mode" value="replace" class="btn btn-primary">
                        Zatwierdź i zastąp
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif

