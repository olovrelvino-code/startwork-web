import './admin-article-editor.js';

const feedback = document.querySelector('#admin-feedback');

function showFeedback(message, type = 'error') {
    if (!feedback) return;

    feedback.className = type === 'success'
        ? 'mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900'
        : 'mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900';
    feedback.setAttribute('role', type === 'error' ? 'alert' : 'status');
    feedback.textContent = message;
    feedback.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function offerLoginLink() {
    if (!feedback) return;
    const link = document.createElement('a');
    link.href = '/login';
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    link.className = 'ml-2 font-semibold underline underline-offset-2';
    link.textContent = 'Buka login';
    feedback.append(link);
}

const lastMessage = sessionStorage.getItem('admin-success-message');
if (lastMessage) {
    sessionStorage.removeItem('admin-success-message');
    showFeedback(lastMessage, 'success');
}

function clearFieldErrors(form) {
    form.querySelectorAll('[data-admin-field-error]').forEach((node) => node.remove());
    form.querySelectorAll('[aria-invalid="true"]').forEach((field) => {
        field.removeAttribute('aria-invalid');
        field.removeAttribute('aria-describedby');
    });
}

document.addEventListener('change', (event) => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || !input.matches('[data-admin-image-input]')) return;

    const preview = input.parentElement.querySelector('[data-admin-preview]');
    if (!(preview instanceof HTMLImageElement)) return;
    if (preview.dataset.objectUrl) URL.revokeObjectURL(preview.dataset.objectUrl);

    const file = input.files?.[0];
    if (!file) {
        preview.classList.add('hidden');
        return;
    }
    if (!['image/jpeg', 'image/png'].includes(file.type) || file.size > 2 * 1024 * 1024) {
        input.value = '';
        preview.classList.add('hidden');
        showFeedback('Pilih gambar JPG atau PNG dengan ukuran maksimal 2 MB.');
        return;
    }

    preview.dataset.objectUrl = URL.createObjectURL(file);
    preview.src = preview.dataset.objectUrl;
    preview.classList.remove('hidden');
});

function showFieldErrors(form, errors) {
    let firstField = null;

    for (const [name, messages] of Object.entries(errors)) {
        const field = form.elements.namedItem(name);
        if (!(field instanceof HTMLElement)) continue;

        const error = document.createElement('p');
        error.dataset.adminFieldError = '';
        error.className = 'mt-1 text-sm text-rose-700';
        error.textContent = messages.join(' ');
        error.id = `${field.id || name}-error`;
        const target = name === 'body_json' ? form.querySelector('[data-article-editor]')
            : name === 'category' ? form.querySelector('[data-category-search]') : field;
        (target || field).setAttribute('aria-invalid', 'true');
        (target || field).setAttribute('aria-describedby', error.id);
        if (name === 'body_json' && target) {
            target.prepend(error);
        } else {
            (target || field).insertAdjacentElement('afterend', error);
        }
        firstField ??= target || field;
    }

    firstField?.focus();
}

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('[data-admin-form]')) return;

    event.preventDefault();
    clearFieldErrors(form);

    const submitButton = form.querySelector('[type="submit"]');
    const originalLabel = submitButton?.textContent;
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = form.dataset.method === 'DELETE' ? 'Menghapus…' : 'Menyimpan…';
    }

    const method = form.dataset.method || 'POST';
    const formData = new FormData(form);
    const expiresAt = form.querySelector('input[name="expires_at"][type="datetime-local"]');
    if (expiresAt?.value) formData.set('expires_at', expiresAt.value + '+07:00');
    const isMultipart = form.enctype === 'multipart/form-data';
    const headers = {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
    };

    if (method === 'DELETE') formData.delete('_token');
    if (isMultipart && method === 'PUT') formData.set('_method', 'PUT');
    let body;
    if (method !== 'DELETE') {
        body = isMultipart ? formData : new URLSearchParams(formData);
        if (!isMultipart) headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8';
    }

    try {
        const response = await fetch(form.action, {
            method: isMultipart && method === 'PUT' ? 'POST' : method,
            headers,
            credentials: 'same-origin',
            body,
        });
        const result = response.status === 204 ? {} : await response.json().catch(() => ({}));

        if (!response.ok) {
            if (response.status === 422 && result.errors) {
                showFieldErrors(form, result.errors);
                showFeedback('Periksa kembali kolom yang ditandai. Data belum disimpan.');
            } else if (response.status === 419) {
                showFeedback('Sesi berakhir. Buka halaman login di tab baru, lalu kembali ke formulir ini dan coba lagi.');
                offerLoginLink();
            } else if (response.status === 401) {
                showFeedback('Sesi login tidak aktif. Masuk kembali sebelum melanjutkan.');
                offerLoginLink();
            } else {
                showFeedback(result.message || `Permintaan gagal (${response.status}). Periksa data dan coba lagi.`);
            }
            return;
        }

        const destination = form.dataset.successUrl?.replace('{id}', result.data?.id || '');
        const message = form.dataset.successMessage || result.message || 'Perubahan berhasil disimpan.';
        if (destination) {
            sessionStorage.setItem('admin-success-message', message);
            window.location.assign(destination);
        } else {
            showFeedback(message, 'success');
        }
    } catch {
        showFeedback('Koneksi terputus. Perubahan belum dapat dipastikan; muat ulang data sebelum mencoba lagi.');
    } finally {
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = originalLabel;
        }
    }
});
