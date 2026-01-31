<header class="flex h-14 items-center justify-between border-b border-slate-200 bg-white px-4 md:hidden">
    <a href="{{ route('panel.home') }}" class="flex items-center gap-2 text-sm font-semibold text-slate-900">
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-900 text-xs font-bold text-white">
            ZH
        </span>
        <span>ZHPANEL</span>
    </a>
    <button
        type="button"
        class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
        data-mobile-menu-toggle
        aria-expanded="false"
        aria-controls="mobile-menu"
    >
        <span class="sr-only">Otwórz menu</span>
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>
</header>
