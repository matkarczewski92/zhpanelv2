<div class="card cardopacity mb-3" id="wintering">
    <div class="card-header">Zimowanie</div>
    <div class="card-body">
        @if ($profile->wintering['active'])
            <dl class="row mb-0 small">
                <dt class="col-6 text-muted">Etap</dt>
                <dd class="col-6">{{ $profile->wintering['stage'] ?? '-' }}</dd>
                <dt class="col-6 text-muted">Sezon</dt>
                <dd class="col-6">{{ $profile->wintering['season'] ?? '-' }}</dd>
                <dt class="col-6 text-muted">Plan start</dt>
                <dd class="col-6">{{ $profile->wintering['planned_start'] ?? '-' }}</dd>
                <dt class="col-6 text-muted">Plan koniec</dt>
                <dd class="col-6">{{ $profile->wintering['planned_end'] ?? '-' }}</dd>
                <dt class="col-6 text-muted">Start</dt>
                <dd class="col-6">{{ $profile->wintering['start'] ?? '-' }}</dd>
                <dt class="col-6 text-muted">Koniec</dt>
                <dd class="col-6">{{ $profile->wintering['end'] ?? '-' }}</dd>
                <dt class="col-6 text-muted">Uwagi</dt>
                <dd class="col-6">{{ $profile->wintering['notes'] ?? '-' }}</dd>
            </dl>
        @else
            <div class="text-muted small">Brak aktywnego zimowania.</div>
        @endif
    </div>
</div>
