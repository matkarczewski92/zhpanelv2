@php
    $isHome = request()->routeIs('panel.home');
    $isAnimals = request()->routeIs('panel.animals.*');
    $animalTypes = $animalTypes ?? collect();
@endphp

<nav class="sticky top-0 z-40 hidden h-16 items-center justify-between border-b border-slate-200 bg-white/95 px-6 backdrop-blur md:flex">
    <div class="flex items-center gap-8">
        <a href="{{ route('panel.home') }}" class="flex items-center gap-2 text-sm font-semibold text-slate-900">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-900 text-xs font-bold text-white">
                ZH
            </span>
            <span>ZHPANEL</span>
        </a>

        <div class="flex items-center gap-6 text-sm font-medium">
            <a
                href="{{ route('panel.home') }}"
                class="{{ $isHome ? 'border-b-2 border-blue-600 text-slate-900' : 'border-b-2 border-transparent text-slate-600 hover:text-slate-900' }} pb-1"
            >
                Home
            </a>

            @if ($animalTypes->isNotEmpty())
                <div class="group relative">
                    <a
                        href="{{ route('panel.animals.index') }}"
                        class="{{ $isAnimals ? 'border-b-2 border-blue-600 text-slate-900' : 'border-b-2 border-transparent text-slate-600 hover:text-slate-900' }} flex items-center gap-1 pb-1"
                    >
                        Zwierzęta
                        <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 10.94l3.71-3.71a.75.75 0 1 1 1.06 1.06l-4.24 4.24a.75.75 0 0 1-1.06 0L5.21 8.29a.75.75 0 0 1 .02-1.08z" clip-rule="evenodd" />
                        </svg>
                    </a>
                    <div
                        class="absolute left-0 mt-2 hidden w-56 rounded-xl border border-slate-200 bg-white p-2 text-sm shadow-lg group-hover:block"
                    >
                        <a
                            href="{{ route('panel.animals.index') }}"
                            class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900"
                        >
                            Wszystkie
                        </a>
                        @foreach ($animalTypes as $type)
                            <a
                                href="{{ route('panel.animals.index', ['type_id' => $type->id]) }}"
                                class="block rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900"
                            >
                                {{ $type->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                <a
                    href="{{ route('panel.animals.index') }}"
                    class="{{ $isAnimals ? 'border-b-2 border-blue-600 text-slate-900' : 'border-b-2 border-transparent text-slate-600 hover:text-slate-900' }} pb-1"
                >
                    Zwierzęta
                </a>
            @endif
        </div>
    </div>

    <div class="flex items-center gap-3">
        <span class="text-sm text-slate-600">Witaj, {{ auth()->user()->name }}</span>
        @include('panel.partials.admin-dropdown')
    </div>
</nav>
