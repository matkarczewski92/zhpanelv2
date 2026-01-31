<div class="card cardopacity mb-3" id="genetyka">
    <div class="card-header">Genetyka</div>
    <div class="card-body">
        <div id="genotypeChips">
            @include('panel.animals.partials.genotype-chips', ['chips' => $profile->genotypeChips])
        </div>

        <form method="POST" action="{{ route('panel.animals.genotypes.store', $profile->animal['id']) }}" class="row g-2 align-items-end genotype-add-row" id="genotypeAddForm">
            @csrf
            <div class="col-12 col-md-6">
                <label class="form-label" for="genotype_category">Genotyp</label>
                <select id="genotype_category" name="genotype_id" class="form-select form-select-sm" required>
                    @foreach ($profile->genotypeCategoryOptions as $option)
                        <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-8 col-md-4">
                <label class="form-label" for="genotype_type">Typ</label>
                <select id="genotype_type" name="type" class="form-select form-select-sm" required>
                    @foreach ($profile->genotypeTypeOptions as $option)
                        <option value="{{ $option['code'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-4 col-md-2 d-grid">
                <label class="form-label d-none d-md-block">&nbsp;</label>
                <button class="btn btn-primary btn-sm" type="submit">Dodaj</button>
            </div>
        </form>
    </div>
</div>

{{-- Confirm delete modal --}}
<div class="modal fade" id="genotypeDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content photobg">
            <div class="modal-header">
                <h5 class="modal-title">Usuń genotyp</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Zamknij"></button>
            </div>
            <form method="POST" id="genotypeDeleteForm">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    Czy na pewno chcesz usunąć ten genotyp?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-danger">Usuń</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080; pointer-events: none;">
    <div id="panelToastContainer"></div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const deleteModal = document.getElementById('genotypeDeleteModal');
            const deleteForm = document.getElementById('genotypeDeleteForm');
            const chipsWrapper = document.getElementById('genotypeChips');
            const addForm = document.getElementById('genotypeAddForm');
            const toastContainer = document.getElementById('panelToastContainer');

            const ensureBootstrap = () =>
                window.bootstrap
                    ? Promise.resolve(window.bootstrap)
                    : import('https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js').then(() => window.bootstrap);

            const bindDeleteButtons = () => {
                document.querySelectorAll('[data-genotype-delete]').forEach((btn) => {
                    btn.onclick = async () => {
                        const url = btn.getAttribute('data-genotype-delete');
                        if (deleteForm && url) {
                            deleteForm.setAttribute('action', url);
                        }
                        const bs = await ensureBootstrap();
                        const modal = new bs.Modal(deleteModal);
                        modal.show();
                    };
                });
            };

            const showToast = async (message, variant = 'success') => {
                const bs = await ensureBootstrap();
                const toastEl = document.createElement('div');
                toastEl.className = `toast align-items-center text-bg-${variant} border-0`;
                toastEl.role = 'alert';
                toastEl.ariaLive = 'assertive';
                toastEl.ariaAtomic = 'true';
                toastEl.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                `;
                toastContainer?.appendChild(toastEl);
                const toast = new bs.Toast(toastEl, { delay: 5000 });
                toast.show();
                toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
            };

            const refreshChips = (html) => {
                if (!chipsWrapper) return;
                chipsWrapper.innerHTML = html;
                bindDeleteButtons();
            };

            if (addForm) {
                addForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const formData = new FormData(addForm);
                    const response = await fetch(addForm.action, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData,
                    });
                    const data = await response.json();
                    if (response.ok) {
                        refreshChips(data.chips_html || '');
                        showToast(data.message || 'Dodano genotyp');
                    } else {
                        showToast(data.message || 'Błąd', 'danger');
                    }
                });
            }

            if (deleteForm) {
                deleteForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const response = await fetch(deleteForm.action, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: new FormData(deleteForm),
                    });
                    const data = await response.json();
                    const bs = await ensureBootstrap();
                    bs.Modal.getInstance(deleteModal)?.hide();
                    if (response.ok) {
                        refreshChips(data.chips_html || '');
                        showToast(data.message || 'Usunięto genotyp');
                    } else {
                        showToast(data.message || 'Błąd', 'danger');
                    }
                });
            }

            bindDeleteButtons();
        });
    </script>
@endpush
