<div class="tab-pane fade @if($vm->activeTab==='winter') show active @endif" id="tab-winter" role="tabpanel">
    @php
        $groupedStages = collect($vm->winteringStages ?? [])
            ->groupBy(fn ($stage) => trim((string) ($stage->scheme ?? '')) ?: 'Zimowanie normalne')
            ->sortKeys();
        $schemeOptions = $groupedStages->keys()->values();
    @endphp

    <div class="card cardopacity">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>Zimowanie - etapy</span>
            <form class="d-flex flex-wrap gap-2" method="POST" action="{{ route('admin.settings.wintering-stages.store') }}">
                @csrf
                <input type="text" name="scheme" list="wintering-schemes-list" class="form-control form-control-sm bg-dark text-light" placeholder="Schemat (np. Zimowanie normalne)" required>
                <input type="number" name="order" class="form-control form-control-sm bg-dark text-light" placeholder="Kolejność" required min="1">
                <input type="text" name="title" class="form-control form-control-sm bg-dark text-light" placeholder="Nazwa etapu" required>
                <input type="number" name="duration" class="form-control form-control-sm bg-dark text-light" placeholder="Czas (dni)" required min="0">
                <button class="btn btn-sm btn-primary" type="submit">Dodaj</button>
            </form>
            <datalist id="wintering-schemes-list">
                @foreach ($schemeOptions as $schemeOption)
                    <option value="{{ $schemeOption }}"></option>
                @endforeach
            </datalist>
        </div>

        @if($groupedStages->isEmpty())
            <div class="card-body text-muted small">Brak etapów zimowania.</div>
        @else
            @foreach($groupedStages as $scheme => $stages)
                @php
                    $stages = collect($stages)->sortBy('order')->values();
                    $total = $stages->sum(fn ($stage) => (int) $stage->duration);
                @endphp
                <div class="@if(!$loop->first) border-top border-light border-opacity-10 @endif">
                    <div class="px-3 py-2 d-flex justify-content-between align-items-center">
                        <strong>{{ $scheme }}</strong>
                        <span class="small text-muted">{{ $stages->count() }} etapów | {{ $total }} dni</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-dark table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Kolejność</th>
                                    <th>Nazwa</th>
                                    <th>Czas</th>
                                    <th class="text-end">Opcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stages as $stage)
                                    <tr>
                                        <td>{{ $stage->order }}</td>
                                        <td>{{ $stage->title }}</td>
                                        <td>{{ $stage->duration }} dni</td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('admin.settings.wintering-stages.destroy', $stage) }}" onsubmit="return confirm('Usunąć etap?')" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Usuń</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="4">
                                            <form class="row g-1 align-items-center" method="POST" action="{{ route('admin.settings.wintering-stages.update', $stage) }}">
                                                @csrf
                                                @method('PATCH')
                                                <div class="col-md-3"><input type="text" name="scheme" class="form-control form-control-sm bg-dark text-light" value="{{ $stage->scheme }}" required></div>
                                                <div class="col-md-1"><input type="number" name="order" class="form-control form-control-sm bg-dark text-light" value="{{ $stage->order }}" min="1" required></div>
                                                <div class="col-md-4"><input type="text" name="title" class="form-control form-control-sm bg-dark text-light" value="{{ $stage->title }}" required></div>
                                                <div class="col-md-2"><input type="number" name="duration" class="form-control form-control-sm bg-dark text-light" value="{{ $stage->duration }}" min="0" required></div>
                                                <div class="col-md-2 text-end"><button class="btn btn-sm btn-outline-light">Zapisz</button></div>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
