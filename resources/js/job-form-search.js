const pause = (callback) => {
    let timer;
    return () => {
        clearTimeout(timer);
        timer = setTimeout(callback, 250);
    };
};

document.querySelectorAll('[data-company-search]').forEach((input) => {
    if (!(input instanceof HTMLInputElement)) return;
    const hidden = document.getElementById(input.dataset.companyTarget);
    const list = document.getElementById(input.getAttribute('aria-controls'));
    const status = document.getElementById(input.getAttribute('aria-describedby'));
    if (!(hidden instanceof HTMLInputElement) || !list || !status) return;
    let request;
    let active = -1;

    const close = () => {
        list.classList.add('hidden');
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        active = -1;
    };
    const options = () => [...list.querySelectorAll('[role="option"]')];
    const choose = (company) => {
        input.value = company.name;
        hidden.value = company.id;
        input.setCustomValidity('');
        status.textContent = `${company.name} dipilih.`;
        close();
        input.focus();
    };
    const setActive = (index) => {
        const items = options();
        if (!items.length) return;
        active = (index + items.length) % items.length;
        items.forEach((item, position) => item.setAttribute('aria-selected', String(position === active)));
        input.setAttribute('aria-activedescendant', items[active].id);
        items[active].scrollIntoView({ block: 'nearest' });
    };
    const search = pause(async () => {
        request?.abort();
        if (!input.value.trim()) {
            close();
            status.textContent = 'Ketik nama perusahaan untuk mencari.';
            return;
        }
        const controller = new AbortController();
        request = controller;
        status.textContent = 'Mencari perusahaan…';
        try {
            const url = new URL(input.dataset.companySearch, window.location.href);
            url.searchParams.set('q', input.value.trim());
            if (input.dataset.companyScope) url.searchParams.set('scope', input.dataset.companyScope);
            const response = await fetch(url, {
                headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: controller.signal,
            });
            if (!response.ok) throw new Error();
            const { data } = await response.json();
            list.replaceChildren();
            data.forEach((company, index) => {
                const item = document.createElement('button');
                item.type = 'button';
                item.id = `${list.id}-item-${index}`;
                item.setAttribute('role', 'option');
                item.setAttribute('aria-selected', 'false');
                item.className = 'block w-full px-3 py-2 text-left text-sm text-slate-800 hover:bg-slate-100 focus:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#2B506B] aria-selected:bg-slate-100';
                item.textContent = company.name;
                item.addEventListener('click', () => choose(company));
                list.append(item);
            });
            list.classList.toggle('hidden', data.length === 0);
            input.setAttribute('aria-expanded', String(data.length > 0));
            status.textContent = data.length ? `${data.length} perusahaan ditemukan. Pilih dari daftar.` : 'Perusahaan tidak ditemukan.';
        } catch (error) {
            if (error.name !== 'AbortError') status.textContent = 'Pencarian gagal. Coba lagi.';
        }
    });

    input.addEventListener('input', () => {
        request?.abort();
        hidden.value = '';
        input.setCustomValidity(input.value.trim() ? 'Pilih perusahaan dari hasil pencarian.' : '');
        close();
        search();
    });
    input.addEventListener('keydown', (event) => {
        if (list.classList.contains('hidden')) return;
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            setActive(active + (event.key === 'ArrowDown' ? 1 : -1));
        } else if (event.key === 'Enter' && active >= 0) {
            event.preventDefault();
            options()[active].click();
        } else if (event.key === 'Escape') {
            close();
        }
    });
    input.addEventListener('blur', () => setTimeout(close, 150));
    input.form?.addEventListener('submit', (event) => {
        if (input.value.trim() && !hidden.value) {
            input.setCustomValidity('Pilih perusahaan dari hasil pencarian.');
            event.preventDefault();
            input.reportValidity();
        }
    }, true);
});
