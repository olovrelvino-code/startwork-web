const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
const publicHeader = document.querySelector('.jobseeker-header');
if (publicHeader) {
    const updateHeader = () => publicHeader.classList.toggle('is-scrolled', window.scrollY > 8);
    window.addEventListener('scroll', updateHeader, { passive: true });
    updateHeader();
}

const jobSearch = document.querySelector('[data-job-search]');
if (jobSearch) {
    const category = jobSearch.querySelector('[data-category-search]');
    const input = category.querySelector('input');
    const suggestions = category.querySelector('[role="listbox"]');
    const options = [...category.querySelectorAll('[data-category-option]')];
    const empty = category.querySelector('[data-category-empty]');
    const closeSuggestions = () => {
        suggestions.classList.add('hidden');
        input.setAttribute('aria-expanded', 'false');
    };
    const showSuggestions = () => {
        const search = input.value.trim().toLocaleLowerCase('id');
        let matches = 0;
        options.forEach((option) => {
            const visible = search && option.dataset.categoryValue.toLocaleLowerCase('id').includes(search) && matches < 8;
            option.classList.toggle('hidden', !visible);
            if (visible) matches++;
        });
        empty.classList.toggle('hidden', !search || matches > 0);
        suggestions.classList.toggle('hidden', !search);
        input.setAttribute('aria-expanded', String(Boolean(search)));
    };
    const chooseCategory = (option) => {
        input.value = option.dataset.categoryValue;
        closeSuggestions();
        jobSearch.requestSubmit();
    };
    input.addEventListener('input', showSuggestions);
    input.addEventListener('focus', showSuggestions);
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeSuggestions();
        if (event.key === 'ArrowDown') {
            const first = options.find((option) => !option.classList.contains('hidden'));
            if (first) { event.preventDefault(); first.focus(); }
        }
    });
    options.forEach((option) => {
        option.addEventListener('click', () => chooseCategory(option));
        option.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); chooseCategory(option); }
            if (event.key === 'Escape') { closeSuggestions(); input.focus(); }
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                const visible = options.filter((item) => !item.classList.contains('hidden'));
                const next = visible.indexOf(option) + (event.key === 'ArrowDown' ? 1 : -1);
                (visible[next] || input).focus();
            }
        });
    });
    document.addEventListener('pointerdown', (event) => { if (!category.contains(event.target)) closeSuggestions(); });
    category.addEventListener('focusout', () => { setTimeout(() => { if (!category.contains(document.activeElement)) closeSuggestions(); }, 0); });

    const types = jobSearch.querySelector('[data-job-types]');
    const allTypes = types.querySelector('[data-all-types]');
    const chosenTypes = [...types.querySelectorAll('[name="job_type[]"]')];
    types.addEventListener('change', (event) => {
        if (event.target === allTypes) chosenTypes.forEach((option) => { option.checked = false; });
        allTypes.checked = !chosenTypes.some((option) => option.checked);
    });
    const from = jobSearch.elements.namedItem('created_from');
    const to = jobSearch.elements.namedItem('created_to');
    from.addEventListener('change', () => { to.min = from.value; });
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

function pageLink(page, label, active = false) {
    const url = new URL(window.location.href);
    url.searchParams.set('page', String(page));
    const link = node('a', `inline-flex min-h-10 min-w-10 items-center justify-center rounded-lg border px-3 text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 ${active ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-300 bg-white text-slate-700 hover:border-blue-400'}`, label);
    link.href = url.pathname + url.search;
    if (active) link.setAttribute('aria-current', 'page');
    return link;
}

const list = document.querySelector('[data-public-list]');
if (list) {
    const kind = list.dataset.publicList;
    const parameters = new URLSearchParams(window.location.search);
    const page = Math.max(1, Number.parseInt(parameters.get('page') || '1', 10) || 1);
    const allowed = kind === 'jobs' ? ['q', 'location', 'category', 'sort', 'created_from', 'created_to'] : ['q', 'location', 'sort'];
    const query = new URLSearchParams({ page: String(page), limit: '12' });
    allowed.forEach((name) => { if (parameters.get(name)) query.set(name, parameters.get(name)); });
    if (kind === 'jobs') {
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
        if (page > last && total > 0) {
            const url = new URL(window.location.href);
            url.searchParams.delete('page');
            window.location.replace(url.pathname + url.search);
            return;
        }
        status.textContent = total ? `${total} ${kind === 'jobs' ? 'lowongan' : 'perusahaan'} ditemukan · Page ${page} of ${last}` : `Tidak ada ${kind === 'jobs' ? 'lowongan' : 'perusahaan'} yang cocok.`;
        const template = document.querySelector(kind === 'jobs' ? '#job-list-card' : '#company-list-card');
        data.forEach((row) => {
            const card = template.content.firstElementChild.cloneNode(true);
            card.href = kind === 'jobs' ? `/jobs/${encodeURIComponent(row.id)}` : `/perusahaan/${encodeURIComponent(row.id)}`;
            ['title', 'company_name', 'name', 'location', 'description'].forEach((field) => {
                const target = card.querySelector(`[data-field="${field}"]`);
                if (target) target.textContent = row[field] || (field === 'location' ? 'Lokasi tidak dicantumkan' : '');
            });
            const types = card.querySelector('[data-field="types"]');
            if (types) types.textContent = [row.job_type, row.work_type].filter(Boolean).join(' · ');
            const logo = card.querySelector('[data-field="logo"]');
            if (logo && row.logo) {
                logo.src = `/api/companies/${encodeURIComponent(row.id)}/logo`;
                logo.alt = `Logo ${row.name}`;
                logo.classList.remove('hidden');
                logo.addEventListener('error', () => logo.classList.add('hidden'));
            }
            items.append(card);
        });
        if (last > 1) {
            if (page > 1) pages.append(pageLink(page - 1, '‹'));
            const start = Math.max(1, Math.min(page - 2, last - 4));
            const end = Math.min(last, start + 4);
            if (start > 1) pages.append(pageLink(1, '1'));
            if (start > 2) pages.append(node('span', 'px-1 text-slate-500', '…'));
            for (let number = start; number <= end; number++) pages.append(pageLink(number, String(number), number === page));
            if (end < last - 1) pages.append(node('span', 'px-1 text-slate-500', '…'));
            if (end < last) pages.append(pageLink(last, String(last)));
            if (page < last) pages.append(pageLink(page + 1, '›'));
        }
    }).catch((error) => { status.textContent = errorMessage(error); });
}

const companyDetail = document.querySelector('[data-company-detail]');
if (companyDetail) {
    const status = companyDetail.querySelector('[data-detail-status]');
    api(`/api/companies/${encodeURIComponent(companyDetail.dataset.companyDetail)}`).then(({ data, open_positions: positions }) => {
        ['name', 'location', 'description'].forEach((field) => {
            companyDetail.querySelector(`[data-field="${field}"]`).textContent = data[field] || (field === 'description' ? 'Belum ada deskripsi perusahaan.' : 'Lokasi tidak dicantumkan');
        });
        document.title = `${data.name} · StartWork`;
        const logo = companyDetail.querySelector('[data-field="logo"]');
        if (data.logo) {
            logo.src = `/api/companies/${encodeURIComponent(data.id)}/logo`;
            logo.alt = `Logo ${data.name}`;
            logo.classList.remove('hidden');
            logo.addEventListener('error', () => logo.classList.add('hidden'));
        }
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
            const fallback = link.querySelector('[data-job-logo-fallback]');
            fallback.textContent = data.name.trim().charAt(0).toUpperCase() || 'P';
            if (data.logo) {
                const image = link.querySelector('[data-job-logo]');
                image.src = `/api/companies/${encodeURIComponent(data.id)}/logo`;
                image.addEventListener('load', () => { fallback.classList.add('hidden'); image.classList.remove('hidden'); }, { once: true });
            }
            container.append(link);
        });
        status.remove();
        companyDetail.querySelector('[data-detail-content]').classList.remove('hidden');
    }).catch((error) => { status.textContent = errorMessage(error); });
}

const applyButton = document.querySelector('[data-job-apply]');
if (applyButton) {
    const feedback = document.querySelector('[data-apply-feedback]');
    api('/api/jobseeker/my-applications').then(({ data }) => {
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
    const sections = ['skills', 'work_experiences', 'organization_experiences', 'education_histories', 'certificates'];

    function addEntry(section, values = {}) {
        const template = document.querySelector(`[data-entry-template="${section}"]`);
        const entry = template.content.firstElementChild.cloneNode(true);
        entry.querySelectorAll('[data-field]').forEach((field) => {
            field.value = String(values[field.dataset.field] ?? '');
            if (field.type === 'date') field.value = field.value.slice(0, 10);
        });
        entry.querySelector('[data-remove]').addEventListener('click', () => entry.remove());
        details.querySelector(`[data-entries="${section}"]`).append(entry);
        return entry;
    }

    function fillDetails(data = {}) {
        sections.forEach((section) => {
            details.querySelector(`[data-entries="${section}"]`).replaceChildren();
            (data[section] || []).forEach((entry) => addEntry(section, section === 'skills' ? { name: entry.name } : entry));
        });
        if (!details.querySelector('[data-entries="skills"] [data-entry]')) addEntry('skills');
    }

    function fillProfile(data) {
        profileExists = true;
        ['full_name', 'domicile', 'birth_date', 'gender', 'last_education', 'portfolio_link'].forEach((field) => {
            main.elements.namedItem(field).value = String(data[field] ?? '').slice(0, field === 'birth_date' ? 10 : undefined);
        });
        const photo = profile.querySelector('[data-profile-photo]');
        if (data.profile_photo) { photo.src = '/api/jobseeker/profile/photo'; photo.classList.remove('hidden'); photo.addEventListener('error', () => photo.classList.add('hidden'), { once: true }); }
        profile.querySelector('[data-cv-status]').textContent = data.cv_file ? `CV tersimpan: ${data.cv_filename || 'berkas CV'}${data.cv_uploaded_at ? ` · diunggah ${new Date(data.cv_uploaded_at).toLocaleDateString('id-ID')}` : ''}` : 'Belum ada CV.';
        profile.querySelector('[data-cv-link]').classList.toggle('hidden', !data.cv_file);
    }

    let profileReady = false;
    api('/api/jobseeker/profile').then(async ({ data }) => {
        fillProfile(data);
        try {
            const response = await api('/api/jobseeker/profile/details');
            fillDetails(response.data);
        } catch (error) {
            fillDetails();
            detailsFeedback.textContent = errorMessage(error);
        }
        profileReady = true;
    }).catch((error) => {
        if (error.status === 404) { fillDetails(); profileReady = true; }
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
        } catch (error) { detailsFeedback.textContent = errorMessage(error); }
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
        if (file.size > (kind === 'photo' ? 2 : 5) * 1024 * 1024) { feedback.textContent = `Ukuran ${kind === 'photo' ? 'foto' : 'CV'} melebihi batas.`; return; }
        const body = new FormData();
        body.append(kind, file);
        const button = form.querySelector('[type="submit"]');
        button.disabled = true;
        feedback.textContent = 'Mengunggah…';
        try {
            await api(`/api/jobseeker/profile/${kind}`, { method: 'POST', body });
            const response = await api('/api/jobseeker/profile');
            fillProfile(response.data);
            feedback.textContent = `${kind === 'photo' ? 'Foto' : 'CV'} berhasil diunggah.`;
            input.value = '';
        } catch (error) { feedback.textContent = errorMessage(error); }
        finally { button.disabled = false; }
    }));
}

const applications = document.querySelector('[data-my-applications]');
if (applications) {
    const status = applications.querySelector('[data-applications-status]');
    const rows = applications.querySelector('[data-applications-rows]');
    const names = { submitted: 'Terkirim', in_process: 'Dalam proses', accepted: 'Diterima', rejected: 'Ditolak' };
    const tones = { submitted: 'bg-blue-50 text-blue-700', in_process: 'bg-amber-50 text-amber-800', accepted: 'bg-emerald-50 text-emerald-800', rejected: 'bg-rose-50 text-rose-800' };
    api('/api/jobseeker/my-applications').then(({ total, data }) => {
        status.textContent = total ? `${total} lamaran ditemukan.` : 'Belum ada lamaran. Jelajahi lowongan untuk mulai melamar.';
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
    }).catch((error) => { status.textContent = error.status === 404 ? 'Belum ada profil atau lamaran.' : errorMessage(error); });
}
