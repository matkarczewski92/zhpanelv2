<div class="modal fade" id="offerEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content photobg">
            <div class="modal-header">
                <h5 class="modal-title">Oferta sprzedaży</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <form method="POST" action="{{ $profile->offerForm['action'] ?? '#' }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <h6 class="text-muted">Wystaw na sprzedaż</h6>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Cena</label>
                                <input type="number" step="0.01" name="price" class="form-control" value="{{ $profile->offerForm['price'] ?? '' }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Data sprzedaży</label>
                                <input type="date" name="sold_at" class="form-control" value="{{ $profile->offerForm['sold_at'] ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <div class="form-check mt-4 pt-2">
                                    <input type="checkbox" class="form-check-input" id="publicProfileToggle" name="public_profile" @checked($profile->offerForm['public_profile_enabled'] ?? false)>
                                    <label class="form-check-label" for="publicProfileToggle">Włącz profil publiczny</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <h6 class="text-muted">Rezerwacja</h6>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Rezerwujący</label>
                                <input type="text" name="reserver_name" class="form-control" value="{{ $profile->offerForm['reserver_name'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Zadatek</label>
                                <input type="number" step="0.01" name="deposit_amount" class="form-control" value="{{ $profile->offerForm['deposit_amount'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Data ważności</label>
                                <input type="date" name="reservation_valid_until" class="form-control" value="{{ $profile->offerForm['reservation_valid_until'] ?? '' }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Adnotacje</label>
                                <textarea name="notes" rows="3" class="form-control">{{ $profile->offerForm['notes'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Zamknij</button>
                    <button type="submit" data-role="delete-reservation" formaction="{{ $profile->offerForm['delete_reservation_url'] ?? '' }}" formmethod="POST" name="_method" value="DELETE" class="btn btn-outline-danger" onclick="return confirm('Usunąć rezerwację?')" @if(empty($profile->offerForm['delete_reservation_url'])) hidden @endif>Usuń rezerwację</button>
                    <button type="submit" data-role="sell-offer" formaction="{{ $profile->offerForm['sell_url'] ?? '' }}" formmethod="POST" class="btn btn-success" @if(empty($profile->offerForm['sell_url'])) hidden @endif>Sprzedaj</button>
                    <button type="submit" data-role="delete-offer" formaction="{{ $profile->offerForm['delete_offer_url'] ?? '' }}" formmethod="POST" name="_method" value="DELETE" class="btn btn-outline-danger" onclick="return confirm('Usunąć ofertę?')" @if(empty($profile->offerForm['delete_offer_url'])) hidden @endif>Usuń ofertę</button>
                    <button type="submit" class="btn btn-primary">Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
