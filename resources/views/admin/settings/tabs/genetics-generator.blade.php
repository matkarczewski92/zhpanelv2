<div class="tab-pane fade @if($vm->activeTab==='genetics-generator') show active @endif" id="tab-genetics-generator" role="tabpanel">
    @php
        $generatedRows = $generatedRows ?? [];
        $defaultSelected = $vm->animalsWithoutGenotypes->pluck('id')->map(fn($id) => (int) $id)->all();
        $selectedAnimalIds = collect($selectedAnimalIds ?? $defaultSelected)->map(fn($id) => (int) $id)->unique()->flip();
    @endphp

    <div class="card cardopacity">
        <form method="POST">
            @csrf
            <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <span>Generuj genetykę z nazwy</span>
                <button
                    class="btn btn-sm btn-primary"
                    type="submit"
                    formaction="{{ route('admin.settings.genetics-generator.generate') }}"
                    formmethod="POST"
                >
                    Generuj genetykę z nazwy
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-dark table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 60px;">#</th>
                            <th style="width: 90px;">ID</th>
                            <th>Nazwa</th>
                            <th>Wygenerowane geny</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vm->animalsWithoutGenotypes as $animal)
                            <tr>
                                <td class="text-center">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="selected_animal_ids[]"
                                        value="{{ $animal->id }}"
                                        @checked($selectedAnimalIds->has((int) $animal->id))
                                    >
                                </td>
                                <td>{{ $animal->id }}</td>
                                <td>{{ $animal->name }}</td>
                                <td>
                                    @php $genes = $generatedRows[$animal->id] ?? []; @endphp
                                    @if(empty($genes))
                                        <span class="text-muted">-</span>
                                    @else
                                        @foreach($genes as $gene)
                                            <span class="badge text-bg-info me-1 mb-1">
                                                {{ strtoupper($gene['type']) }}: {{ $gene['name'] }}
                                            </span>
                                        @endforeach
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    Brak węży bez wpisów w tabeli animal_genotype.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer d-flex justify-content-end">
                <button
                    class="btn btn-sm btn-success"
                    type="submit"
                    formaction="{{ route('admin.settings.genetics-generator.store') }}"
                    formmethod="POST"
                >
                    Wprowadź geny
                </button>
            </div>
        </form>
    </div>
</div>
