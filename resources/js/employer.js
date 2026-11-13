import './auth-switch.js';

const feedback = document.querySelector('#employer-feedback');

const subscription = document.querySelector('[data-subscription]');
if (subscription) {
    const list = subscription.querySelector('[data-package-list]');
    const status = subscription.querySelector('[data-package-status]');
    const template = subscription.querySelector('[data-package-template]');
    const dialog = document.querySelector('[data-package-dialog]');
    const benefits = { Basic: ['Tayang di website (15 hari)', '1x Instagram Story', '1x Feed (Simple Design)'], Standard: ['Tayang di website (30 hari)', '1x Feed (Excellent Design)', '2x Instagram Story', 'Featured Lowongan di Website (1 hari)'], Premium: ['Tayang di website (45 hari)', '1x Reels (Video)', '1x Feed', '3x Instagram Story', 'Featured Lowongan di Website (3 hari)'] };
    const currency = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
    let selectedButton;
    fetch(subscription.dataset.packagesUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' }).then((response) => response.json()).then(({ data }) => {
        const plans = ['Basic', 'Standard', 'Premium'];
        const packages = data.filter((item) => plans.includes(item.name) && item.currency === 'IDR' && item.is_active).sort((a, b) => plans.indexOf(a.name) - plans.indexOf(b.name));
        if (!packages.length) { status.textContent = 'Belum ada paket aktif yang tersedia.'; return; }
        packages.forEach((item) => {
            const card = template.content.firstElementChild.cloneNode(true);
            card.style.borderTopColor = { Basic: '#17394B', Standard: '#B85B12', Premium: '#167C83' }[item.name];
            card.querySelector('[data-package-name]').textContent = item.name;
            if (item.name === 'Standard') card.querySelector('[data-package-badge]').classList.remove('hidden');
            const price = currency.format(Number(item.price));
            const quota = `${item.quota} lowongan`;
            const duration = `${item.duration_days} hari`;
            card.querySelector('[data-package-price]').textContent = price;
            card.querySelector('[data-package-quota]').textContent = quota;
            card.querySelector('[data-package-duration]').textContent = duration;
            benefits[item.name].forEach((text) => { const row = document.createElement('li'); row.textContent = text; card.querySelector('[data-package-benefits]').append(row); });
            const button = card.querySelector('[data-select-package]');
            button.addEventListener('click', () => {
                selectedButton = button;
                dialog.querySelector('[data-confirm-name]').textContent = item.name;
                dialog.querySelector('[data-confirm-price]').textContent = price;
                dialog.querySelector('[data-confirm-quota]').textContent = quota;
                dialog.querySelector('[data-confirm-duration]').textContent = duration;
                dialog.showModal();
            });
            list.append(card);
        });
        status.textContent = '';
    }).catch(() => { status.textContent = 'Katalog paket gagal dimuat. Muat ulang halaman untuk mencoba lagi.'; });
    dialog.querySelector('[data-package-close]').addEventListener('click', () => dialog.close());
    dialog.querySelector('[data-package-confirm]').addEventListener('click', () => { status.textContent = `Paket ${dialog.querySelector('[data-confirm-name]').textContent} dipilih. Pembelian belum tersedia; akses akun tidak berubah.`; dialog.close(); });
    dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
    dialog.addEventListener('close', () => selectedButton?.focus());
}

const upgradeDialog = document.querySelector('[data-upgrade-dialog]');
const upgradeTrigger = document.querySelector('[data-upgrade-open]');
if (upgradeDialog instanceof HTMLDialogElement && upgradeTrigger instanceof HTMLButtonElement) {
    const close = upgradeDialog.querySelector('[data-upgrade-close]');
    const status = upgradeDialog.querySelector('[data-upgrade-status]');
    const list = upgradeDialog.querySelector('[data-upgrade-packages]');
    const template = upgradeDialog.querySelector('[data-upgrade-template]');
    const names = ['Basic', 'Standard', 'Premium'];
    const benefits = {
        Basic: ['Website 15 hari', '1 Instagram Story', '1 Instagram Feed (Simple Design)'],
        Standard: ['Website 30 hari', '1 Instagram Feed (Excellent Design)', '2 Instagram Story', 'Featured Website 1 hari'],
        Premium: ['Website 45 hari', '1 Instagram Reels', '1 Instagram Feed', '3 Instagram Story', 'Featured Website 3 hari'],
    };
    const rupiah = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
    let request;

    upgradeTrigger.addEventListener('click', async () => {
        request?.abort();
        request = new AbortController();
        list.replaceChildren();
        status.textContent = 'Memuat paket…';
        upgradeDialog.showModal();
        try {
            const response = await fetch(upgradeDialog.dataset.packagesUrl, {
                headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: request.signal,
            });
            if (!response.ok) throw new Error('Katalog paket tidak tersedia.');
            const result = await response.json();
            const packages = (result.data || []).filter((item) => names.includes(item.name) && item.currency === 'IDR')
                .sort((a, b) => names.indexOf(a.name) - names.indexOf(b.name));
            if (!packages.length) {
                status.textContent = 'Belum ada paket aktif yang tersedia.';
                return;
            }
            packages.forEach((item) => {
                const card = template.content.firstElementChild.cloneNode(true);
                card.dataset.featured = String(item.name === 'Standard');
                card.querySelector('[data-package-badge]').textContent = item.name === 'Standard' ? 'Pilihan populer' : 'Paket employer';
                card.querySelector('[data-package-name]').textContent = item.name;
                const price = Number(item.price);
                card.querySelector('[data-package-price]').textContent = Number.isFinite(price) ? rupiah.format(price) : 'Harga tidak tersedia';
                card.querySelector('[data-package-quota]').textContent = item.quota === null ? 'Belum ditentukan' : `${item.quota} lowongan`;
                card.querySelector('[data-package-duration]').textContent = item.duration_days === null ? 'Belum ditentukan' : `${item.duration_days} hari`;
                const benefitList = card.querySelector('[data-package-benefits]');
                benefits[item.name].forEach((benefit) => {
                    const row = document.createElement('li');
                    row.className = 'flex gap-2';
                    const mark = document.createElement('span');
                    mark.className = 'shrink-0 font-semibold text-[#B85B12]';
                    mark.setAttribute('aria-hidden', 'true');
                    mark.textContent = '✓';
                    row.append(mark, document.createTextNode(benefit));
                    benefitList.append(row);
                });
                card.querySelector('[data-select-package]').addEventListener('click', () => {
                    list.querySelectorAll('article').forEach((option) => {
                        option.dataset.selected = String(option === card);
                        const button = option.querySelector('[data-select-package]');
                        button.dataset.selected = String(option === card);
                        button.setAttribute('aria-pressed', String(option === card));
                        button.textContent = option === card ? 'Dipilih' : 'Pilih paket';
                    });
                    status.textContent = `Paket ${item.name} dipilih. Pembelian belum tersedia; akses akun tidak berubah.`;
                });
                list.append(card);
            });
            status.textContent = 'Pilih paket untuk membandingkan opsi. Pembelian belum tersedia.';
        } catch (error) {
            if (error.name !== 'AbortError') status.textContent = 'Katalog paket gagal dimuat. Tutup dan buka kembali untuk mencoba lagi.';
        }
    });
    close.addEventListener('click', () => upgradeDialog.close());
    upgradeDialog.addEventListener('click', (event) => { if (event.target === upgradeDialog) upgradeDialog.close(); });
    upgradeDialog.addEventListener('close', () => { request?.abort(); upgradeTrigger.focus(); });
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
