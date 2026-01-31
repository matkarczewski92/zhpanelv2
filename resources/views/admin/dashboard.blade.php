@extends('layouts.panel')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">Dashboard</h1>
        <p class="text-muted mb-0">Podgląd najważniejszych informacji panelu.</p>
    </div>

    <div class="row g-3">
        <div class="col-sm-6 col-xl-3">
            <div class="card cardopacity h-100">
                <div class="card-body">
                    <div class="text-uppercase text-muted small">Przychod (miesiac)</div>
                    <div class="fs-4 fw-semibold mt-2">128 400 zl</div>
                    <div class="text-success small">+12% vs poprzedni</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card cardopacity h-100">
                <div class="card-body">
                    <div class="text-uppercase text-muted small">Nowi uzytkownicy</div>
                    <div class="fs-4 fw-semibold mt-2">842</div>
                    <div class="text-success small">+6% tygodniowo</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card cardopacity h-100">
                <div class="card-body">
                    <div class="text-uppercase text-muted small">Zamowienia</div>
                    <div class="fs-4 fw-semibold mt-2">1 204</div>
                    <div class="text-danger small">-3% tygodniowo</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card cardopacity h-100">
                <div class="card-body">
                    <div class="text-uppercase text-muted small">Konwersja</div>
                    <div class="fs-4 fw-semibold mt-2">3.2%</div>
                    <div class="text-success small">+0.4 pp</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-8">
            <div class="card cardopacity h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <h2 class="h6 mb-0">Ostatnie zamowienia</h2>
                        <span class="badge text-bg-secondary">Dzisiaj</span>
                    </div>
                    <div class="glass-card glass-table-wrapper table-responsive mt-3">
                        <table class="table glass-table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Klient</th>
                                    <th>Produkt</th>
                                    <th>Status</th>
                                    <th class="text-end">Kwota</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Anna Kowalska</td>
                                    <td>Pakiet PRO</td>
                                    <td><span class="badge text-bg-success">Oplacone</span></td>
                                    <td class="text-end">1 200 zl</td>
                                </tr>
                                <tr>
                                    <td>Mateusz Nowak</td>
                                    <td>Subskrypcja</td>
                                    <td><span class="badge text-bg-warning">Weryfikacja</span></td>
                                    <td class="text-end">420 zl</td>
                                </tr>
                                <tr>
                                    <td>Emilia Piasek</td>
                                    <td>Dodatek CRM</td>
                                    <td><span class="badge text-bg-info">Nowe</span></td>
                                    <td class="text-end">860 zl</td>
                                </tr>
                                <tr>
                                    <td>Marcin Zieliński</td>
                                    <td>Pakiet Basic</td>
                                    <td><span class="badge text-bg-danger">Anulowane</span></td>
                                    <td class="text-end">0 zl</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card cardopacity h-100">
                <div class="card-body">
                    <h2 class="h6">Do zrobienia</h2>
                    <div class="vstack gap-2 mt-3">
                        <div class="d-flex align-items-center justify-content-between p-2 rounded bg-dark bg-opacity-50">
                            <span>Zweryfikuj platnosci</span>
                            <span class="badge text-bg-primary">4</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between p-2 rounded bg-dark bg-opacity-50">
                            <span>Nowe zgloszenia</span>
                            <span class="badge text-bg-primary">8</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between p-2 rounded bg-dark bg-opacity-50">
                            <span>Aktualizacja tresci</span>
                            <span class="badge text-bg-primary">2</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
