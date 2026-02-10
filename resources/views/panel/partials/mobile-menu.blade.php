@php
    $isHome = request()->routeIs('panel.home');
    $isAnimals = request()->routeIs('panel.animals.*');
    $animalTypes = $animalTypes ?? collect();
@endphp

<div class="md:hidden">
    <div
        class="fixed inset-0 z-40 hidden bg-slate-900/40"
        data-mobile-menu-backdrop
        data-cloak
        aria-hidden="true"
    ></div>

    <div
        id="mobile-menu"
        class="fixed inset-x-0 top-14 z-50 hidden border-b border-slate-200 bg-white shadow-lg"
        data-mobile-menu
        data-cloak
    >
        <div class="px-4 py-4">
            <nav class="space-y-2 text-sm font-medium">
                <a
                    href="{{ route('panel.home') }}"
                    class="{{ $isHome ? 'bg-slate-100 text-slate-900' : 'text-slate-600' }} block rounded-lg px-3 py-2 hover:bg-slate-100 hover:text-slate-900"
                >
                    Home
                </a>
                <a
                    href="{{ route('panel.animals.index') }}"
                    class="{{ $isAnimals ? 'bg-slate-100 text-slate-900' : 'text-slate-600' }} block rounded-lg px-3 py-2 hover:bg-slate-100 hover:text-slate-900"
                >
                    Zwierzęta
                </a>
                @if ($animalTypes->isNotEmpty())
                    <div class="ml-3 space-y-1 border-l border-slate-200 pl-3 text-xs text-slate-500">
                        @foreach ($animalTypes as $type)
                            <a
                                href="{{ route('panel.animals.index', ['type_id' => $type->id]) }}"
                                class="block rounded-md px-2 py-1 text-slate-600 hover:bg-slate-100 hover:text-slate-900"
                            >
                                {{ $type->name }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </nav>

            <div class="mt-4 border-t border-slate-200 pt-4">
                <a
                    href="{{ route('admin.settings.index') }}"
                    class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900"
                >
                    Ustwienia
                </a>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button
                        type="submit"
                        class="block w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900"
                    >
                        Wyloguj
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
