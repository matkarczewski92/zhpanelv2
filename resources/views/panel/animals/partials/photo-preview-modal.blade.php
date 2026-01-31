<div class="modal fade" id="animalPhotoPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content photobg position-relative">
            <div class="modal-header border-0">
                <h5 class="modal-title">Podgląd zdjęcia</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <div class="modal-body d-flex justify-content-center align-items-center position-relative" style="min-height:60vh;">
                <button class="btn btn-outline-light position-absolute top-50 start-0 translate-middle-y" type="button" id="photoPreviewPrev" aria-label="Poprzednie">&#10094;</button>
                <img id="photoPreviewImg" src="" alt="" class="img-fluid" style="max-height:70vh; object-fit:contain;">
                <button class="btn btn-outline-light position-absolute top-50 end-0 translate-middle-y" type="button" id="photoPreviewNext" aria-label="Następne">&#10095;</button>
            </div>
        </div>
    </div>
</div>
