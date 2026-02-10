<details class="relative">
    <summary
        class="flex cursor-pointer list-none items-center justify-center rounded-full border border-slate-200 bg-white p-2 text-slate-600 hover:border-slate-300 hover:text-slate-900 [&::-webkit-details-marker]:hidden"
        aria-label="Ustawienia"
    >
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h3M7.2 3.6l2.1 2.1M16.8 3.6l-2.1 2.1M3.6 7.2l2.1 2.1M20.4 7.2l-2.1 2.1M3 12h3M18 12h3M3.6 16.8l2.1-2.1M20.4 16.8l-2.1-2.1M7.2 20.4l2.1-2.1M16.8 20.4l-2.1-2.1M10.5 18h3" />
        </svg>
    </summary>
    <div class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-2 text-sm shadow-lg">
        <a
            href="{{ route('admin.settings.index') }}"
            class="block rounded-lg px-3 py-2 text-slate-700 hover:bg-slate-100"
        >
            Administracja
        </a>
        <div class="my-2 border-t border-slate-100"></div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="block w-full rounded-lg px-3 py-2 text-left text-slate-700 hover:bg-slate-100" type="submit">
                Wyloguj
            </button>
        </form>
    </div>
</details>
