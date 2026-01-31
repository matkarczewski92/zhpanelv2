<div class="tab-pane fade @if($vm->activeTab==='feeds') show active @endif" id="tab-feeds" role="tabpanel">
    <div class="card cardopacity">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Karma</span>
            <form class="d-flex gap-2" method="POST" action="{{ route('admin.settings.feeds.store') }}">
                @csrf
                <input type="text" name="name" class="form-control form-control-sm bg-dark text-light" placeholder="Nazwa" required>
                <input type="number" name="feeding_interval" class="form-control form-control-sm bg-dark text-light" placeholder="Interwał" required min="0">
                <input type="number" step="0.01" name="last_price" class="form-control form-control-sm bg-dark text-light" placeholder="Cena">
                <input type="number" name="amount" class="form-control form-control-sm bg-dark text-light" placeholder="Ilość" min="0">
                <button class="btn btn-sm btn-primary" type="submit">Dodaj</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-sm align-middle mb-0">
                <thead><tr><th>ID</th><th>Nazwa</th><th>Interwał</th><th>Cena</th><th>Ilość</th><th class="text-end">Opcje</th></tr></thead>
                <tbody>
                @foreach($vm->feeds as $feed)
                    <tr>
                        <td>{{ $feed->id }}</td>
                        <td>{{ $feed->name }}</td>
                        <td>{{ $feed->feeding_interval }}</td>
                        <td>{{ $feed->last_price }}</td>
                        <td>{{ $feed->amount }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.settings.feeds.destroy', $feed) }}" onsubmit="return confirm('Usunąć karmę?')" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Usuń</button>
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6">
                            <form class="row g-1 align-items-center" method="POST" action="{{ route('admin.settings.feeds.update', $feed) }}">
                                @csrf
                                @method('PATCH')
                                <div class="col-md-3"><input type="text" name="name" class="form-control form-control-sm bg-dark text-light" value="{{ $feed->name }}" required></div>
                                <div class="col-md-2"><input type="number" name="feeding_interval" class="form-control form-control-sm bg-dark text-light" value="{{ $feed->feeding_interval }}" min="0" required></div>
                                <div class="col-md-2"><input type="number" step="0.01" name="last_price" class="form-control form-control-sm bg-dark text-light" value="{{ $feed->last_price }}" min="0"></div>
                                <div class="col-md-2"><input type="number" name="amount" class="form-control form-control-sm bg-dark text-light" value="{{ $feed->amount }}" min="0"></div>
                                <div class="col-md-3 text-end"><button class="btn btn-sm btn-outline-light">Zapisz</button></div>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
