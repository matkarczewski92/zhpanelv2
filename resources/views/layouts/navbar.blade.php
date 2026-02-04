<nav class="navbar navbar-expand-xl navbar-dark fixed-top navbar-glass">
    <div class="container-fluid">
        <a class="navbar-brand fw-semibold" href="{{ route('panel.home') }}">ZH Panel</a>
        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#panelOffcanvas"
            aria-controls="panelOffcanvas"
            aria-label="Otw�rz menu"
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
                    <a class="nav-link {{ request()->routeIs('panel.finances.*') ? 'active' : '' }}" href="{{ route('panel.finances.index') }}">Finanse</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('panel.offers.*') ? 'active' : '' }}" href="{{ route('panel.offers.index') }}">Oferty</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('panel.massdata.*') ? 'active' : '' }}" href="{{ route('panel.massdata.index') }}">Masowe Dane</a>
                </li>
                {{-- placeholder for future links --}}
            </ul>

            <div class="dropdown ms-auto">
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
</nav>

<div class="offcanvas offcanvas-end text-bg-dark" tabindex="-1" id="panelOffcanvas" aria-labelledby="panelOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="panelOffcanvasLabel">ZH Panel</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Zamknij"></button>
    </div>
    <div class="offcanvas-body">
        <nav class="nav flex-column gap-1">
            <a
                class="nav-link {{ request()->routeIs('panel.home') ? 'active' : '' }}"
                href="{{ route('panel.home') }}"
                data-bs-dismiss="offcanvas"
            >
                Home
            </a>
            <a
                class="nav-link {{ request()->routeIs('panel.animals.*') ? 'active' : '' }}"
                href="{{ route('panel.animals.index') }}"
                data-bs-dismiss="offcanvas"
            >
                Zwierz�ta
            </a>
            <a
                class="nav-link {{ request()->routeIs('panel.feeds.*') ? 'active' : '' }}"
                href="{{ route('panel.feeds.index') }}"
                data-bs-dismiss="offcanvas"
            >
                Karma
            </a>
            <a
                class="nav-link {{ request()->routeIs('panel.litters.*') ? 'active' : '' }}"
                href="{{ route('panel.litters.index') }}"
                data-bs-dismiss="offcanvas"
            >
                Mioty
            </a>
            <a
                class="nav-link {{ request()->routeIs('panel.finances.*') ? 'active' : '' }}"
                href="{{ route('panel.finances.index') }}"
                data-bs-dismiss="offcanvas"
            >
                Finanse
            </a>
            <a
                class="nav-link {{ request()->routeIs('panel.offers.*') ? 'active' : '' }}"
                href="{{ route('panel.offers.index') }}"
                data-bs-dismiss="offcanvas"
            >
                Oferty
            </a>
            <a
                class="nav-link {{ request()->routeIs('panel.massdata.*') ? 'active' : '' }}"
                href="{{ route('panel.massdata.index') }}"
                data-bs-dismiss="offcanvas"
            >
                Masowe Dane
            </a>
            <div class="border-top border-secondary my-2"></div>
            <a class="nav-link" href="{{ route('admin.settings.index') }}" data-bs-dismiss="offcanvas">
                Ustawienia portalu
            </a>
            <a class="nav-link" href="{{ route('admin.labels.print') }}" data-bs-dismiss="offcanvas">
                Drukowanie etykiet
            </a>
            <a class="nav-link" href="{{ route('admin.shipping-list.index') }}" data-bs-dismiss="offcanvas">
                Lista przewozowa
            </a>
            <a class="nav-link" href="{{ route('admin.pricelist.index') }}" data-bs-dismiss="offcanvas">
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
        })();
    </script>
@endpush
