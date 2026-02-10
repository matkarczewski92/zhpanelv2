@extends('layouts.panel')

@section('title', 'Administracja - Ustawienia portalu')

@section('content')
    <div class="mb-3 d-flex align-items-center justify-content-between">
        <div>
            <h1 class="h4 mb-1">Administracja - Ustawienia portalu</h1>
            <p class="text-muted mb-0">Zarządzanie słownikami i konfiguracją - przed zmianami wykonaj Export</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.settings.transfer.export') }}" class="btn btn-outline-light btn-sm">Eksport</a>
            <form method="POST" action="{{ route('admin.settings.transfer.import-preview') }}" enctype="multipart/form-data" class="d-flex align-items-center gap-2">
                @csrf
                <input type="file" name="import_file" accept=".json,.txt,application/json,text/plain" class="form-control form-control-sm bg-dark text-light" required>
                <button type="submit" class="btn btn-primary btn-sm">Import</button>
            </form>
        </div>
    </div>

    @include('admin.settings.partials.import-preview', ['importPreview' => $importPreview ?? null])

    <ul class="nav nav-pills mb-3" id="settingsTabs" role="tablist">
        @php $tabs = [
            'categories' => 'Kategorie',
            'types' => 'Typy',
            'genes' => 'Genotyp: Kategorie',
            'traits' => 'Genotyp: Traits',
            'winter' => 'Zimowanie: Etapy',
            'system' => 'System config',
            'feeds' => 'Karma',
            'finance-categories' => 'Kategorie finansowe',
            'color-groups' => 'Grupy kolorystyczne',
            'ewelink-devices' => 'eWeLink: Urządzenia',
            'genetics-generator' => 'Generuj genetykę',
            'update' => 'Aktualizacja',
        ]; @endphp
        @foreach($tabs as $key => $label)
            <li class="nav-item" role="presentation">
                <button class="nav-link @if($vm->activeTab === $key) active @endif" data-bs-toggle="tab" data-bs-target="#tab-{{ $key }}" type="button" role="tab">{{ $label }}</button>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @include('admin.settings.tabs.categories', ['vm' => $vm])
        @include('admin.settings.tabs.types', ['vm' => $vm])
        @include('admin.settings.tabs.genes', ['vm' => $vm])
        @include('admin.settings.tabs.traits', ['vm' => $vm])
        @include('admin.settings.tabs.winter', ['vm' => $vm])
        @include('admin.settings.tabs.system', ['vm' => $vm])
        @include('admin.settings.tabs.feeds', ['vm' => $vm])
        @include('admin.settings.tabs.finance-categories', ['vm' => $vm])
        @include('admin.settings.tabs.color-groups', ['vm' => $vm])
        @include('admin.settings.tabs.ewelink-devices', ['vm' => $vm])
        @include('admin.settings.tabs.genetics-generator', ['vm' => $vm, 'generatedRows' => $generatedRows ?? [], 'selectedAnimalIds' => $selectedAnimalIds ?? null])
        @include('admin.settings.tabs.update', ['updatePanel' => $updatePanel ?? []])
    </div>
@endsection
