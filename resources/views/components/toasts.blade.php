@php
    $toasts = [];
    if (session('toast')) {
        $toast = session('toast');
        if (is_array($toast) && isset($toast['message'])) {
            $toasts[] = [
                'message' => $toast['message'],
                'type' => $toast['type'] ?? 'info',
            ];
        } elseif (is_string($toast)) {
            $toasts[] = ['message' => $toast, 'type' => 'info'];
        }
    }
    foreach (['success', 'error', 'warning', 'info'] as $key) {
        if (session($key)) {
            $toasts[] = ['message' => session($key), 'type' => $key === 'error' ? 'danger' : $key];
        }
    }
@endphp

<div
    class="toast-container position-fixed bottom-0 end-0 p-3"
    style="z-index: 1090;"
    data-toasts='@json($toasts)'
    id="globalToastContainer"
></div>
