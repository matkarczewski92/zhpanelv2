import { Toast } from "bootstrap";

const container = document.getElementById('globalToastContainer');
if (container) {
    const toasts = container.dataset.toasts ? JSON.parse(container.dataset.toasts) : [];
    const queue = Array.isArray(toasts) ? toasts : [];

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

    queue.forEach((toast) => {
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
    });
}
