import { Toast } from "bootstrap";

const container = document.getElementById('globalToastContainer');

const typeClass = (type) => {
    switch (type) {
        case 'success':
            return 'bg-success text-white';
        case 'danger':
        case 'error':
            return 'bg-danger text-white';
        case 'warning':
            return 'bg-warning text-dark';
        default:
            return 'bg-info text-white';
    }
};

const showToast = (toast) => {
    if (!container) {
        return;
    }

    const wrapper = document.createElement('div');
    wrapper.className = `toast align-items-center ${typeClass(toast.type)}`;
    wrapper.setAttribute('role', 'alert');
    wrapper.setAttribute('aria-live', 'assertive');
    wrapper.setAttribute('aria-atomic', 'true');
    wrapper.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${toast.message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    container.appendChild(wrapper);
    const bsToast = new Toast(wrapper, { delay: 5000, autohide: true });
    bsToast.show();
};

if (container) {
    const toasts = container.dataset.toasts ? JSON.parse(container.dataset.toasts) : [];
    const queue = Array.isArray(toasts) ? toasts : [];

    queue.forEach(showToast);

    document.addEventListener('app:toast', (event) => {
        const detail = event instanceof CustomEvent ? event.detail : null;

        if (!detail || typeof detail.message !== 'string') {
            return;
        }

        showToast({
            type: detail.type ?? 'info',
            message: detail.message,
        });
    });
}
