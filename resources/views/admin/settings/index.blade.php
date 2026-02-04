@extends('layouts.panel')

@section('title', 'Ustawienia portalu')

@section('content')
    <div class="mb-3 d-flex align-items-center justify-content-between">
        <div>
            <h1 class="h4 mb-1">Ustawienia portalu</h1>
            <p class="text-muted mb-0">Zarządzanie słownikami i konfiguracją.</p>
        </div>
    </div>

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
    </div>
@endsection
