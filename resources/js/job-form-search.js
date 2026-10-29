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

document.querySelectorAll('[data-admin-category]').forEach((root) => {
    const search = root.querySelector('[data-category-search]');
    const value = root.querySelector('[data-category-value]');
    const chip = root.querySelector('[data-category-chip]');
    const chipLabel = root.querySelector('[data-category-chip-label]');
    const remove = root.querySelector('[data-category-remove]');
    const list = root.querySelector('[role="listbox"]');
    const status = root.querySelector('[data-category-status]');
    const presets = JSON.parse(root.dataset.categoryPresets || '[]');
    let active = -1;

    const close = () => {
        list.classList.add('hidden');
        search.setAttribute('aria-expanded', 'false');
        search.removeAttribute('aria-activedescendant');
        active = -1;
    };
    const showChip = () => {
        const selected = value.value.trim();
        chipLabel.textContent = selected;
        chip.title = selected;
        const position = presets.indexOf(selected);
        chip.dataset.tone = position < 0 ? 'legacy' : String(position % 3);
        remove.setAttribute('aria-label', 'Hapus kategori ' + (selected || 'terpilih'));
        remove.title = 'Hapus kategori ' + (selected || 'terpilih');
        chip.classList.toggle('hidden', !selected);
        chip.classList.toggle('flex', !!selected);
    };
    const choose = (category) => {
        value.value = category;
        search.value = '';
        search.setCustomValidity('');
        status.textContent = 'Kategori ' + category + ' dipilih.';
        showChip();
        close();
        search.focus();
    };
    const options = () => [...list.querySelectorAll('[role="option"]')];
    const setActive = (index) => {
        const items = options();
        if (!items.length) return;
        active = (index + items.length) % items.length;
        items.forEach((item, position) => item.setAttribute('aria-selected', String(position === active)));
        search.setAttribute('aria-activedescendant', items[active].id);
        items[active].scrollIntoView({ block: 'nearest' });
    };
    const update = () => {
        const query = search.value.trim().replace(/\s+/g, ' ').toLocaleLowerCase();
        list.replaceChildren();
        if (!query) {
            close();
            return;
        }
        presets.filter((category) => category.toLocaleLowerCase().includes(query)).slice(0, 8).forEach((category, index) => {
            const option = document.createElement('button');
            option.type = 'button';
            option.id = list.id + '-item-' + index;
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', 'false');
            option.className = 'block w-full px-3 py-2 text-left text-sm text-slate-800 hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#2B506B] aria-selected:bg-sky-50 aria-selected:text-sky-900';
            option.textContent = category;
            option.addEventListener('click', () => choose(category));
            list.append(option);
        });
        const found = list.childElementCount > 0;
        if (!found) {
            const empty = document.createElement('p');
            empty.className = 'px-3 py-3 text-sm text-slate-500';
            empty.textContent = 'Tidak ada kategori yang cocok.';
            list.append(empty);
        }
        list.classList.remove('hidden');
        search.setAttribute('aria-expanded', 'true');
        search.removeAttribute('aria-activedescendant');
        active = -1;
        status.textContent = found ? 'Pilih kategori dari hasil pencarian.' : 'Tidak ada kategori yang cocok.';
    };

    search.addEventListener('input', () => { search.setCustomValidity(''); update(); });
    search.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
        } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (list.classList.contains('hidden')) update();
            setActive(active + (event.key === 'ArrowDown' ? 1 : -1));
        } else if (event.key === 'Enter') {
            event.preventDefault();
            const chosen = options()[active >= 0 ? active : 0];
            if (chosen) chosen.click();
            else status.textContent = 'Pilih kategori yang tersedia.';
        }
    });
    remove.addEventListener('click', () => {
        value.value = '';
        showChip();
        status.textContent = 'Kategori dihapus. Pilih kategori baru.';
        search.focus();
    });
    document.addEventListener('pointerdown', (event) => {
        if (!root.contains(event.target)) close();
    });
    search.form?.addEventListener('submit', (event) => {
        if (search.value.trim() || !value.value.trim()) {
            event.preventDefault();
            event.stopPropagation();
            search.setCustomValidity('Pilih satu kategori dari daftar.');
            search.reportValidity();
        }
    }, true);
    showChip();
});

document.querySelectorAll('[data-category-input]').forEach((input) => {
    const list = document.getElementById(input.getAttribute('aria-controls'));
    const status = input.parentElement.querySelector('[data-category-status]');
    let request;
    let active = -1;
    const normalized = (value) => value.trim().replace(/\s+/g, ' ').toLocaleLowerCase();

    const close = () => {
        list.classList.add('hidden');
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        active = -1;
    };
    const setActive = (index) => {
        const options = [...list.querySelectorAll('[role="option"]')];
        active = (index + options.length) % options.length;
        options.forEach((option, i) => option.setAttribute('aria-selected', String(i === active)));
        input.setAttribute('aria-activedescendant', options[active].id);
        options[active].scrollIntoView({ block: 'nearest' });
    };
    const addOption = (label, onSelect) => {
        const option = document.createElement('button');
        option.type = 'button';
        option.id = `${list.id}-item-${list.childElementCount}`;
        option.setAttribute('role', 'option');
        option.setAttribute('aria-selected', 'false');
        option.className = 'block w-full px-3 py-2 text-left text-sm text-slate-800 hover:bg-slate-100 focus:bg-slate-100 aria-selected:bg-slate-100';
        option.textContent = label;
        option.addEventListener('click', onSelect);
        list.append(option);
    };

    const search = pause(async () => {
        request?.abort();
        request = new AbortController();
        const value = input.value.trim().replace(/\s+/g, ' ');
        if (!value) {
            close();
            status.textContent = 'Pilih saran atau konfirmasi kategori baru.';
            return;
        }
        status.textContent = 'Mencari kategori…';
        try {
            const response = await fetch(`/api/job-categories?q=${encodeURIComponent(value)}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                signal: request.signal,
            });
            if (!response.ok) throw new Error();
            const { data } = await response.json();
            list.replaceChildren();
            data.forEach(({ name }) => addOption(name, () => {
                input.value = name;
                status.textContent = `Kategori ${name} dipilih.`;
                close();
                input.focus();
            }));
            if (!data.some(({ name }) => normalized(name) === normalized(value))) {
                addOption(`Tambahkan “${value}”`, async () => {
                    status.textContent = 'Menyimpan kategori…';
                    try {
                        const response = await fetch('/api/job-categories', {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            },
                            credentials: 'same-origin',
                            body: new URLSearchParams({ name: value }),
                        });
                        if (!response.ok) throw new Error();
                        const result = await response.json();
                        input.value = result.data.name;
                        status.textContent = `Kategori ${result.data.name} tersimpan.`;
                        close();
                        input.focus();
                    } catch {
                        status.textContent = 'Kategori belum tersimpan. Coba lagi atau simpan bersama lowongan.';
                    }
                });
            }
            list.classList.remove('hidden');
            input.setAttribute('aria-expanded', 'true');
            status.textContent = data.length ? 'Pilih kategori atau tambahkan yang baru.' : 'Belum ada kategori yang cocok.';
        } catch (error) {
            if (error.name !== 'AbortError') status.textContent = 'Saran kategori gagal dimuat. Anda masih dapat menyimpan lowongan.';
        }
    });

    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-expanded', 'false');
    input.addEventListener('input', search);
    input.addEventListener('focus', search);
    input.addEventListener('keydown', (event) => {
        if (list.classList.contains('hidden')) return;
        const options = list.querySelectorAll('[role="option"]');
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            setActive(active + (event.key === 'ArrowDown' ? 1 : -1));
        } else if (event.key === 'Enter' && active >= 0) {
            event.preventDefault();
            options[active].click();
        } else if (event.key === 'Escape') {
            close();
        }
    });
    input.addEventListener('blur', () => setTimeout(close, 150));
});
