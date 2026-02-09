<style>
    .navbar-quick-search-group {
        min-width: 370px;
    }

    .navbar-quick-search-scope {
        max-width: 120px;
    }

    .navbar-quick-search-suggestions {
        background: rgba(33, 37, 41, 0.98);
        border-color: rgba(255, 255, 255, 0.18);
    }

    .navbar-quick-search-suggestions .dropdown-item {
        color: #f8f9fa;
    }

    .navbar-quick-search-suggestions .dropdown-item:hover {
        background: rgba(13, 110, 253, 0.28);
        color: #fff;
    }
</style>

<nav class="navbar navbar-expand-xl navbar-dark fixed-top navbar-glass">
    <div class="container-fluid">
        <a class="navbar-brand fw-semibold" href="{{ route('panel.home') }}">ZH Panel</a>
        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#panelOffcanvas"
            aria-controls="panelOffcanvas"
            aria-label="Otwórz menu"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="d-none d-xl-flex w-100 align-items-center">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('panel.home') ? 'active' : '' }}" href="{{ route('panel.home') }}">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('panel.animals.*') ? 'active' : '' }}" href="{{ route('panel.animals.index') }}">Zwierzęta</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('panel.feeds.*') ? 'active' : '' }}" href="{{ route('panel.feeds.index') }}">Karma</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('panel.litters.*') ? 'active' : '' }}" href="{{ route('panel.litters.index') }}">Mioty</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('panel.litters-planning.*') ? 'active' : '' }}" href="{{ route('panel.litters-planning.index') }}">Planowanie miotow</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('panel.finances.*') ? 'active' : '' }}" href="{{ route('panel.finances.index') }}">Finanse</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('panel.offers.*') ? 'active' : '' }}" href="{{ route('panel.offers.index') }}">Oferty</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('panel.massdata.*') ? 'active' : '' }}" href="{{ route('panel.massdata.index') }}">Masowe Dane</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('panel.devices.*') ? 'active' : '' }}" href="{{ route('panel.devices.index') }}">Urządzenia</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('web.home') }}">Web</a>
                </li>
                {{-- placeholder for future links --}}
            </ul>

            @php
                $animalNav = $animalNav ?? [];
                $hasAnimalNav = request()->routeIs('panel.animals.show') && !empty($animalNav);
            @endphp

            <div class="d-flex align-items-center gap-2 ms-auto">
                @if ($hasAnimalNav)
                    @if (!empty($animalNav['back_url']))
                        <a class="btn btn-outline-light btn-sm" href="{{ $animalNav['back_url'] }}" title="Powrót do listy">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                    @endif
                    <a
                        class="btn btn-outline-light btn-sm @if (empty($animalNav['prev_url'])) disabled @endif"
                        href="{{ $animalNav['prev_url'] ?? '#' }}"
                        title="Poprzedni wąż"
                    >
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    <span class="small text-light-emphasis">
                        {{ $animalNav['position'] ?? 0 }}/{{ $animalNav['total'] ?? 0 }}
                    </span>
                    <a
                        class="btn btn-outline-light btn-sm @if (empty($animalNav['next_url'])) disabled @endif"
                        href="{{ $animalNav['next_url'] ?? '#' }}"
                        title="Następny wąż"
                    >
                        <i class="bi bi-chevron-right"></i>
                    </a>
                @endif

                @php
                    $navbarSearchScope = request()->query('scope', 'id');
                    if (!in_array($navbarSearchScope, ['id', 'public_tag', 'litter_id', 'litter_code'], true)) {
                        $navbarSearchScope = 'id';
                    }
                    $navbarSearchQuery = trim((string) request()->query('q', ''));
                @endphp

                <form
                    method="GET"
                    action="{{ route('panel.navbar-search.go') }}"
                    class="position-relative"
                    id="navbarQuickSearchForm"
                    autocomplete="off"
                >
                    <div class="input-group input-group-sm navbar-quick-search-group">
                        <select class="form-select navbar-quick-search-scope" name="scope" data-role="navbar-quick-search-scope" aria-label="Zakres wyszukiwania">
                            <option value="id" @selected($navbarSearchScope === 'id')>ID</option>
                            <option value="public_tag" @selected($navbarSearchScope === 'public_tag')>Public tag</option>
                            <option value="litter_id" @selected($navbarSearchScope === 'litter_id')>ID Miotu</option>
                            <option value="litter_code" @selected($navbarSearchScope === 'litter_code')>Kod miotu</option>
                        </select>
                        <input
                            type="text"
                            class="form-control"
                            name="q"
                            value="{{ $navbarSearchQuery }}"
                            placeholder="Wyszukaj"
                            data-role="navbar-quick-search-input"
                        >
                    </div>
                    <div
                        class="dropdown-menu p-0 mt-1 w-100 overflow-auto navbar-quick-search-suggestions"
                        data-role="navbar-quick-search-suggestions"
                        style="max-height: 320px;"
                    ></div>
                </form>

                <div class="dropdown">
                <button
                    id="adminMenuToggle"
                    class="btn btn-link text-light p-0"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="Admin menu"
                >
                    <i class="bi bi-gear" style="font-size: 1.1rem;"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" id="adminMenuDropdown">
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.settings.index') }}">
                            Ustawienia portalu
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.labels.print') }}">
                            Drukowanie etykiet
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.labels.secret.print') }}">
                            Etykiety (sekret)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.shipping-list.index') }}">
                            Lista przewozowa
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.pricelist.index') }}">
                            Cennik
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item" type="submit">Wyloguj</button>
                        </form>
                    </li>
                </ul>
            </div>
            </div>
        </div>
    </div>
</nav>

<div class="offcanvas offcanvas-end text-bg-dark panel-mobile-nav" tabindex="-1" id="panelOffcanvas" aria-labelledby="panelOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="panelOffcanvasLabel">ZH Panel</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Zamknij"></button>
    </div>
    <div class="offcanvas-body">
        <nav class="nav flex-column gap-1">
            <a
                class="nav-link {{ request()->routeIs('panel.home') ? 'active' : '' }}"
                href="{{ route('panel.home') }}"
            >
                Home
            </a>
            <a
                class="nav-link {{ request()->routeIs('panel.animals.*') ? 'active' : '' }}"
                href="{{ route('panel.animals.index') }}"
            >
                Zwierzęta
            </a>
            <a
                class="nav-link {{ request()->routeIs('panel.feeds.*') ? 'active' : '' }}"
                href="{{ route('panel.feeds.index') }}"
            >
                Karma
            </a>
            <a
                class="nav-link {{ request()->routeIs('panel.litters.*') ? 'active' : '' }}"
                href="{{ route('panel.litters.index') }}"
            >
                Mioty
            </a>
            <a
                class="nav-link {{ request()->routeIs('panel.litters-planning.*') ? 'active' : '' }}"
                href="{{ route('panel.litters-planning.index') }}"
            >
                Planowanie miotow
            </a>
            <a
                class="nav-link {{ request()->routeIs('panel.finances.*') ? 'active' : '' }}"
                href="{{ route('panel.finances.index') }}"
            >
                Finanse
            </a>
            <a
                class="nav-link {{ request()->routeIs('panel.offers.*') ? 'active' : '' }}"
                href="{{ route('panel.offers.index') }}"
            >
                Oferty
            </a>
            <a
                class="nav-link {{ request()->routeIs('panel.massdata.*') ? 'active' : '' }}"
                href="{{ route('panel.massdata.index') }}"
            >
                Masowe Dane
            </a>
            <a
                class="nav-link {{ request()->routeIs('panel.devices.*') ? 'active' : '' }}"
                href="{{ route('panel.devices.index') }}"
            >
                Urządzenia
            </a>
            <a
                class="nav-link"
                href="{{ route('web.home') }}"
            >
                Web
            </a>
            <div class="border-top border-secondary my-2"></div>
            <a class="nav-link" href="{{ route('admin.settings.index') }}">
                Ustawienia portalu
            </a>
            <a class="nav-link" href="{{ route('admin.labels.print') }}">
                Drukowanie etykiet
            </a>
            <a class="nav-link" href="{{ route('admin.labels.secret.print') }}">
                Etykiety (sekret)
            </a>
            <a class="nav-link" href="{{ route('admin.shipping-list.index') }}">
                Lista przewozowa
            </a>
            <a class="nav-link" href="{{ route('admin.pricelist.index') }}">
                Cennik
            </a>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button class="btn btn-outline-light w-100" type="submit">Wyloguj</button>
            </form>
        </nav>
    </div>
</div>
@push('scripts')
    <script>
        (() => {
            const initDropdowns = () => {
                if (!window.bootstrap || !window.bootstrap.Dropdown) {
                    return false;
                }
                document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach((el) => {
                    new window.bootstrap.Dropdown(el);
                });
                return true;
            };

            const fallback = () => {
                const toggle = document.getElementById('adminMenuToggle');
                const menu = document.getElementById('adminMenuDropdown');
                if (!toggle || !menu) return;
                toggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    menu.classList.toggle('show');
                    menu.style.position = 'absolute';
                });
                document.addEventListener('click', (e) => {
                    if (!menu.contains(e.target) && !toggle.contains(e.target)) {
                        menu.classList.remove('show');
                    }
                });
            };

            if (!initDropdowns()) {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js';
                script.onload = () => initDropdowns() || fallback();
                document.head.appendChild(script);
            } else {
                fallback();
            }

            const searchForm = document.getElementById('navbarQuickSearchForm');
            const searchInput = searchForm?.querySelector('[data-role="navbar-quick-search-input"]');
            const searchScope = searchForm?.querySelector('[data-role="navbar-quick-search-scope"]');
            const suggestions = searchForm?.querySelector('[data-role="navbar-quick-search-suggestions"]');
            const suggestUrl = @json(route('panel.navbar-search.suggest'));
            let requestId = 0;
            let debounceTimer = null;

            const closeSuggestions = () => {
                if (!suggestions) return;
                suggestions.classList.remove('show');
                suggestions.innerHTML = '';
            };

            const escapeHtml = (value) => String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const renderSuggestions = (items) => {
                if (!suggestions) return;
                if (!Array.isArray(items) || items.length === 0) {
                    closeSuggestions();
                    return;
                }

                suggestions.innerHTML = items.map((item) => {
                    const label = escapeHtml(item.label ?? '');
                    const subtitle = escapeHtml(item.subtitle ?? '');
                    const url = escapeHtml(item.url ?? '#');
                    return `
                        <a class="dropdown-item py-2 border-bottom border-secondary border-opacity-25" href="${url}">
                            <div class="fw-semibold">${label}</div>
                            ${subtitle ? `<div class="small text-light-emphasis">${subtitle}</div>` : ''}
                        </a>
                    `;
                }).join('');
                suggestions.classList.add('show');
            };

            const fetchSuggestions = async () => {
                if (!(searchInput instanceof HTMLInputElement) || !(searchScope instanceof HTMLSelectElement)) {
                    return;
                }
                const q = searchInput.value.trim();
                if (q.length < 1) {
                    closeSuggestions();
                    return;
                }

                requestId += 1;
                const currentRequestId = requestId;
                const params = new URLSearchParams({
                    scope: searchScope.value,
                    q,
                });

                try {
                    const res = await fetch(`${suggestUrl}?${params.toString()}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                        },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) {
                        closeSuggestions();
                        return;
                    }
                    const payload = await res.json();
                    if (currentRequestId !== requestId) {
                        return;
                    }
                    renderSuggestions(payload.items ?? []);
                } catch (_) {
                    closeSuggestions();
                }
            };

            const scheduleSuggestions = () => {
                if (debounceTimer) {
                    clearTimeout(debounceTimer);
                }
                debounceTimer = setTimeout(fetchSuggestions, 120);
            };

            searchInput?.addEventListener('input', scheduleSuggestions);
            searchInput?.addEventListener('focus', scheduleSuggestions);
            searchScope?.addEventListener('change', scheduleSuggestions);
            document.addEventListener('click', (event) => {
                if (!searchForm || !(event.target instanceof Node)) {
                    return;
                }
                if (!searchForm.contains(event.target)) {
                    closeSuggestions();
                }
            });
        })();
    </script>
@endpush
