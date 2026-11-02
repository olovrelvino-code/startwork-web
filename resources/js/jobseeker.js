const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const hero = document.querySelector('.jobseeker-home-hero');
const heroCount = document.querySelector('[data-hero-count]');
if (hero && heroCount && !reducedMotion) {
    const formatCount = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    let started = false;
    const startCount = () => {
        if (started) return;
        started = true;
        heroCount.textContent = `${formatCount.format(0)}%`;
        hero.classList.add('is-animating');
        const start = performance.now();
        const frame = (now) => {
            const progress = Math.min((now - start) / 1200, 1);
            heroCount.textContent = `${formatCount.format(Math.round(8995 * (1 - (1 - progress) ** 3)) / 100)}%`;
            if (progress < 1) requestAnimationFrame(frame);
            else heroCount.textContent = '89,95%';
        };
        requestAnimationFrame(frame);
    };
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            if (!started && entries.some((entry) => entry.isIntersecting)) { observer.disconnect(); startCount(); }
        }, { threshold: 0.1 });
        observer.observe(hero);
    } else startCount();
}
const jobsBanner = document.querySelector('[data-jobs-banner]');
if (jobsBanner && !reducedMotion) jobsBanner.classList.add('is-animating');
const publicHeader = document.querySelector('.jobseeker-header');
if (publicHeader) {
    const updateHeader = () => publicHeader.classList.toggle('is-scrolled', window.scrollY > 8);
    window.addEventListener('scroll', updateHeader, { passive: true });
    updateHeader();
}
const navPhoto = document.querySelector('[data-nav-photo]');
if (navPhoto) {
    const fallback = navPhoto.previousElementSibling;
    const reveal = () => { navPhoto.classList.remove('hidden'); fallback.classList.add('hidden'); };
    navPhoto.addEventListener('load', reveal);
    navPhoto.addEventListener('error', () => { navPhoto.classList.add('hidden'); fallback.classList.remove('hidden'); });
    if (navPhoto.complete && navPhoto.naturalWidth > 0) reveal();
}

const jobSearch = document.querySelector('[data-job-search]');
if (jobSearch) {
    const types = jobSearch.querySelector('[data-job-types]');
    const allTypes = types.querySelector('[data-all-types]');
    const chosenTypes = [...types.querySelectorAll('[name="job_type[]"]')];
    types.addEventListener('change', (event) => {
        if (event.target === allTypes) chosenTypes.forEach((option) => { option.checked = false; });
        allTypes.checked = !chosenTypes.some((option) => option.checked);
    });
}

async function api(url, options = {}) {
    const headers = { Accept: 'application/json', ...options.headers };
    if (options.method && options.method !== 'GET') headers['X-CSRF-TOKEN'] = csrfToken;
    const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store', ...options, headers });
    const body = await response.json().catch(() => ({}));
    if (!response.ok) throw { status: response.status, body };
    return body;
}

function errorMessage(error) {
    if (error.status === 401) return 'Sesi berakhir. Masuk kembali sebelum melanjutkan.';
    if (error.status === 419) return 'Token sesi kedaluwarsa. Muat ulang halaman sebelum mencoba lagi.';
    if (error.status === 404) return 'Data tidak ditemukan atau tidak lagi tersedia.';
    if (error.status === 409) return error.body?.message || 'Aksi ini tidak dapat diulang.';
    if (error.status === 422) return Object.values(error.body?.errors || {})[0]?.[0] || error.body?.message || 'Periksa kembali isian Anda.';
    return error.body?.message || 'Terjadi kesalahan. Coba lagi beberapa saat.';
}

function node(tag, className, text) {
    const item = document.createElement(tag);
    item.className = className;
    item.textContent = text;
    return item;
}

function safeUrl(value, allowed = ['http:', 'https:']) {
    try {
        const url = new URL(value);
        return allowed.includes(url.protocol) ? url.href : null;
    } catch {
        return null;
    }
}

function showCompanyLogo(image, fallback, companyId, companyName, filename) {
    fallback.textContent = companyName?.trim().charAt(0).toUpperCase() || 'P';
    if (!filename || !companyId) return;
    const reveal = () => {
        image.classList.remove('hidden');
        fallback.classList.add('hidden');
    };
    image.addEventListener('load', reveal, { once: true });
    image.addEventListener('error', () => {
        image.classList.add('hidden');
        fallback.classList.remove('hidden');
    }, { once: true });
    image.src = `/api/companies/${encodeURIComponent(companyId)}/logo`;
    if (image.complete && image.naturalWidth > 0) reveal();
}

function pageLink(page, label, active = false) {
    const url = new URL(window.location.href);
    url.searchParams.set('page', String(page));
    const link = node('a', `inline-flex min-h-10 min-w-10 items-center justify-center rounded-lg border px-3 text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 ${active ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-300 bg-white text-slate-700 hover:border-blue-400'}`, label);
    link.href = url.pathname + url.search;
    if (active) link.setAttribute('aria-current', 'page');
    if (label === '‹') link.setAttribute('aria-label', 'Halaman sebelumnya');
    if (label === '›') link.setAttribute('aria-label', 'Halaman berikutnya');
    return link;
}

function renderPages(container, page, last) {
    if (last <= 1) return;
    if (page > 1) container.append(pageLink(page - 1, '‹'));
    const start = Math.max(1, Math.min(page - 2, last - 4));
    const end = Math.min(last, start + 4);
    if (start > 1) container.append(pageLink(1, '1'));
    if (start > 2) container.append(node('span', 'px-1 text-slate-500', '…'));
    for (let number = start; number <= end; number++) container.append(pageLink(number, String(number), number === page));
    if (end < last - 1) container.append(node('span', 'px-1 text-slate-500', '…'));
    if (end < last) container.append(pageLink(last, String(last)));
    if (page < last) container.append(pageLink(page + 1, '›'));
}

const list = document.querySelector('[data-public-list]');
if (list) {
    const kind = list.dataset.publicList;
    const parameters = new URLSearchParams(window.location.search);
    const page = Math.max(1, Number.parseInt(parameters.get('page') || '1', 10) || 1);
    const allowed = ['q', 'location', 'sort'];
    const query = new URLSearchParams({ page: String(page), limit: '12' });
    allowed.forEach((name) => { if (parameters.get(name)) query.set(name, parameters.get(name)); });
    if (kind === 'jobs') {
        if (parameters.get('category')) query.append('category[]', parameters.get('category'));
        parameters.getAll('category[]').forEach((value) => {
            const option = [...document.querySelectorAll('[name="category[]"][data-query-values]')].find((input) => input.value === value);
            (option?.dataset.queryValues.split('|') || [value]).forEach((category) => query.append('category[]', category));
        });
        ['job_type', 'work_type'].forEach((name) => {
            if (parameters.get(name)) query.set(name, parameters.get(name));
            parameters.getAll(`${name}[]`).forEach((value) => query.append(`${name}[]`, value));
        });
    }
    const status = list.querySelector('[data-list-status]');
    const items = list.querySelector('[data-list-items]');
    const pages = list.querySelector('[data-list-pages]');

    api(`/api/${kind === 'jobs' ? 'job-posts' : 'companies'}?${query}`).then(({ total, data }) => {
        const last = Math.max(1, Math.ceil(total / 12));
        if (page > last) {
            const url = new URL(window.location.href);
            url.searchParams.delete('page');
            window.location.replace(url.pathname + url.search);
            return;
        }
        status.textContent = total ? `${total} ${kind === 'jobs' ? 'lowongan' : 'perusahaan'} ditemukan · Page ${page} of ${last}` : `Tidak ada ${kind === 'jobs' ? 'lowongan' : 'perusahaan'} yang cocok.`;
        if (!total && window.location.search) {
            const reset = node('a', 'ml-2 font-semibold text-blue-700 underline', 'Hapus filter');
            reset.href = window.location.pathname;
            status.append(reset);
        }
        const template = document.querySelector(kind === 'jobs' ? '#job-list-card' : '#company-list-card');
        data.forEach((row) => {
            const card = template.content.firstElementChild.cloneNode(true);
            const link = card.querySelector('[data-job-link]') || card;
            link.href = kind === 'jobs' ? `/jobs/${encodeURIComponent(row.id)}` : `/perusahaan/${encodeURIComponent(row.id)}`;
            if (kind === 'jobs') {
                card.querySelector('[data-save-job]').dataset.saveJob = row.id;
                card.querySelector('[data-share-job]').dataset.shareJob = row.id;
                card.querySelector('[data-share-job]').dataset.shareUrl = link.href;
            }
            ['title', 'company_name', 'name', 'location', 'description'].forEach((field) => {
                const target = card.querySelector(`[data-field="${field}"]`);
                if (target) target.textContent = row[field] || (field === 'location' ? 'Lokasi tidak dicantumkan' : '');
            });
            const types = card.querySelector('[data-field="types"]');
            if (types) {
                [['category', row.category], ['job-type', row.job_type], ['work-type', row.work_type]].forEach(([chipKind, value]) => {
                    if (!value) return;
                    const chip = node('span', 'jobseeker-job-chip', value);
                    chip.dataset.kind = chipKind;
                    types.append(chip);
                });
            }
            const companyId = kind === 'jobs' ? row.company_id : row.id;
            const companyName = kind === 'jobs' ? row.company_name : row.name;
            showCompanyLogo(card.querySelector('[data-field="logo"]'), card.querySelector('[data-field="logo-fallback"]'), companyId, companyName, kind === 'jobs' ? row.company_logo : row.logo);
            items.append(card);
        });
        updateSaveButtons();
        renderPages(pages, page, last);
    }).catch((error) => { status.textContent = errorMessage(error); });
}

const savedJobs = new Set();
document.querySelectorAll('[data-saved-card] [data-save-job]').forEach((button) => savedJobs.add(button.dataset.saveJob));
const isJobseeker = document.querySelector('meta[name="session-user-id"]')?.dataset.role === 'jobseeker';
const saveStatus = document.querySelector('[data-save-status]');
let statusTimer;
function saveFeedback(message) {
    if (!saveStatus) return;
    saveStatus.textContent = message;
    saveStatus.classList.remove('hidden');
    clearTimeout(statusTimer);
    statusTimer = setTimeout(() => saveStatus.classList.add('hidden'), 3500);
}
function updateSaveButtons() {
    document.querySelectorAll('[data-save-job]').forEach((button) => {
        const saved = savedJobs.has(button.dataset.saveJob);
        button.setAttribute('aria-pressed', String(saved));
        button.setAttribute('aria-label', saved ? 'Hapus lowongan tersimpan' : 'Simpan lowongan');
        button.title = saved ? 'Hapus dari tersimpan' : 'Simpan lowongan';
    });
}
const savedReady = isJobseeker
    ? api('/api/jobseeker/saved-jobs').then(({ data }) => {
        data.forEach((id) => savedJobs.add(id));
        updateSaveButtons();
    }).catch(() => saveFeedback('Status lowongan tersimpan belum dapat dimuat.'))
    : Promise.resolve();
const shareDialog = document.querySelector('#jobseeker-share-dialog');
let sharedLink = '';
document.addEventListener('click', async (event) => {
    const save = event.target.closest('[data-save-job]');
    if (save) {
        if (!isJobseeker) { window.location.href = '/login'; return; }
        save.disabled = true;
        await savedReady;
        const id = save.dataset.saveJob;
        const wasSaved = savedJobs.has(id);
        try {
            await api(`/api/jobseeker/saved-jobs/${encodeURIComponent(id)}`, { method: wasSaved ? 'DELETE' : 'POST' });
            if (wasSaved) savedJobs.delete(id); else savedJobs.add(id);
            updateSaveButtons();
            saveFeedback(wasSaved ? 'Lowongan dihapus dari daftar tersimpan.' : 'Lowongan berhasil disimpan.');
            if (wasSaved && save.closest('[data-saved-card]')) {
                save.closest('[data-saved-card]').remove();
                if (!document.querySelector('[data-saved-card]')) {
                    if (document.querySelector('[data-saved-pagination] a[href]')) {
                        window.location.assign(window.location.pathname);
                        return;
                    }
                    document.querySelector('[data-saved-grid]')?.classList.add('hidden');
                    document.querySelector('[data-saved-pagination]')?.classList.add('hidden');
                    document.querySelector('[data-saved-empty]')?.classList.remove('hidden');
                }
            }
        } catch (error) { saveFeedback(errorMessage(error)); }
        finally { save.disabled = false; }
        return;
    }
    const share = event.target.closest('[data-share-job]');
    if (share && shareDialog) {
        sharedLink = new URL(share.dataset.shareUrl || `/jobs/${encodeURIComponent(share.dataset.shareJob)}`, window.location.origin).href;
        shareDialog.querySelector('[data-share-whatsapp]').href = `https://wa.me/?text=${encodeURIComponent(sharedLink)}`;
        shareDialog.querySelector('[data-share-native]').classList.toggle('hidden', !navigator.share);
        shareDialog.querySelector('[data-share-feedback]').textContent = '';
        shareDialog.showModal();
    }
});
if (shareDialog) {
    shareDialog.querySelector('[data-share-close]').addEventListener('click', () => shareDialog.close());
    shareDialog.addEventListener('click', (event) => { if (event.target === shareDialog) shareDialog.close(); });
    shareDialog.querySelector('[data-share-whatsapp]').addEventListener('click', () => {
        shareDialog.querySelector('[data-share-feedback]').textContent = 'WhatsApp dibuka untuk membagikan tautan.';
    });
    shareDialog.querySelector('[data-share-copy]').addEventListener('click', async () => {
        try {
            if (navigator.clipboard?.writeText) await navigator.clipboard.writeText(sharedLink);
            else {
                const input = document.createElement('textarea');
                input.value = sharedLink;
                input.style.position = 'fixed';
                input.style.opacity = '0';
                document.body.append(input);
                input.select();
                const copied = document.execCommand('copy');
                input.remove();
                if (!copied) throw new Error('Copy failed');
            }
            shareDialog.querySelector('[data-share-feedback]').textContent = 'Link berhasil disalin.';
        } catch { shareDialog.querySelector('[data-share-feedback]').textContent = 'Gagal menyalin link. Silakan salin dari alamat halaman.'; }
    });
    shareDialog.querySelector('[data-share-native]').addEventListener('click', async () => {
        try {
            await navigator.share({ url: sharedLink });
            shareDialog.querySelector('[data-share-feedback]').textContent = 'Tautan berhasil dibagikan.';
        } catch (error) {
            if (error.name !== 'AbortError') shareDialog.querySelector('[data-share-feedback]').textContent = 'Gagal membagikan tautan. Coba salin link.';
        }
    });
}

const companyDetail = document.querySelector('[data-company-detail]');
if (companyDetail) {
    const status = companyDetail.querySelector('[data-detail-status]');
    api(`/api/companies/${encodeURIComponent(companyDetail.dataset.companyDetail)}`).then(({ data, open_positions: positions }) => {
        ['name', 'location', 'description'].forEach((field) => {
            companyDetail.querySelector(`[data-field="${field}"]`).textContent = data[field] || (field === 'description' ? 'Belum ada deskripsi perusahaan.' : 'Lokasi tidak dicantumkan');
        });
        document.title = `${data.name} · StartWork`;
        showCompanyLogo(companyDetail.querySelector('[data-field="logo"]'), companyDetail.querySelector('[data-company-logo-fallback]'), data.id, data.name, data.logo);
        for (const field of ['website', 'contact']) {
            const link = companyDetail.querySelector(`[data-field="${field}"]`);
            const href = safeUrl(field === 'contact' ? data.contact_url : data.website, field === 'contact' ? ['http:', 'https:', 'mailto:'] : ['http:', 'https:']);
            if (href) { link.href = href; link.classList.remove('hidden'); }
        }
        const container = companyDetail.querySelector('[data-field="positions"]');
        if (!positions.length) container.append(node('p', 'rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-600 sm:col-span-2', 'Belum ada lowongan terbuka dari perusahaan ini.'));
        const template = document.querySelector('#company-open-job-card');
        positions.forEach((job) => {
            const link = template.content.firstElementChild.cloneNode(true);
            link.href = `/jobs/${encodeURIComponent(job.id)}`;
            link.querySelector('[data-job-company]').textContent = data.name;
            link.querySelector('[data-job-title]').textContent = job.title || 'Lowongan tanpa judul';
            link.querySelector('[data-job-location]').textContent = job.location || 'Lokasi tidak dicantumkan';
            link.querySelector('[data-job-types]').textContent = [job.job_type, job.work_type].filter(Boolean).join(' · ');
            showCompanyLogo(link.querySelector('[data-job-logo]'), link.querySelector('[data-job-logo-fallback]'), data.id, data.name, data.logo);
            container.append(link);
        });
        status.remove();
        companyDetail.querySelector('[data-detail-content]').classList.remove('hidden');
    }).catch((error) => { status.textContent = errorMessage(error); });
}

const applyButton = document.querySelector('[data-job-apply]');
if (applyButton) {
    const feedback = document.querySelector('[data-apply-feedback]');
    api(`/api/jobseeker/my-applications?job_id=${encodeURIComponent(applyButton.dataset.jobApply)}&limit=1`).then(({ data }) => {
        if (data.some((application) => application.job_id === applyButton.dataset.jobApply)) {
            applyButton.disabled = true;
            applyButton.textContent = 'Lamaran sudah terkirim';
            feedback.textContent = 'Status lamaran dapat dilihat di Lamaran Saya.';
        }
    }).catch(() => {});
    applyButton.addEventListener('click', async () => {
        applyButton.disabled = true;
        feedback.textContent = 'Mengirim lamaran…';
        try {
            await api(`/api/job-posts/${encodeURIComponent(applyButton.dataset.jobApply)}/apply`, { method: 'POST' });
            applyButton.textContent = 'Lamaran sudah terkirim';
            feedback.textContent = 'Lamaran berhasil dikirim. Lihat perkembangannya di Lamaran Saya.';
        } catch (error) {
            feedback.textContent = errorMessage(error);
            if (error.status === 422) {
                const link = node('a', 'ml-1 font-semibold text-blue-700 underline', 'Lengkapi profil & CV');
                link.href = '/profil';
                feedback.append(link);
            }
            if (error.status === 401) {
                const link = node('a', 'ml-1 font-semibold text-blue-700 underline', 'Masuk kembali');
                link.href = '/login';
                feedback.append(link);
            }
            if (error.status !== 409 || !/pernah melamar/i.test(error.body?.message || '')) applyButton.disabled = false;
        }
    });
}

const profile = document.querySelector('[data-jobseeker-profile]');
if (profile) {
    let profileExists = false;
    const main = profile.querySelector('[data-profile-main]');
    const details = profile.querySelector('[data-profile-details]');
    const content = profile.querySelector('[data-profile-content]');
    const loading = profile.querySelector('[data-profile-loading]');
    const mainFeedback = profile.querySelector('[data-main-feedback]');
    const detailsFeedback = profile.querySelector('[data-details-feedback]');
    const retryDetails = profile.querySelector('[data-details-retry]');
    const sections = ['skills', 'work_experiences', 'organization_experiences', 'education_histories'];
    const certificateForm = profile.querySelector('[data-certificate-form]');
    const certificateList = profile.querySelector('[data-certificate-list]');
    const certificateFeedback = profile.querySelector('[data-certificate-list-feedback]');
    let detailsLoaded = false;
    const photo = profile.querySelector('[data-profile-photo]');
    const photoFallback = profile.querySelector('[data-profile-photo-fallback]');
    photo.addEventListener('load', () => { photo.classList.remove('hidden'); photoFallback.classList.add('hidden'); });
    photo.addEventListener('error', () => { photo.classList.add('hidden'); photoFallback.classList.remove('hidden'); });
    const ready = (name, complete, message) => {
        profile.querySelector(`[data-ready-${name}]`).textContent = message;
        const icon = profile.querySelector(`[data-ready-icon="${name}"]`);
        icon.classList.toggle('text-emerald-600', complete);
        icon.classList.toggle('text-rose-600', !complete);
        icon.classList.remove('text-slate-500');
        icon.querySelector('path').setAttribute('d', complete ? 'm5 12 4 4L19 6' : 'M6 6l12 12M18 6 6 18');
    };

    function setDetailsLoaded(loaded) {
        detailsLoaded = loaded;
        details.querySelectorAll('input, select, textarea, button').forEach((control) => { control.disabled = !loaded; });
    }

    function addEntry(section, values = {}) {
        const template = document.querySelector(`[data-entry-template="${section}"]`);
        const entry = template.content.firstElementChild.cloneNode(true);
        entry.querySelectorAll('[data-field]').forEach((field) => {
            const value = String(values[field.dataset.field] ?? '');
            if (field.tagName === 'SELECT' && value && ![...field.options].some((option) => option.value === value)) { const option = new Option(`${value} (data lama)`, value); option.dataset.legacy = 'true'; field.add(option); }
            field.value = value;
            if (field.type === 'date') field.value = field.value.slice(0, 10);
        });
        entry.querySelector('[data-remove]').addEventListener('click', () => { if (window.confirm('Hapus entri ini? Simpan perubahan untuk menerapkannya.')) entry.remove(); });
        details.querySelector(`[data-entries="${section}"]`).append(entry);
        return entry;
    }

    function fillDetails(data = {}) {
        sections.forEach((section) => {
            details.querySelector(`[data-entries="${section}"]`).replaceChildren();
            (data[section] || []).forEach((entry) => addEntry(section, section === 'skills' ? { name: entry.name } : entry));
        });
        if (!details.querySelector('[data-entries="skills"] [data-entry]')) addEntry('skills');
        ready('skills', Boolean(data.skills?.length), data.skills?.length ? 'Terisi' : 'Belum lengkap');
        renderCertificates(data.certificates || []);
    }

    function renderCertificates(items) {
        certificateList.replaceChildren();
        if (!items.length) { certificateList.append(node('p', 'text-sm text-slate-600', 'Belum ada sertifikat.')); return; }
        items.forEach((certificate) => {
            const row = node('div', 'flex flex-col gap-3 rounded-lg bg-slate-50 p-4 sm:flex-row sm:items-center', '');
            const summary = node('div', 'min-w-0 flex-1', '');
            const actions = node('div', 'flex flex-wrap items-center gap-2', '');
            summary.append(node('p', 'break-words text-sm font-semibold text-[#17394B]', certificate.name || 'Sertifikat'));
            const extension = certificate.file_path?.split('.').pop()?.toUpperCase();
            const size = Number.isFinite(certificate.file_size) ? ` · ${(certificate.file_size / 1024 / 1024).toFixed(2)} MB` : '';
            summary.append(node('p', 'mt-1 text-xs text-slate-600', extension ? `${extension}${size}${certificate.file_size === null ? ' · berkas tidak tersedia' : ''}` : certificate.file_url ? 'Tautan lama' : 'Belum ada berkas'));
            row.append(summary);
            if (certificate.file_path && certificate.file_size !== null) {
                const url = `/api/jobseeker/profile/certificates/${encodeURIComponent(certificate.id)}/file`;
                const preview = node('a', 'text-sm font-medium text-[#17394B] hover:underline', 'Pratinjau');
                preview.href = `${url}?preview=1`; preview.target = '_blank'; preview.rel = 'noopener';
                const download = node('a', 'grid h-10 w-10 place-items-center rounded-lg border border-slate-300 text-[#17394B] hover:bg-white', '');
                download.href = url; download.title = 'Unduh sertifikat'; download.setAttribute('aria-label', 'Unduh sertifikat');
                download.append(profile.querySelector('[data-cv-link] svg').cloneNode(true));
                actions.append(preview, download);
            } else if (/^https?:\/\//i.test(certificate.file_url || '')) {
                const legacy = node('a', 'text-sm text-[#17394B] hover:underline', 'Buka tautan lama');
                legacy.href = certificate.file_url; legacy.target = '_blank'; legacy.rel = 'noopener noreferrer';
                actions.append(legacy);
            }
            const edit = node('button', 'rounded-lg px-3 py-2 text-sm font-medium text-[#17394B] hover:bg-white', 'Ubah');
            edit.type = 'button'; edit.title = 'Ubah sertifikat';
            edit.addEventListener('click', () => {
                certificateForm.reset();
                certificateForm.dataset.id = certificate.id;
                certificateForm.elements.namedItem('name').value = certificate.name || '';
                certificateForm.elements.namedItem('file').required = false;
                certificateForm.classList.remove('hidden');
                certificateForm.elements.namedItem('name').focus();
            });
            const remove = node('button', 'grid h-10 w-10 place-items-center rounded-lg border border-slate-300 text-rose-700 hover:bg-white', '');
            remove.type = 'button'; remove.title = 'Hapus sertifikat'; remove.setAttribute('aria-label', 'Hapus sertifikat');
            remove.append(profile.querySelector('[data-cv-delete] svg').cloneNode(true));
            remove.addEventListener('click', async () => {
                if (!window.confirm(`Hapus sertifikat ${certificate.name || ''} beserta berkasnya?`)) return;
                remove.disabled = true; certificateFeedback.textContent = 'Menghapus sertifikat…';
                try {
                    await api(`/api/jobseeker/profile/certificates/${encodeURIComponent(certificate.id)}`, { method: 'DELETE' });
                    await refreshCertificates(); certificateFeedback.textContent = 'Sertifikat berhasil dihapus.';
                } catch (error) { certificateFeedback.textContent = errorMessage(error); remove.disabled = false; }
            });
            actions.append(edit, remove);
            row.append(actions);
            certificateList.append(row);
        });
    }

    async function refreshCertificates() {
        const response = await api('/api/jobseeker/profile/details');
        renderCertificates(response.data.certificates || []);
    }

    function fillProfile(data) {
        profileExists = true;
        const navName = document.querySelector('[data-nav-name]');
        if (navName && data.full_name) {
            navName.textContent = data.full_name;
            navPhoto.previousElementSibling.textContent = data.full_name.trim().charAt(0).toLocaleUpperCase('id');
        }
        ['full_name', 'domicile', 'birth_date', 'portfolio_link'].forEach((field) => {
            main.elements.namedItem(field).value = String(data[field] ?? '').slice(0, field === 'birth_date' ? 10 : undefined);
        });
        const gender = ['male', 'laki-laki'].includes(String(data.gender).toLowerCase()) ? 'Laki-laki' : ['female', 'perempuan'].includes(String(data.gender).toLowerCase()) ? 'Perempuan' : '';
        main.querySelectorAll('[name="gender"]').forEach((radio) => { radio.checked = radio.value === gender; });
        const education = main.elements.namedItem('last_education');
        const oldOption = education.querySelector('[data-legacy]');
        oldOption?.remove();
        if (data.last_education && ![...education.options].some((option) => option.value === data.last_education)) { const option = new Option(`${data.last_education} (data lama)`, data.last_education); option.dataset.legacy = 'true'; education.add(option); }
        education.value = data.last_education || '';
        if (data.profile_photo) photo.src = `/api/jobseeker/profile/photo?v=${encodeURIComponent(data.profile_photo)}`;
        else { photo.removeAttribute('src'); photo.classList.add('hidden'); photoFallback.classList.remove('hidden'); }
        const hasCv = Boolean(data.cv_file && data.cv_size !== null);
        profile.querySelector('[data-cv-status]').textContent = data.cv_file ? `${data.cv_filename || 'Berkas CV'} · ${data.cv_file.split('.').pop().toUpperCase()}${Number.isFinite(data.cv_size) ? ` · ${(data.cv_size / 1024 / 1024).toFixed(2)} MB` : ' · berkas tidak tersedia, unggah ulang'}` : 'Belum ada CV.';
        profile.querySelector('[data-cv-link]').classList.toggle('hidden', !hasCv);
        profile.querySelector('[data-cv-link]').classList.toggle('grid', hasCv);
        profile.querySelector('[data-cv-preview]').classList.toggle('hidden', !hasCv);
        profile.querySelector('[data-cv-delete]').classList.toggle('hidden', !data.cv_file);
        profile.querySelector('[data-cv-delete]').classList.toggle('grid', Boolean(data.cv_file));
        const profileComplete = ['full_name', 'domicile', 'birth_date', 'gender', 'last_education'].every((field) => data[field]);
        ready('profile', profileComplete, profileComplete ? 'Terisi' : 'Belum lengkap');
        ready('cv', hasCv, hasCv ? 'Terunggah' : 'Belum diunggah');
    }

    async function loadDetails() {
        setDetailsLoaded(false);
        retryDetails.classList.add('hidden');
        detailsFeedback.textContent = 'Memuat keahlian dan riwayat…';
        try {
            const response = await api('/api/jobseeker/profile/details');
            fillDetails(response.data);
            setDetailsLoaded(true);
            detailsFeedback.textContent = '';
        } catch (error) {
            detailsFeedback.textContent = errorMessage(error);
            profile.querySelector('[data-ready-skills]').textContent = 'Belum dapat diperiksa';
            retryDetails.classList.remove('hidden');
        }
    }

    retryDetails.addEventListener('click', loadDetails);
    let profileReady = false;
    api('/api/jobseeker/profile').then(async ({ data }) => {
        fillProfile(data);
        await loadDetails();
        profileReady = true;
    }).catch((error) => {
        if (error.status === 404) { fillDetails(); ready('profile', false, 'Belum lengkap'); ready('cv', false, 'Belum diunggah'); setDetailsLoaded(true); profileReady = true; }
        else loading.textContent = errorMessage(error);
    }).finally(() => {
        if (profileReady) {
            loading.classList.add('hidden');
            content.classList.remove('hidden');
        }
    });

    details.querySelectorAll('[data-add]').forEach((button) => button.addEventListener('click', () => addEntry(button.dataset.add).querySelector('[data-field]')?.focus()));

    main.addEventListener('submit', async (event) => {
        event.preventDefault();
        main.querySelectorAll('[data-field-error]').forEach((item) => item.remove());
        main.querySelectorAll('[aria-invalid]').forEach((item) => item.removeAttribute('aria-invalid'));
        const values = Object.fromEntries(new FormData(main));
        values.portfolio_link ||= null;
        const button = main.querySelector('[type="submit"]');
        button.disabled = true;
        mainFeedback.textContent = 'Menyimpan…';
        try {
            const response = await api('/api/jobseeker/profile', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(values) });
            fillProfile(response.data);
            mainFeedback.textContent = 'Data diri berhasil disimpan.';
        } catch (error) {
            mainFeedback.textContent = errorMessage(error);
            Object.entries(error.body?.errors || {}).forEach(([field, messages]) => {
                const input = main.elements.namedItem(field);
                if (!(input instanceof HTMLElement)) return;
                input.setAttribute('aria-invalid', 'true');
                const note = node('span', 'mt-1 block text-xs text-rose-700', messages[0]);
                note.dataset.fieldError = field;
                input.parentElement.append(note);
            });
        } finally { button.disabled = false; }
    });

    details.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!detailsLoaded) { detailsFeedback.textContent = 'Muat keahlian dan riwayat sebelum menyimpan.'; return; }
        if (!profileExists) { detailsFeedback.textContent = 'Simpan data diri terlebih dahulu.'; main.querySelector('input').focus(); return; }
        const payload = {};
        sections.forEach((section) => {
            const entries = [...details.querySelectorAll(`[data-entries="${section}"] [data-entry]`)];
            payload[section] = entries.map((entry) => {
                const values = Object.fromEntries([...entry.querySelectorAll('[data-field]')].map((field) => [field.dataset.field, field.value.trim()]));
                return section === 'skills' ? values.name : values;
            }).filter((values) => section === 'skills' ? values !== '' : Object.values(values).some(Boolean));
        });
        if (!payload.skills.length) { detailsFeedback.textContent = 'Tambahkan minimal satu skill.'; return; }
        const button = details.querySelector('[type="submit"]');
        button.disabled = true;
        detailsFeedback.textContent = 'Menyimpan…';
        try {
            await api('/api/jobseeker/profile/details', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            detailsFeedback.textContent = 'Keahlian dan riwayat berhasil disimpan.';
            ready('skills', true, 'Terisi');
        } catch (error) { detailsFeedback.textContent = errorMessage(error); }
        finally { button.disabled = false; }
    });

    async function documentError(file) {
        const extension = file.name.split('.').pop()?.toLowerCase();
        const mime = { pdf: 'application/pdf', jpg: 'image/jpeg', jpeg: 'image/jpeg', png: 'image/png' }[extension];
        if (!mime || (file.type && file.type !== mime)) return 'Pilih PDF, JPG, JPEG, atau PNG yang valid.';
        if (extension !== 'pdf' && file.size > 2 * 1024 * 1024) return 'Gambar maksimal 2 MB.';
        const bytes = new Uint8Array(await file.slice(0, 8).arrayBuffer());
        const pdf = extension === 'pdf' && [37, 80, 68, 70, 45].every((byte, index) => bytes[index] === byte);
        const jpeg = ['jpg', 'jpeg'].includes(extension) && bytes[0] === 255 && bytes[1] === 216 && bytes[2] === 255;
        const png = extension === 'png' && [137, 80, 78, 71, 13, 10, 26, 10].every((byte, index) => bytes[index] === byte);
        return pdf || jpeg || png ? null : 'Isi berkas tidak sesuai dengan format yang dipilih.';
    }

    profile.querySelector('[data-certificate-add]').addEventListener('click', () => {
        certificateForm.reset(); delete certificateForm.dataset.id;
        certificateForm.elements.namedItem('file').required = true;
        certificateForm.querySelector('[data-certificate-feedback]').textContent = '';
        certificateForm.classList.remove('hidden');
        certificateForm.elements.namedItem('name').focus();
    });
    profile.querySelector('[data-certificate-cancel]').addEventListener('click', () => certificateForm.classList.add('hidden'));
    certificateForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const feedback = certificateForm.querySelector('[data-certificate-feedback]');
        if (!profileExists) { feedback.textContent = 'Simpan data diri terlebih dahulu.'; return; }
        const file = certificateForm.elements.namedItem('file').files[0];
        if (file) { const problem = await documentError(file); if (problem) { feedback.textContent = problem; return; } }
        const button = certificateForm.querySelector('[type="submit"]');
        button.disabled = true; feedback.textContent = 'Menyimpan sertifikat…';
        const url = `/api/jobseeker/profile/certificates${certificateForm.dataset.id ? `/${certificateForm.dataset.id}` : ''}`;
        try {
            const body = new FormData(certificateForm);
            if (!file) body.delete('file');
            await api(url, { method: 'POST', body });
            await refreshCertificates();
            certificateForm.classList.add('hidden'); certificateForm.reset();
            certificateFeedback.textContent = 'Sertifikat berhasil disimpan.';
        } catch (error) { feedback.textContent = errorMessage(error); }
        finally { button.disabled = false; }
    });

    profile.querySelector('[data-cv-delete]').addEventListener('click', async (event) => {
        if (!window.confirm('Hapus CV saya? Lamaran yang sudah dikirim tetap menyimpan salinannya.')) return;
        const button = event.currentTarget;
        const feedback = profile.querySelector('[data-upload="cv"] [data-upload-feedback]');
        button.disabled = true; feedback.textContent = 'Menghapus CV…';
        try {
            await api('/api/jobseeker/profile/cv', { method: 'DELETE' });
            const response = await api('/api/jobseeker/profile');
            fillProfile(response.data); feedback.textContent = 'CV berhasil dihapus.';
        } catch (error) { feedback.textContent = errorMessage(error); }
        finally { button.disabled = false; }
    });

    profile.querySelectorAll('[data-upload]').forEach((form) => form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const kind = form.dataset.upload;
        const input = form.querySelector('input[type="file"]');
        const file = input.files[0];
        const feedback = form.querySelector('[data-upload-feedback]');
        if (!profileExists) { feedback.textContent = 'Simpan data diri terlebih dahulu.'; return; }
        if (!file) { feedback.textContent = 'Pilih berkas terlebih dahulu.'; return; }
        if (kind === 'photo' && file.size > 2 * 1024 * 1024) { feedback.textContent = 'Ukuran foto melebihi 2 MB.'; return; }
        if (kind === 'cv') { const problem = await documentError(file); if (problem) { feedback.textContent = problem; return; } }
        const body = new FormData();
        body.append(kind, file);
        const button = form.querySelector('[type="submit"]');
        button.disabled = true;
        feedback.textContent = 'Mengunggah…';
        try {
            await api(`/api/jobseeker/profile/${kind}`, { method: 'POST', body });
            const response = await api('/api/jobseeker/profile');
            fillProfile(response.data);
            if (kind === 'photo' && navPhoto) navPhoto.src = `/api/jobseeker/profile/photo?updated=${Date.now()}`;
            feedback.textContent = `${kind === 'photo' ? 'Foto' : 'CV'} berhasil diunggah.`;
            input.value = '';
        } catch (error) { feedback.textContent = kind === 'photo' && error.status >= 500 ? 'Foto belum dapat diproses di server. Coba lagi nanti.' : errorMessage(error); }
        finally { button.disabled = false; }
    }));
}

const applications = document.querySelector('[data-my-applications]');
if (applications) {
    const page = Math.max(1, Number.parseInt(new URLSearchParams(window.location.search).get('page') || '1', 10) || 1);
    const status = applications.querySelector('[data-applications-status]');
    const rows = applications.querySelector('[data-applications-rows]');
    const pages = applications.querySelector('[data-applications-pages]');
    const names = { submitted: 'Terkirim', in_process: 'Dalam proses', accepted: 'Diterima', rejected: 'Ditolak' };
    const tones = { submitted: 'bg-blue-50 text-blue-700', in_process: 'bg-amber-50 text-amber-800', accepted: 'bg-emerald-50 text-emerald-800', rejected: 'bg-rose-50 text-rose-800' };
    api(`/api/jobseeker/my-applications?page=${page}&limit=12`).then(({ total, data }) => {
        const last = Math.max(1, Math.ceil(total / 12));
        if (page > last) {
            window.location.replace(window.location.pathname);
            return;
        }
        status.textContent = total ? `${total} lamaran ditemukan · Page ${page} of ${last}` : 'Belum ada lamaran. Jelajahi lowongan untuk mulai melamar.';
        if (!total) {
            const row = document.createElement('tr');
            const cell = node('td', 'px-5 py-8 text-center text-slate-600', 'Belum ada lamaran.');
            cell.colSpan = 4;
            row.append(cell);
            rows.append(row);
        }
        data.forEach((application) => {
            const row = document.createElement('tr');
            const title = document.createElement('td');
            title.className = 'px-5 py-4 font-semibold';
            title.textContent = application.title;
            row.append(title, node('td', 'px-5 py-4 text-slate-600', application.company_name), node('td', 'whitespace-nowrap px-5 py-4 text-slate-600', application.applied_at ? new Date(application.applied_at).toLocaleDateString('id-ID') : '—'));
            const state = document.createElement('td');
            state.className = 'px-5 py-4';
            state.append(node('span', `inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${tones[application.status] || 'bg-slate-100 text-slate-700'}`, names[application.status] || application.status));
            row.append(state);
            rows.append(row);
        });
        renderPages(pages, page, last);
    }).catch((error) => { status.textContent = error.status === 404 ? 'Belum ada profil atau lamaran.' : errorMessage(error); });
}
