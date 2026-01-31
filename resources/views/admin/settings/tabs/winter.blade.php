<div class="tab-pane fade @if($vm->activeTab==='winter') show active @endif" id="tab-winter" role="tabpanel">
    <div class="card cardopacity">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Zimowanie - etapy</span>
            <form class="d-flex gap-2" method="POST" action="{{ route('admin.settings.wintering-stages.store') }}">
                @csrf
                <input type="number" name="order" class="form-control form-control-sm bg-dark text-light" placeholder="Kolejność" required min="1">
                <input type="text" name="title" class="form-control form-control-sm bg-dark text-light" placeholder="Nazwa" required>
                <input type="number" name="duration" class="form-control form-control-sm bg-dark text-light" placeholder="Czas (dni)" required min="0">
                <button class="btn btn-sm btn-primary" type="submit">Dodaj</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-sm align-middle mb-0">
                <thead><tr><th>Kolejność</th><th>Nazwa</th><th>Czas</th><th class="text-end">Opcje</th></tr></thead>
                <tbody>
                @php $total = 0; @endphp
                @foreach($vm->winteringStages as $stage)
                    @php $total += (int)$stage->duration; @endphp
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
                                <div class="col-md-2"><input type="number" name="order" class="form-control form-control-sm bg-dark text-light" value="{{ $stage->order }}" min="1" required></div>
                                <div class="col-md-6"><input type="text" name="title" class="form-control form-control-sm bg-dark text-light" value="{{ $stage->title }}" required></div>
                                <div class="col-md-2"><input type="number" name="duration" class="form-control form-control-sm bg-dark text-light" value="{{ $stage->duration }}" min="0" required></div>
                                <div class="col-md-2 text-end"><button class="btn btn-sm btn-outline-light">Zapisz</button></div>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                    <tr><th colspan="2">Łącznie</th><th colspan="2">{{ $total }} dni</th></tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
