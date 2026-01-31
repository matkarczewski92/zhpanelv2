<div class="modal fade" id="galleryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content photobg">
            <div class="modal-header">
                <h5 class="modal-title">Galeria zdjęć</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ $profile->galleryUploadUrl }}" enctype="multipart/form-data" class="row g-2 align-items-end mb-3">
                    @csrf
                    <div class="col-md-8">
                        <label class="form-label">Dodaj zdjęcie</label>
                        <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100 mt-3 mt-md-0">Dodaj nowe zdjęcie</button>
                    </div>
                </form>

                @if ($profile->photos['has_photos'])
                    <div class="row g-3">
                        @foreach ($profile->photos['items'] as $photo)
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="card h-100 glass-card">
                                    <img src="{{ $photo['thumb_url'] }}" class="card-img-top" alt="{{ $photo['label'] }}" style="object-fit: cover; height: 200px;">
                                    <div class="card-body d-flex flex-column gap-2">
                                        <div class="d-flex flex-wrap gap-2">
                                            <form method="POST" action="{{ $photo['delete_url'] }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm w-100">Usuń</button>
                                            </form>
                                            @if (!$photo['is_main'])
                                                <form method="POST" action="{{ $photo['set_main_url'] }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-success btn-sm w-100">Ustaw jako główne</button>
                                                </form>
                                            @else
                                                <span class="badge bg-success align-self-center">Główne</span>
                                            @endif
                                        </div>
                                        <form method="POST" action="{{ $photo['toggle_website_url'] }}">
                                            @csrf
                                            @method('PATCH')
                                            <div class="form-check form-switch">
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    role="switch"
                                                    id="photoWebsite{{ $photo['id'] }}"
                                                    onchange="this.form.submit()"
                                                    {{ $photo['website_visible'] ? 'checked' : '' }}
                                                >
                                                <label class="form-check-label" for="photoWebsite{{ $photo['id'] }}">
                                                    Strona główna hodowli
                                                </label>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-muted">Brak zdjęć w galerii.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content photobg">
            <div class="modal-header">
                <h5 class="modal-title">Zdjęcie główne</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <div class="modal-body text-center">
                @if ($profile->animal['avatar_url'])
                    <img class="profile-main-photo" src="{{ $profile->animal['avatar_url'] }}" alt="{{ $profile->animal['name'] }}" />
                @else
                    <div class="text-muted">Brak zdjęcia.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="passportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content photobg">
            <div class="modal-header">
                <h5 class="modal-title">Paszport</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <div class="modal-body">
                <div class="text-muted">Brak danych paszportu/certyfikatu.</div>
            </div>
        </div>
    </div>
</div>

@include('panel.animals.partials.offer-edit-modal')
@include('panel.animals.partials.animal-edit-modal')
