<div class="tab-pane fade @if($vm->activeTab==='traits') show active @endif" id="tab-traits" role="tabpanel">
    <div class="card cardopacity">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Traits</span>
            <form class="d-flex gap-2" method="POST" action="{{ route('admin.settings.traits.store') }}">
                @csrf
                <input type="text" name="name" class="form-control form-control-sm bg-dark text-light" placeholder="Nazwa traitu" required>
                <select name="gene_ids[]" multiple class="form-select form-select-sm bg-dark text-light" style="min-width:200px;">
                    @foreach($vm->genotypeCategories as $gene)
                        <option value="{{ $gene->id }}">{{ $gene->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-primary" type="submit">Dodaj</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-sm align-middle mb-0">
                <thead><tr><th>ID</th><th>Nazwa</th><th>Geny</th><th class="text-end">Opcje</th></tr></thead>
                <tbody>
                @foreach($vm->traits as $trait)
                    <tr>
                        <td>{{ $trait->id }}</td>
                        <td>{{ $trait->name }}</td>
                        <td>
                            @php $list = $trait->genes->map(fn($g) => $g->category?->name)->filter()->implode(', '); @endphp
                            {{ $list ?: '-' }}
                        </td>
                        <td class="text-end d-flex gap-2 justify-content-end">
                            <form method="POST" action="{{ route('admin.settings.traits.destroy', $trait) }}" onsubmit="return confirm('Usunąć trait?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Usuń</button>
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <form class="row g-1 align-items-center" method="POST" action="{{ route('admin.settings.traits.update', $trait) }}">
                                @csrf
                                @method('PATCH')
                                <div class="col-md-3"><input type="text" name="name" class="form-control form-control-sm bg-dark text-light" value="{{ $trait->name }}" required></div>
                                <div class="col-md-7">
                                    <select name="gene_ids[]" multiple class="form-select form-select-sm bg-dark text-light">
                                        @foreach($vm->genotypeCategories as $gene)
                                            <option value="{{ $gene->id }}" @selected($trait->genes->pluck('category_id')->contains($gene->id))>{{ $gene->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2 text-end"><button class="btn btn-sm btn-outline-light" type="submit">Zapisz</button></div>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
