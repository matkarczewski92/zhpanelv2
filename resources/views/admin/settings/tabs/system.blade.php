<div class="tab-pane fade @if($vm->activeTab==='system') show active @endif" id="tab-system" role="tabpanel">
    <div class="card cardopacity">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>System config</span>
            <form class="d-flex gap-2 flex-wrap" method="POST" action="{{ route('admin.settings.system-config.store') }}">
                @csrf
                <input type="text" name="key" class="form-control form-control-sm bg-dark text-light" style="max-width: 220px;" placeholder="Klucz" required>
                <input type="text" name="name" class="form-control form-control-sm bg-dark text-light" style="max-width: 220px;" placeholder="Nazwa" required>
                <textarea name="value" class="form-control form-control-sm bg-dark text-light text-break" style="max-width: 420px; min-height: 48px;" placeholder="Wartość" rows="2"></textarea>
                <button class="btn btn-sm btn-primary" type="submit">Dodaj</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-sm align-middle mb-0">
                <thead><tr><th>Klucz</th><th>Nazwa</th><th>Wartość</th><th class="text-end">Opcje</th></tr></thead>
                <tbody>
                @foreach($vm->systemConfig as $cfg)
                    <tr>
                        <td class="text-break">{{ $cfg->key }}</td>
                        <td class="text-break">{{ $cfg->name }}</td>
                        <td class="text-break" style="max-width: 480px; white-space: pre-wrap;">{{ $cfg->value }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.settings.system-config.destroy', $cfg) }}" onsubmit="return confirm('Usunąć?')" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Usuń</button>
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <form class="row g-1 align-items-center" method="POST" action="{{ route('admin.settings.system-config.update', $cfg) }}">
                                @csrf
                                @method('PATCH')
                                <div class="col-md-3 col-lg-2"><input type="text" class="form-control form-control-sm bg-dark text-light" value="{{ $cfg->name }}" name="name" required></div>
                                <div class="col-md-7 col-lg-8"><textarea class="form-control form-control-sm bg-dark text-light text-break" name="value" rows="3" style="white-space: pre-wrap;">{{ $cfg->value }}</textarea></div>
                                <div class="col-md-2 text-end"><button class="btn btn-sm btn-outline-light">Zapisz</button></div>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
