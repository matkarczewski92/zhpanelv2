<div class="modal fade" id="offerEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content photobg">
            <div class="modal-header">
                <h5 class="modal-title">Oferta sprzedazy</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <form id="offerEditForm" method="POST" action="{{ $profile->offerForm['action'] ?? '#' }}">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <h6 class="text-muted">Wystaw na sprzedaz</h6>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Cena</label>
                                <input type="number" step="0.01" name="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $profile->offerForm['price'] ?? '') }}" required>
                                @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Data sprzedazy</label>
                                <input type="date" name="sold_at" class="form-control @error('sold_at') is-invalid @enderror" value="{{ old('sold_at', $profile->offerForm['sold_at'] ?? '') }}">
                                @error('sold_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <div class="form-check mt-4 pt-2">
                                    <input type="checkbox" class="form-check-input" id="publicProfileToggle" name="public_profile" value="1" @checked(old('public_profile', $profile->offerForm['public_profile_enabled'] ?? false))>
                                    <label class="form-check-label" for="publicProfileToggle">Wlacz profil publiczny</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <h6 class="text-muted">Rezerwacja</h6>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Rezerwujacy</label>
                                <input type="text" name="reserver_name" class="form-control @error('reserver_name') is-invalid @enderror" value="{{ old('reserver_name', $profile->offerForm['reserver_name'] ?? '') }}">
                                @error('reserver_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Zadatek</label>
                                <input type="number" step="0.01" name="deposit_amount" class="form-control @error('deposit_amount') is-invalid @enderror" value="{{ old('deposit_amount', $profile->offerForm['deposit_amount'] ?? '') }}">
                                @error('deposit_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Data waznosci</label>
                                <input type="date" name="reservation_valid_until" class="form-control @error('reservation_valid_until') is-invalid @enderror" value="{{ old('reservation_valid_until', $profile->offerForm['reservation_valid_until'] ?? '') }}">
                                @error('reservation_valid_until')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Adnotacje</label>
                                <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $profile->offerForm['notes'] ?? '') }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <div class="modal-footer flex-wrap gap-2">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Zamknij</button>
                <button type="submit" form="offerDeleteReservationForm" formnovalidate data-role="delete-reservation" class="btn btn-outline-danger" onclick="return confirm('Usunac rezerwacje?')" @if(empty($profile->offerForm['delete_reservation_url'])) hidden @endif>Usun rezerwacje</button>
                <button type="submit" form="offerSellForm" formnovalidate data-role="sell-offer" class="btn btn-success" @if(empty($profile->offerForm['sell_url'])) hidden @endif>Sprzedaj</button>
                <button type="submit" form="offerDeleteForm" formnovalidate data-role="delete-offer" class="btn btn-outline-danger" onclick="return confirm('Usunac oferte?')" @if(empty($profile->offerForm['delete_offer_url'])) hidden @endif>Usun oferte</button>
                <button type="submit" form="offerEditForm" class="btn btn-primary">Zapisz</button>
            </div>

            <form id="offerDeleteReservationForm" method="POST" action="{{ $profile->offerForm['delete_reservation_url'] ?? '#' }}">
                @csrf
                @method('DELETE')
            </form>
            <form id="offerDeleteForm" method="POST" action="{{ $profile->offerForm['delete_offer_url'] ?? '#' }}">
                @csrf
                @method('DELETE')
            </form>
            <form id="offerSellForm" method="POST" action="{{ $profile->offerForm['sell_url'] ?? '#' }}">
                @csrf
            </form>
        </div>
    </div>
</div>
