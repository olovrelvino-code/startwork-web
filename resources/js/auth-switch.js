const employerPanel = document.querySelector('[data-employer-auth]');
const jobseekerPanel = document.querySelector('[data-jobseeker-auth]');
const roleVisuals = document.querySelectorAll('[data-role-visual]');
const roleButtons = document.querySelectorAll('[data-auth-role]');

function clearFieldError(form, name) {
    const input = form.elements.namedItem(name);
    const error = form.querySelector(`[data-auth-error="${name}"]`);
    const warning = form.querySelector(`[data-auth-warning="${name}"]`);
    input?.removeAttribute('aria-invalid');
    if (error) {
        error.textContent = '';
        error.hidden = true;
        error.classList.add('hidden');
    }
    warning?.classList.add('hidden');
}

function clearLoginErrors(form) {
    clearFieldError(form, 'email');
    clearFieldError(form, 'password');
    const feedback = form.querySelector('[data-auth-feedback]');
    if (feedback) {
        feedback.textContent = '';
        feedback.hidden = true;
        feedback.classList.add('hidden');
    }
}

function showFieldError(form, name, message) {
    const input = form.elements.namedItem(name);
    const error = form.querySelector(`[data-auth-error="${name}"]`);
    const warning = form.querySelector(`[data-auth-warning="${name}"]`);
    if (!input || !error) return;
    input.setAttribute('aria-invalid', 'true');
    error.textContent = message;
    error.hidden = false;
    error.classList.remove('hidden');
    warning?.classList.remove('hidden');
}

function showLoginFeedback(form, message) {
    const feedback = form.querySelector('[data-auth-feedback]');
    if (!feedback) return;
    feedback.textContent = message;
    feedback.hidden = false;
    feedback.classList.remove('hidden');
}

async function submitLogin(event, form) {
    event.preventDefault();
    event.stopPropagation();
    if (form.dataset.submitting) return;
    clearLoginErrors(form);

    const email = form.elements.namedItem('email');
    const password = form.elements.namedItem('password');
    email.value = email.value.trim();
    let valid = true;
    if (!email.value) {
        showFieldError(form, 'email', 'Email wajib diisi');
        valid = false;
    } else if (email.validity.typeMismatch) {
        showFieldError(form, 'email', 'Format email tidak valid');
        valid = false;
    }
    if (!password.value) {
        showFieldError(form, 'password', 'Password wajib diisi');
        valid = false;
    }
    if (!valid) return;

    form.dataset.submitting = 'true';
    form.setAttribute('aria-busy', 'true');
    const button = form.querySelector('[type="submit"]');
    const label = button?.dataset.idleLabel || button?.textContent.trim();
    if (button) {
        button.disabled = true;
        button.textContent = button.dataset.loadingLabel || 'Memproses…';
    }

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: new URLSearchParams(new FormData(form)),
        });
        const result = await response.json().catch(() => null);
        if (response.ok && response.redirected) {
            clearLoginErrors(form);
            password.value = '';
            window.location.assign(response.url);
            return;
        }
        if (response.status === 422 && result?.errors) {
            if (result.errors.email) showFieldError(form, 'password', 'Email atau password yang Anda masukkan salah');
            if (result.errors.password) showFieldError(form, 'password', 'Password wajib diisi');
            if (!result.errors.email && !result.errors.password) showLoginFeedback(form, 'Terjadi kesalahan. Silakan coba kembali');
        } else {
            showLoginFeedback(form, 'Terjadi kesalahan. Silakan coba kembali');
        }
        password.value = '';
    } catch {
        showLoginFeedback(form, 'Terjadi kesalahan. Silakan coba kembali');
        password.value = '';
    } finally {
        delete form.dataset.submitting;
        form.removeAttribute('aria-busy');
        if (button) {
            button.disabled = false;
            button.textContent = label;
        }
    }
}

function bindLoginForms(root = document) {
    root.querySelectorAll('[data-auth-login-form]').forEach((form) => {
        if (form.dataset.authBound) return;
        form.dataset.authBound = 'true';
        form.noValidate = true;
        form.addEventListener('input', (event) => {
            if (event.target instanceof HTMLInputElement) clearLoginErrors(form);
        });
        form.addEventListener('submit', (event) => submitLogin(event, form));
    });
}

bindLoginForms();

if (employerPanel && jobseekerPanel && roleButtons.length === 2) {
    const content = employerPanel.querySelector('[data-employer-inline-content]');
    const status = employerPanel.querySelector('[data-employer-inline-status]');
    const loginUrl = employerPanel.dataset.loginUrl;
    const registerUrl = employerPanel.dataset.registerUrl;
    const setupUrl = employerPanel.dataset.setupUrl;
    let requestId = 0;

    function showStatus(message, error = false) {
        status.textContent = message;
        status.className = message
            ? `mb-5 rounded-lg border px-4 py-3 text-sm ${error ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-slate-200 bg-slate-50 text-slate-700'}`
            : 'hidden';
        status.setAttribute('role', error ? 'alert' : 'status');
    }

    async function showEmployerPage(url, existingResponse = null) {
        const currentRequest = ++requestId;
        employerPanel.setAttribute('aria-busy', 'true');
        showStatus('Memuat formulir Employer…');

        try {
            const response = existingResponse || await fetch(url, {
                headers: { Accept: 'text/html' }, credentials: 'same-origin',
            });
            if (!response.ok) throw new Error('Formulir belum dapat dimuat. Coba lagi.');
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            if (currentRequest !== requestId) return;

            const fragment = page.querySelector('[data-employer-auth-content]');
            if (!fragment) {
                const destination = new URL(response.url);
                if (response.redirected && destination.origin === location.origin && destination.pathname.startsWith('/employer/')) {
                    location.assign(destination.href);
                    return;
                }
                throw new Error('Sesi berubah. Muat ulang halaman untuk melanjutkan.');
            }

            const token = page.querySelector('meta[name="csrf-token"]')?.content;
            if (token) document.querySelector('meta[name="csrf-token"]').content = token;
            content.replaceChildren(...Array.from(fragment.childNodes, (node) => document.importNode(node, true)));
            bindLoginForms(content);
            showStatus('');
        } catch (error) {
            if (currentRequest === requestId) showStatus(error.message || 'Koneksi terputus. Coba lagi.', true);
        } finally {
            if (currentRequest === requestId) employerPanel.removeAttribute('aria-busy');
        }
    }

    function selectRole(role) {
        const isEmployer = role === 'employer';
        document.querySelectorAll('[data-auth-login-form]').forEach(clearLoginErrors);
        showStatus('');
        jobseekerPanel.hidden = isEmployer;
        jobseekerPanel.classList.toggle('hidden', isEmployer);
        employerPanel.hidden = !isEmployer;
        employerPanel.classList.toggle('hidden', !isEmployer);
        roleVisuals.forEach((visual) => {
            const selected = visual.dataset.roleVisual === role;
            visual.hidden = !selected;
            visual.classList.toggle('hidden', !selected);
        });
        roleButtons.forEach((button) => {
            const selected = button.dataset.authRole === role;
            button.setAttribute('aria-pressed', String(selected));
            button.classList.toggle('text-blue-700', selected);
            button.classList.toggle('text-slate-600', !selected);
        });
        document.querySelector('[data-role-switch]')?.setAttribute('data-role', role);
        if (isEmployer && !content.hasChildNodes()) showEmployerPage(loginUrl);
    }

    roleButtons.forEach((button) => button.addEventListener('click', () => selectRole(button.dataset.authRole)));

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (!link || link.origin !== location.origin) return;
        if (link.href === loginUrl && jobseekerPanel.contains(link)) {
            event.preventDefault();
            selectRole('employer');
        } else if (employerPanel.contains(link) && (link.href === loginUrl || link.href === registerUrl)) {
            event.preventDefault();
            showEmployerPage(link.href);
        }
    });

    employerPanel.addEventListener('employer:before-navigate', (event) => {
        if (event.detail.destination !== setupUrl) return;
        event.preventDefault();
        showEmployerPage(setupUrl);
    });
}
