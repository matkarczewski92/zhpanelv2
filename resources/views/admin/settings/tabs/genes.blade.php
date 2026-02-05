<div class="tab-pane fade @if($vm->activeTab==='genes') show active @endif" id="tab-genes" role="tabpanel">
    <div class="card cardopacity">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Genotyp: kategorie</span>
            <form class="d-flex gap-2" method="POST" action="{{ route('admin.settings.genotype-categories.store') }}">
                @csrf
                <input type="text" name="name" class="form-control form-control-sm bg-dark text-light" placeholder="Nazwa" required>
                <input type="text" name="gene_code" class="form-control form-control-sm bg-dark text-light" placeholder="Kod" maxlength="10" required>
                <input type="text" name="gene_type" class="form-control form-control-sm bg-dark text-light" placeholder="Typ" maxlength="2" required>
                <button class="btn btn-sm btn-primary" type="submit">Dodaj</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-sm align-middle mb-0">
                <thead><tr><th>ID</th><th>Nazwa</th><th>Kod</th><th>Typ</th><th class="text-end">Opcje</th></tr></thead>
                <tbody>
                @foreach($vm->genotypeCategories as $gene)
                    <tr>
                        <td>{{ $gene->id }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.settings.genotype-categories.update', $gene) }}" class="row g-1 align-items-center">
                                @csrf
                                @method('PATCH')
                                <div class="col-md-4"><input type="text" name="name" class="form-control form-control-sm bg-dark text-light" value="{{ $gene->name }}" required></div>
                                <div class="col-md-3"><input type="text" name="gene_code" class="form-control form-control-sm bg-dark text-light" value="{{ $gene->gene_code }}" maxlength="10" required></div>
                                <div class="col-md-3"><input type="text" name="gene_type" class="form-control form-control-sm bg-dark text-light" value="{{ $gene->gene_type }}" maxlength="2" required></div>
                                <div class="col-md-2 text-end"><button class="btn btn-sm btn-outline-light" type="submit">Zapisz</button></div>
                            </form>
                        </td>
                        <td colspan="3"></td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.settings.genotype-categories.destroy', $gene) }}" onsubmit="return confirm('Usunąć?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Usuń</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
