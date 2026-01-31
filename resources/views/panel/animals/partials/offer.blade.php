<div class="card cardopacity mb-3" id="offer">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span>Oferta</span>
        <button class="btn btn-outline-light btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#offerEditModal">
            Edycja
        </button>
    </div>
    <div class="card-body">
        @if ($profile->offerExists)
            <div class="mb-3">
                <h6 class="text-muted mb-2">Oferta sprzedaży</h6>
                <dl class="row mb-0 small">
                    <dt class="col-6 text-muted">Cena</dt>
                    <dd class="col-6">{{ $profile->offerSummary['price'] ?? '-' }}</dd>
                    @if (!empty($profile->offerSummary['listed_at']))
                        <dt class="col-6 text-muted">Data wystawienia</dt>
                        <dd class="col-6">{{ $profile->offerSummary['listed_at'] }}</dd>
                    @endif
                    @if (!empty($profile->offerSummary['updated_at']))
                        <dt class="col-6 text-muted">Data aktualizacji</dt>
                        <dd class="col-6">{{ $profile->offerSummary['updated_at'] }}</dd>
                    @endif
                    @if (!empty($profile->offerSummary['sold_at']))
                        <dt class="col-6 text-muted">Data sprzedaży</dt>
                        <dd class="col-6">{{ $profile->offerSummary['sold_at'] }}</dd>
                    @endif
                </dl>
            </div>

            @if ($profile->reservationExists && $profile->reservationSummary)
                <div>
                    <h6 class="text-muted mb-2">Rezerwacja</h6>
                    <dl class="row mb-0 small">
                        <dt class="col-6 text-muted">Rezerwujący</dt>
                        <dd class="col-6">{{ $profile->reservationSummary['reserver_name'] ?? '-' }}</dd>
                        @if (!empty($profile->reservationSummary['deposit_amount']))
                            <dt class="col-6 text-muted">Zadatek</dt>
                            <dd class="col-6">{{ $profile->reservationSummary['deposit_amount'] }}</dd>
                        @endif
                        @if (!empty($profile->reservationSummary['reservation_date']))
                            <dt class="col-6 text-muted">Data rezerwacji</dt>
                            <dd class="col-6">{{ $profile->reservationSummary['reservation_date'] }}</dd>
                        @endif
                        @if (!empty($profile->reservationSummary['reservation_valid_until']))
                            <dt class="col-6 text-muted">Data ważności</dt>
                            <dd class="col-6">{{ $profile->reservationSummary['reservation_valid_until'] }}</dd>
                        @endif
                        @if (!empty($profile->reservationSummary['notes']))
                            <dt class="col-6 text-muted">Adnotacje</dt>
                            <dd class="col-6">{{ $profile->reservationSummary['notes'] }}</dd>
                        @endif
                    </dl>
                </div>
            @endif
        @else
            <div class="text-muted small">Brak aktywnej oferty.</div>
        @endif
    </div>
</div>
