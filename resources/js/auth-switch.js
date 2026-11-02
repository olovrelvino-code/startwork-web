const employerPanel = document.querySelector('[data-employer-auth]');
const jobseekerPanel = document.querySelector('[data-jobseeker-auth]');
const roleButtons = document.querySelectorAll('[data-auth-role]');

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
            showStatus('');
        } catch (error) {
            if (currentRequest === requestId) showStatus(error.message || 'Koneksi terputus. Coba lagi.', true);
        } finally {
            if (currentRequest === requestId) employerPanel.removeAttribute('aria-busy');
        }
    }

    function selectRole(role) {
        const isEmployer = role === 'employer';
        jobseekerPanel.hidden = isEmployer;
        jobseekerPanel.classList.toggle('hidden', isEmployer);
        employerPanel.hidden = !isEmployer;
        employerPanel.classList.toggle('hidden', !isEmployer);
        roleButtons.forEach((button) => {
            const selected = button.dataset.authRole === role;
            button.setAttribute('aria-pressed', String(selected));
            button.classList.toggle('bg-white', selected);
            button.classList.toggle('text-blue-700', selected);
            button.classList.toggle('shadow-sm', selected);
            button.classList.toggle('text-slate-600', !selected);
        });
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

    employerPanel.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.action !== loginUrl) return;
        event.preventDefault();
        event.stopPropagation();
        if (form.dataset.submitting) return;
        form.dataset.submitting = 'true';
        const button = form.querySelector('[type="submit"]');
        const label = button?.textContent;
        if (button) {
            button.disabled = true;
            button.textContent = 'Memproses…';
        }
        showStatus('');

        try {
            const response = await fetch(form.action, {
                method: 'POST', credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: new URLSearchParams(new FormData(form)),
            });
            if (!response.ok) {
                const result = await response.json().catch(() => ({}));
                showStatus(result.errors?.email?.join(' ') || result.message || 'Login gagal. Periksa kembali data Anda.', true);
                return;
            }
            await showEmployerPage(response.url, response);
        } catch {
            showStatus('Koneksi terputus. Coba masuk kembali.', true);
        } finally {
            delete form.dataset.submitting;
            if (button) {
                button.disabled = false;
                button.textContent = label;
            }
        }
    });

    employerPanel.addEventListener('employer:before-navigate', (event) => {
        if (event.detail.destination !== setupUrl) return;
        event.preventDefault();
        showEmployerPage(setupUrl);
    });
}
