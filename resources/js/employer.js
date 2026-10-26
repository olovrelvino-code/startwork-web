const feedback = document.querySelector('#employer-feedback');

function showFeedback(message) {
    if (!feedback) return;
    feedback.className = 'mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900';
    feedback.setAttribute('role', 'alert');
    feedback.textContent = message;
    feedback.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

const savedMessage = sessionStorage.getItem('employer-success-message');
if (savedMessage) {
    sessionStorage.removeItem('employer-success-message');
    if (feedback) {
        feedback.className = 'mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900';
        feedback.setAttribute('role', 'status');
        feedback.textContent = savedMessage;
    }
}

function clearErrors(form) {
    form.querySelectorAll('[data-employer-field-error]').forEach((node) => node.remove());
    form.querySelectorAll('[aria-invalid="true"]').forEach((field) => {
        field.removeAttribute('aria-invalid');
        field.removeAttribute('aria-describedby');
    });
}

function showErrors(form, errors) {
    let firstField = null;
    for (const [name, messages] of Object.entries(errors)) {
        const field = form.elements.namedItem(name);
        if (!(field instanceof HTMLElement)) continue;
        const error = document.createElement('p');
        error.dataset.employerFieldError = '';
        error.id = `${field.id || name}-error`;
        error.className = 'mt-1 text-sm text-rose-700';
        error.textContent = messages.join(' ');
        field.setAttribute('aria-invalid', 'true');
        field.setAttribute('aria-describedby', error.id);
        field.insertAdjacentElement('afterend', error);
        firstField ??= field;
    }
    firstField?.focus();
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

document.addEventListener('click', async (event) => {
    const link = event.target instanceof Element ? event.target.closest('[data-employer-download]') : null;
    if (!(link instanceof HTMLAnchorElement)) return;
    event.preventDefault();
    if (link.dataset.loading) return;

    link.dataset.loading = 'true';
    link.setAttribute('aria-busy', 'true');
    const originalLabel = link.textContent;
    link.textContent = 'Menyiapkan CV…';

    try {
        const response = await fetch(link.href, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
        if (!response.ok) {
            const result = await response.json().catch(() => ({}));
            showFeedback(result.message || `CV tidak dapat diunduh (${response.status}). Muat ulang halaman sebelum mencoba lagi.`);
            return;
        }

        const disposition = response.headers.get('Content-Disposition') || '';
        const encodedName = disposition.match(/filename\*=UTF-8''([^;]+)/i)?.[1];
        const plainName = disposition.match(/filename="?([^";]+)"?/i)?.[1];
        let filename = plainName || 'CV';
        if (encodedName) {
            try { filename = decodeURIComponent(encodedName); } catch { /* Keep the plain filename. */ }
        }

        const objectUrl = URL.createObjectURL(await response.blob());
        const download = document.createElement('a');
        download.href = objectUrl;
        download.download = filename;
        document.body.append(download);
        download.click();
        download.remove();
        setTimeout(() => URL.revokeObjectURL(objectUrl), 60000);
    } catch {
        showFeedback('Koneksi terputus. CV belum berhasil diunduh.');
    } finally {
        delete link.dataset.loading;
        link.removeAttribute('aria-busy');
        link.textContent = originalLabel;
    }
});

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('[data-employer-form]')) return;
    event.preventDefault();
    if (form.dataset.submitting) return;
    if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) return;
    clearErrors(form);
    form.dataset.submitting = 'true';

    const button = form.querySelector('[type="submit"]');
    const originalLabel = button?.textContent;
    if (button) {
        button.disabled = true;
        button.textContent = 'Memproses…';
    }

    const method = form.dataset.method || 'POST';
    const formData = new FormData(form);
    const expiresAt = form.querySelector('input[name="expires_at"][type="datetime-local"]');
    if (expiresAt?.value) formData.set('expires_at', expiresAt.value + '+07:00');
    const multipart = form.enctype === 'multipart/form-data';
    const headers = {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
    };
    if (multipart && method === 'PUT') formData.set('_method', 'PUT');
    const body = method === 'DELETE' ? undefined : multipart ? formData : new URLSearchParams(formData);
    if (!multipart && method !== 'DELETE') headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8';

    let navigating = false;
    try {
        const response = await fetch(form.action, {
            method: multipart && method === 'PUT' ? 'POST' : method,
            headers,
            credentials: 'same-origin',
            body,
        });
        const result = response.status === 204 ? {} : await response.json().catch(() => ({}));
        if (!response.ok) {
            if (response.status === 422 && result.errors) {
                showFeedback('Periksa kolom yang ditandai. Data belum disimpan.');
                showErrors(form, result.errors);
            } else if (response.status === 419) {
                showFeedback('Sesi berakhir. Muat ulang halaman, lalu masuk kembali bila diminta.');
            } else if (response.status === 401) {
                showFeedback('Sesi login tidak aktif. Masuk kembali sebelum melanjutkan.');
            } else if (response.status === 403) {
                showFeedback('Akun ini tidak memiliki akses ke tindakan tersebut.');
            } else if (response.status === 404 || response.status === 409) {
                showFeedback(`${result.message || 'Data telah berubah.'} Muat ulang halaman sebelum mencoba lagi.`);
            } else {
                showFeedback(result.message || `Permintaan gagal (${response.status}). Muat ulang data sebelum mencoba lagi.`);
            }
            return;
        }

        const destination = form.dataset.successUrl?.replace('{id}', result.data?.id || '');
        if (destination) {
            sessionStorage.setItem('employer-success-message', form.dataset.successMessage || result.message || 'Perubahan berhasil disimpan.');
            window.location.assign(destination);
            navigating = true;
        }
    } catch {
        showFeedback('Koneksi terputus. Perubahan belum dapat dipastikan; muat ulang data sebelum mencoba lagi.');
    } finally {
        if (!navigating) delete form.dataset.submitting;
        if (button && !navigating) {
            button.disabled = false;
            button.textContent = originalLabel;
        }
    }
});
