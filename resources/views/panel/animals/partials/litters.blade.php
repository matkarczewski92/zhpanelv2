<div class="card cardopacity mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span>Mioty</span>
        <span class="text-muted small">{{ $profile->littersCount }} wpisów</span>
    </div>
    <div class="card-body">
        @if (count($profile->littersAsParent))
            <div class="litter-chips">
                @foreach ($profile->littersAsParent as $litter)
                    @php $chipClasses = 'litter-chip litter-chip--' . $litter['category_code']; @endphp
                    @if (!empty($litter['url']) && $litter['url'] !== '#')
                        <a
                            class="{{ $chipClasses }}"
                            href="{{ $litter['url'] }}"
                            title="{{ $litter['title'] }}"
                        >
                            {{ $litter['code'] }}
                        </a>
                    @else
                        <span class="{{ $chipClasses }}" title="{{ $litter['title'] }}">
                            {{ $litter['code'] }}
                        </span>
                    @endif
                @endforeach
            </div>
        @else
            <div class="text-muted small">Brak danych o miotach.</div>
        @endif
    </div>
</div>
