<div class="card cardopacity mb-3">
    <div class="card-header">Szczegóły</div>
    <div class="card-body">
        <dl class="row mb-0 small">
            @foreach ($profile->details as $item)
                <dt class="col-6 text-muted">{{ $item['label'] }}</dt>
                <dd class="col-6">
                    @if (!empty($item['value_html']))
                        {!! $item['value_html'] !!}
                    @else
                        {{ $item['value'] ?? '-' }}
                    @endif
                </dd>
            @endforeach
        </dl>
    </div>
</div>
