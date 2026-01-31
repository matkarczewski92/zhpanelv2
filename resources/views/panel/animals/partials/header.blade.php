<div class="profile-hero" style="--profile-hero-image: url('{{ $profile->animal['banner_url'] }}'); margin-top:-45px"></div>

<div class="profile-header-wrap mb-4" style="margin-top: -30px">
    <div class="card cardopacity profile-header">
        <div class="card-body d-flex flex-column flex-lg-row align-items-start align-items-lg-center gap-3">
            <button
                type="button"
                class="profile-avatar"
                data-bs-toggle="modal"
                data-bs-target="#photoModal"
            >
                @if ($profile->animal['avatar_url'])
                    <img src="{{ $profile->animal['avatar_url'] }}" alt="{{ $profile->animal['name'] }}" />
                @else
                    <span>{{ $profile->animal['avatar_initials'] }}</span>
                @endif
            </button>

            <div class="flex-grow-1">
                <h1 class="h4 mb-1 profile-name">
                    @if (!empty($profile->animal['second_name']))
                        <span class="profile-second-name">"{{ $profile->animal['second_name'] }}"</span>
                    @endif
                    <span class="profile-name-text">{!! $profile->animal['name_display_html'] !!}</span>
                </h1>
                <div class="profile-meta text-muted">
                    <span>{{ $profile->animal['type'] ?? '-' }}</span>
                    <span>{{ $profile->animal['category'] ?? '-' }}</span>
                    <span>{{ $profile->animal['sex_label'] }}</span>
                </div>
            </div>

            <div class="d-none d-lg-flex align-items-center gap-2 flex-wrap">
                @foreach ($profile->actions as $action)
                    @php $isModal = $action['type'] === 'modal'; @endphp
                    @if($action['key'] === 'public-toggle')
                        <form method="POST" action="{{ $profile->toggle_public_profile_url }}" class="d-inline">
                            @csrf
                            <button type="submit" class="profile-public-toggle-btn" title="Przełącz profil publiczny">
                                <span class="profile-action-icon" style="color: {{ $profile->is_public_profile_enabled ? 'limegreen' : '#7a7a7a' }};">
                                    @if($profile->is_public_profile_enabled)
                                        <i class="bi bi-eye-fill"></i>
                                    @else
                                        <i class="bi bi-eye-slash-fill"></i>
                                    @endif
                                </span>
                            </button>
                        </form>
                        @continue
                    @endif
                    <a
                        class="btn profile-action-btn"
                        href="{{ $action['href'] }}"
                        @if ($isModal)
                            data-bs-toggle="modal"
                            data-bs-target="{{ $action['href'] }}"
                        @endif
                        title="{{ $action['label'] }}"
                        @if(isset($action['disabled']) && $action['disabled']) style="pointer-events:none; opacity:0.5;" @endif
                    >
                        <span class="profile-action-icon" @if(isset($action['color'])) style="color: {{ $action['color'] }};" @endif>
                            @if($action['key'] === 'public')
                                <i class="bi bi-person-circle"></i>
                            @else
                                {!! $action['icon'] !!}
                            @endif
                        </span>
                        <span class="d-none d-xl-inline">{{ $action['label'] }}</span>
                    </a>
                @endforeach
            </div>

            <div class="d-lg-none">
                <button
                    class="btn profile-action-toggle"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#actionsOffcanvas"
                    aria-controls="actionsOffcanvas"
                >
                    Akcje
                </button>
            </div>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-bottom text-bg-dark" tabindex="-1" id="actionsOffcanvas" aria-labelledby="actionsOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="actionsOffcanvasLabel">Akcje</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Zamknij"></button>
    </div>
    <div class="offcanvas-body">
        <div class="d-grid gap-2">
            @foreach ($profile->actions as $action)
                @php $isModal = $action['type'] === 'modal'; @endphp
                <a
                    class="btn profile-action-btn w-100 text-start"
                    href="{{ $action['href'] }}"
                    @if ($isModal)
                        data-bs-toggle="modal"
                        data-bs-target="{{ $action['href'] }}"
                    @endif
                    data-bs-dismiss="offcanvas"
                >
                    <span class="profile-action-icon">{!! $action['icon'] !!}</span>
                    {{ $action['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</div>
