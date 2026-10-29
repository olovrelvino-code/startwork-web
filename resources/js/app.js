

import Alpine from 'alpinejs';
import './job-form-search.js';

window.Alpine = Alpine;

Alpine.start();

document.querySelectorAll('[data-auth-login-form]').forEach((form) => {
    const button = form.querySelector('[type="submit"]');
    if (!(button instanceof HTMLButtonElement)) return;
    const label = button.textContent;

    form.addEventListener('submit', () => {
        button.disabled = true;
        button.textContent = button.dataset.loadingLabel || label;
        form.setAttribute('aria-busy', 'true');
    });
    window.addEventListener('pageshow', () => {
        button.disabled = false;
        button.textContent = label;
        form.removeAttribute('aria-busy');
    });
});

const sessionIdentity = document.querySelector('meta[name="session-user-id"]');
if (sessionIdentity) {
    let checking = false;
    const clearFeedback = () => {
        sessionStorage.removeItem('admin-success-message');
        sessionStorage.removeItem('employer-success-message');
    };

    async function checkSessionIdentity() {
        if (checking) return;
        checking = true;

        try {
            const response = await fetch('/api/me', {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            if (response.status === 401) {
                clearFeedback();
                window.location.reload();
                return;
            }
            if (!response.ok) return;

            const { data } = await response.json();
            if (data?.id !== sessionIdentity.content || data?.role !== sessionIdentity.dataset.role) {
                clearFeedback();
                window.location.replace('/dashboard');
            }
        } catch {
            // The next server request still enforces the current session.
        } finally {
            checking = false;
        }
    }

    window.addEventListener('pageshow', checkSessionIdentity);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) checkSessionIdentity();
    });
    document.addEventListener('submit', (event) => {
        if (event.target instanceof HTMLFormElement && event.target.action === new URL('/logout', window.location.href).href) {
            clearFeedback();
        }
    });
}
