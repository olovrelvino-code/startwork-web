import './auth-switch.js';

const feedback = document.querySelector('#employer-feedback');

const subscription = document.querySelector('[data-subscription]');
if (subscription) {
    const list = subscription.querySelector('[data-package-list]');
    const status = subscription.querySelector('[data-package-status]');
    const template = subscription.querySelector('[data-package-template]');
    const dialog = document.querySelector('[data-package-dialog]');
    const trialQueueDialog = document.querySelector('[data-trial-queue-dialog]');
    const benefits = { Basic: ['Tayang di website (15 hari)', '1x Instagram Story', '1x Feed (Simple Design)'], Standard: ['Tayang di website (30 hari)', '1x Feed (Excellent Design)', '2x Instagram Story', 'Featured Lowongan di Website (1 hari)'], Premium: ['Tayang di website (45 hari)', '1x Reels (Video)', '1x Feed', '3x Instagram Story', 'Featured Lowongan di Website (3 hari)'] };
    const currency = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
    let selectedButton;
    let selectedPackage;
    let checkingAvailability = false;
    const showQueueDialog = (message) => {
        if (dialog.open) dialog.close();
        trialQueueDialog.querySelector('[data-trial-queue-message]').textContent = message;
        trialQueueDialog.showModal();
    };
    fetch(subscription.dataset.packagesUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' }).then((response) => response.json()).then(({ data }) => {
        const plans = ['Basic', 'Standard', 'Premium'];
        const packages = data.filter((item) => plans.includes(item.name) && item.currency === 'IDR' && item.is_active).sort((a, b) => plans.indexOf(a.name) - plans.indexOf(b.name));
        if (!packages.length) { status.textContent = 'Belum ada paket aktif yang tersedia.'; return; }
        packages.forEach((item) => {
            const card = template.content.firstElementChild.cloneNode(true);
            card.querySelector('[data-package-name]').textContent = item.name;
            const price = currency.format(Number(item.price));
            const quota = `${item.quota} lowongan`;
            const duration = `${item.duration_days} hari`;
            card.querySelector('[data-package-price]').textContent = price;
            card.querySelector('[data-package-quota]').textContent = quota;
            card.querySelector('[data-package-duration]').textContent = duration;
            benefits[item.name].forEach((text) => { const row = document.createElement('li'); row.textContent = text; card.querySelector('[data-package-benefits]').append(row); });
            const button = card.querySelector('[data-select-package]');
            button.addEventListener('click', async () => {
                if (checkingAvailability) return;
                checkingAvailability = true;
                selectedButton = button;
                selectedPackage = item;
                button.disabled = true;
                try {
                    const response = await fetch(subscription.dataset.availabilityUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' });
                    const availability = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(availability.message || 'Status antrean gagal diperiksa.');
                    if (!availability.allowed) {
                        showQueueDialog(availability.message);
                        return;
                    }
                    dialog.querySelector('[data-confirm-name]').textContent = item.name;
                    dialog.querySelector('[data-confirm-price]').textContent = price;
                    dialog.querySelector('[data-confirm-quota]').textContent = quota;
                    dialog.querySelector('[data-confirm-duration]').textContent = duration;
                    const benefitList = dialog.querySelector('[data-confirm-benefits]');
                    benefitList.replaceChildren(...benefits[item.name].map((benefit) => { const row = document.createElement('li'); row.textContent = benefit; return row; }));
                    const confirmBtn = dialog.querySelector('[data-package-confirm]');
                    if (confirmBtn) {
                        confirmBtn.disabled = false;
                        confirmBtn.textContent = 'Konfirmasi';
                        delete confirmBtn.dataset.submitting;
                    }
                    dialog.showModal();
                } catch (error) {
                    showFeedback(error.message);
                } finally {
                    button.disabled = false;
                    checkingAvailability = false;
                }
            });
            list.append(card);
        });
        status.textContent = '';
    }).catch(() => { status.textContent = 'Katalog paket gagal dimuat. Muat ulang halaman untuk mencoba lagi.'; });
    dialog.querySelectorAll('[data-package-close]').forEach((button) => button.addEventListener('click', () => dialog.close()));
    const confirmButton = dialog.querySelector('[data-package-confirm]');
    confirmButton.addEventListener('click', async () => {
        if (confirmButton.disabled || confirmButton.dataset.submitting === 'true') return;
        confirmButton.disabled = true;
        confirmButton.dataset.submitting = 'true';
        confirmButton.textContent = 'Memproses…';
        const popup = window.open('', '_blank');
        try {
            const response = await fetch(subscription.dataset.purchaseUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    package_id: selectedPackage.id,
                    idempotency_key: generateUuid(),
                }),
            });
            const data = await response.json();
            if (response.status === 409) {
                popup?.close();
                showQueueDialog(data.message || 'Status antrean berubah. Periksa kembali sebelum membeli paket.');
                return;
            }
            if (!response.ok) throw new Error(data.message || 'Permintaan pembelian gagal.');
            if (popup) {
                popup.location.href = data.whatsapp_url;
                window.location.reload();
            } else {
                window.location.href = data.whatsapp_url;
            }
        } catch (error) {
            popup?.close();
            showFeedback(error.message);
            confirmButton.disabled = false;
            confirmButton.textContent = 'Konfirmasi';
            delete confirmButton.dataset.submitting;
        }
    });
    trialQueueDialog.querySelector('[data-trial-queue-close]').addEventListener('click', () => trialQueueDialog.close());
    dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
    dialog.addEventListener('close', () => {
        if (confirmButton) {
            confirmButton.disabled = false;
            confirmButton.textContent = 'Konfirmasi';
            delete confirmButton.dataset.submitting;
        }
        selectedButton?.focus();
    });
}

document.querySelectorAll('[data-proof-form]').forEach((form) => form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const file = form.elements.namedItem('proof')?.files?.[0];
    if (file?.size > 20 * 1024 * 1024) {
        showFeedback('Ukuran gambar maksimal 20 MB. Pilih berkas yang lebih kecil.');
        return;
    }
    if (file?.type === 'application/pdf' && file.size > 2 * 1024 * 1024) {
        showFeedback('Bukti PDF maksimal 2 MB.');
        return;
    }
    const button = form.querySelector('[type="submit"]');
    button.disabled = true;
    try {
        const response = await fetch(form.action, { method: 'POST', credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: new FormData(form) });
        if (response.ok) {
            window.location.reload();
            return;
        }
        const result = await response.json().catch(() => ({}));
        showFeedback(response.status === 413 ? 'Ukuran berkas melebihi batas server. Pilih gambar yang lebih kecil.' : result.errors?.proof?.[0] || result.message || 'Bukti pembayaran gagal dikirim.');
    } catch {
        showFeedback('Koneksi terputus. Periksa status transaksi sebelum mengirim ulang.');
    } finally {
        button.disabled = false;
    }
}));

function generateUuid() {
    if (typeof crypto !== 'undefined') {
        if (typeof crypto.randomUUID === 'function') {
            return crypto.randomUUID();
        }
        if (typeof crypto.getRandomValues === 'function') {
            return ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, (c) =>
                (c ^ (crypto.getRandomValues(new Uint8Array(1))[0] & (15 >> (c / 4)))).toString(16)
            );
        }
    }
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = (Math.random() * 16) | 0;
        const v = c === 'x' ? r : (r & 0x3) | 0x8;
        return v.toString(16);
    });
}

function showFeedback(message) {
    const currentFeedback = document.querySelector('#employer-feedback');
    if (!currentFeedback) return;
    currentFeedback.className = 'mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900';
    currentFeedback.setAttribute('role', 'alert');
    currentFeedback.textContent = message;
    currentFeedback.scrollIntoView({ behavior: 'smooth', block: 'start' });
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
            if (!form.dispatchEvent(new CustomEvent('employer:before-navigate', { bubbles: true, cancelable: true, detail: { destination } }))) return;
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
