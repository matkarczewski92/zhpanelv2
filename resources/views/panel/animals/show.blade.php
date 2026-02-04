@extends('layouts.panel')

@section('title', 'Profil zwierzęcia')

@section('content')
    <div class="animal-profile">
        @include('panel.animals.partials.header', ['profile' => $profile])
        @include('panel.animals.partials.photobar', ['profile' => $profile])

        <div class="row g-3">
            <div class="col-lg-3">
                @include('panel.animals.partials.details', ['profile' => $profile])
                @include('panel.animals.partials.genotype', ['profile' => $profile])
                @include('panel.animals.partials.wintering', ['profile' => $profile])
                @include('panel.animals.partials.color-groups', ['profile' => $profile])
            </div>
            <div class="col-lg-6">
                @include('panel.animals.partials.feedings', ['profile' => $profile])
                @include('panel.animals.partials.weights', ['profile' => $profile])
            </div>
            <div class="col-lg-3">
                @include('panel.animals.partials.offer', ['profile' => $profile])
                @include('panel.animals.partials.litters', ['profile' => $profile])
                @include('panel.animals.partials.molts', ['profile' => $profile])
            </div>
        </div>

        @include('panel.animals.partials.photo-preview-modal')
        @include('panel.animals.partials.modals', ['profile' => $profile])
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const cleanupOverlayState = () => {
                window.setTimeout(() => {
                    const hasOpenModal = document.querySelector('.modal.show');
                    const hasOpenOffcanvas = document.querySelector('.offcanvas.show');

                    if (hasOpenModal || hasOpenOffcanvas) {
                        return;
                    }

                    document.querySelectorAll('.modal-backdrop, .offcanvas-backdrop').forEach((el) => el.remove());
                    document.body.classList.remove('modal-open', 'offcanvas-open');
                    document.body.style.removeProperty('overflow');
                    document.body.style.removeProperty('padding-right');
                }, 0);
            };

            document.addEventListener('hidden.bs.modal', cleanupOverlayState);
            document.addEventListener('hidden.bs.offcanvas', cleanupOverlayState);

            const photobar = document.getElementById('panelPhotobar');
            const previewModalEl = document.getElementById('animalPhotoPreviewModal');
            const previewImg = document.getElementById('photoPreviewImg');
            const btnPrev = document.getElementById('photoPreviewPrev');
            const btnNext = document.getElementById('photoPreviewNext');
            if (!photobar || !previewModalEl || !previewImg || !btnPrev || !btnNext) return;

            const photosAttr = photobar.querySelector('[data-photos]')?.getAttribute('data-photos');
            const photos = photosAttr ? JSON.parse(photosAttr) : [];
            if (!photos.length) return;

            const modal = bootstrap.Modal.getOrCreateInstance(previewModalEl);
            let current = 0;

            const show = (idx) => {
                current = (idx + photos.length) % photos.length;
                previewImg.src = photos[current].url;
                previewImg.alt = photos[current].alt || 'Zdjęcie';
            };

            document.querySelectorAll('.photobar-thumb[data-gallery-index]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const idx = parseInt(btn.getAttribute('data-gallery-index') || '0', 10);
                    show(idx);
                    modal.show();
                });
            });

            btnPrev.addEventListener('click', () => show(current - 1));
            btnNext.addEventListener('click', () => show(current + 1));

            const keyHandler = (e) => {
                if (e.key === 'ArrowLeft') { e.preventDefault(); show(current - 1); }
                if (e.key === 'ArrowRight') { e.preventDefault(); show(current + 1); }
            };

            previewModalEl.addEventListener('shown.bs.modal', () => {
                document.addEventListener('keydown', keyHandler);
            });
            previewModalEl.addEventListener('hidden.bs.modal', () => {
                document.removeEventListener('keydown', keyHandler);
            });

            // swipe support
            let startX = 0;
            previewImg.addEventListener('touchstart', (e) => { startX = e.touches[0].clientX; }, { passive: true });
            previewImg.addEventListener('touchend', (e) => {
                const dx = e.changedTouches[0].clientX - startX;
                if (Math.abs(dx) > 40) {
                    if (dx > 0) show(current - 1);
                    else show(current + 1);
                }
            });
        });
    </script>
    @if ($errors->any() && session('form') === 'animal-edit')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const modalEl = document.getElementById('animalEditModal');
                if (modalEl) {
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            });
        </script>
    @endif
    @if ($errors->has('price') || $errors->has('sold_at') || $errors->has('reserver_name') || $errors->has('deposit_amount') || $errors->has('reservation_valid_until') || $errors->has('notes'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const modalEl = document.getElementById('offerEditModal');
                if (modalEl) {
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            });
        </script>
    @endif
@endpush


