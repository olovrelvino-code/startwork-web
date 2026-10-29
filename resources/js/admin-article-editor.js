const editor = document.querySelector('[data-article-editor]');

if (editor) {
    const form = editor.closest('form');
    const contentInput = form.querySelector('[name="content"]');
    const documentInput = editor.querySelector('[name="body_json"]');
    const blocksElement = editor.querySelector('[data-editor-blocks]');
    const status = editor.querySelector('[data-editor-status]');
    const fileInput = editor.querySelector('[data-editor-image-file]');
    const articleId = editor.dataset.articleId;
    let activeIndex = -1;
    let blocks;

    try {
        blocks = JSON.parse(documentInput.value).blocks;
        if (!Array.isArray(blocks)) throw new Error();
    } catch {
        blocks = contentInput.value.trim()
            ? contentInput.value.split(/\n\s*\n/).map((value) => ({ type: 'paragraph', text: value }))
            : [{ type: 'paragraph', text: '' }];
    }

    const element = (tag, className, label) => {
        const node = document.createElement(tag);
        node.className = className;
        if (label !== undefined) node.textContent = label;
        return node;
    };
    const iconPaths = {
        up: 'M12 19V5m-6 6 6-6 6 6',
        down: 'M12 5v14m-6-6 6 6 6-6',
        trash: 'M4 7h16M10 11v7m4-7v7M6 7l1 14h10l1-14M9 7V4h6v3',
        paragraph: 'M4 6h16M4 10h16M4 14h10M4 18h10',
        heading: 'M4 6v12M12 6v12M4 12h8M17 8h4M19 8v10',
        table: 'M3 4h18v16H3zM3 10h18M3 15h18M9 4v16M15 4v16',
        image: 'M3 4h18v16H3zM6 17l5-5 3 3 3-3 4 4M8 8h.01',
    };
    const icon = (name) => {
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('fill', 'none');
        svg.setAttribute('stroke', 'currentColor');
        svg.setAttribute('stroke-width', '1.8');
        svg.setAttribute('stroke-linecap', 'round');
        svg.setAttribute('stroke-linejoin', 'round');
        svg.setAttribute('aria-hidden', 'true');
        svg.setAttribute('focusable', 'false');
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', iconPaths[name]);
        svg.append(path);
        return svg;
    };
    const button = (label, action, disabled = false, iconName = null) => {
        const control = element('button', iconName ? 'article-editor-icon-button' : 'admin-button-quiet', iconName ? undefined : label);
        control.type = 'button';
        control.disabled = disabled;
        if (iconName) {
            control.append(icon(iconName));
            control.setAttribute('aria-label', label);
            control.title = label;
            if (iconName === 'trash') control.classList.add('article-editor-icon-button--danger');
        }
        control.addEventListener('click', action);
        return control;
    };
    const input = (value, label, onChange) => {
        const control = element('input', 'article-editor-cell');
        control.type = 'text';
        control.value = value;
        control.setAttribute('aria-label', label);
        control.addEventListener('input', () => onChange(control.value));
        return control;
    };
    const refresh = (focusIndex = null) => {
        blocksElement.replaceChildren();
        blocks.forEach((block, index) => {
            const section = element('section', 'article-editor-block');
            section.addEventListener('focusin', () => { activeIndex = index; });
            const header = element('div', 'article-editor-block-header');
            const names = {
                paragraph: 'Paragraf',
                heading: 'Heading ' + block.level,
                table: 'Tabel perbandingan',
                image: 'Gambar dalam isi',
            };
            header.append(element('strong', 'text-sm text-slate-800', (index + 1) + '. ' + (names[block.type] || 'Blok')));
            const actions = element('div', 'article-editor-actions');
            actions.append(
                button('Pindahkan blok ' + (index + 1) + ' ke atas', () => move(index, -1), index === 0, 'up'),
                button('Pindahkan blok ' + (index + 1) + ' ke bawah', () => move(index, 1), index === blocks.length - 1, 'down'),
                button('Hapus blok ' + (index + 1), () => {
                    if (!window.confirm('Hapus blok ini dari artikel?')) return;
                    blocks.splice(index, 1);
                    activeIndex = -1;
                    refresh();
                }, false, 'trash'),
            );
            header.append(actions);
            section.append(header);

            if (block.type === 'paragraph' || block.type === 'heading') {
                const field = element('textarea', 'admin-input');
                field.rows = block.type === 'paragraph' ? 4 : 2;
                field.value = block.text;
                field.setAttribute('aria-label', names[block.type] + ' ' + (index + 1));
                field.addEventListener('input', () => { block.text = field.value; });
                section.append(field);
            } else if (block.type === 'table') {
                const scroll = element('div', 'article-editor-table-scroll');
                const table = element('table', 'article-editor-table');
                const head = document.createElement('thead');
                const headRow = document.createElement('tr');
                block.headers.forEach((value, column) => {
                    const cell = document.createElement('th');
                    cell.append(input(value, 'Judul kolom ' + (column + 1), (text) => { block.headers[column] = text; }));
                    headRow.append(cell);
                });
                head.append(headRow);
                const body = document.createElement('tbody');
                block.rows.forEach((row, rowIndex) => {
                    const tr = document.createElement('tr');
                    row.forEach((value, column) => {
                        const cell = document.createElement('td');
                        cell.append(input(value, 'Baris ' + (rowIndex + 1) + ', kolom ' + (column + 1), (text) => { row[column] = text; }));
                        tr.append(cell);
                    });
                    body.append(tr);
                });
                table.append(head, body);
                scroll.append(table);
                section.append(scroll);
                const tableActions = element('div', 'article-editor-actions');
                tableActions.append(
                    button('Tambah baris', () => { block.rows.push(block.headers.map(() => '')); refresh(); }, block.rows.length >= 40),
                    button('Hapus baris terakhir', () => { block.rows.pop(); refresh(); }, block.rows.length < 2),
                    button('Tambah kolom', () => { block.headers.push(''); block.rows.forEach((row) => row.push('')); refresh(); }, block.headers.length >= 8),
                    button('Hapus kolom terakhir', () => { block.headers.pop(); block.rows.forEach((row) => row.pop()); refresh(); }, block.headers.length <= 2),
                );
                section.append(tableActions);
            } else if (block.type === 'image') {
                const preview = element('img', 'article-editor-image');
                preview.src = '/api/admin/articles/' + articleId + '/images/' + encodeURIComponent(block.file);
                preview.alt = block.alt || 'Pratinjau gambar artikel';
                section.append(preview);
                const altLabel = element('label', 'admin-label', 'Teks alternatif gambar');
                const alt = input(block.alt || '', 'Teks alternatif gambar ' + (index + 1), (text) => { block.alt = text; });
                alt.maxLength = 255;
                alt.required = true;
                altLabel.append(alt);
                const captionLabel = element('label', 'admin-label', 'Keterangan gambar (opsional)');
                const caption = input(block.caption || '', 'Keterangan gambar ' + (index + 1), (text) => { block.caption = text; });
                caption.maxLength = 500;
                captionLabel.append(caption);
                section.append(altLabel, captionLabel);
            }
            blocksElement.append(section);
        });
        if (focusIndex !== null) {
            const field = blocksElement.children[focusIndex]?.querySelector('textarea, input')
                || blocksElement.children[focusIndex]?.querySelector('button');
            field?.focus();
            blocksElement.children[focusIndex]?.scrollIntoView({ block: 'center' });
        }
    };
    const move = (index, direction) => {
        const next = index + direction;
        [blocks[index], blocks[next]] = [blocks[next], blocks[index]];
        activeIndex = next;
        refresh(next);
    };
    const add = (block) => {
        const position = activeIndex < 0 ? blocks.length : activeIndex + 1;
        blocks.splice(position, 0, block);
        activeIndex = position;
        refresh(position);
    };

    editor.querySelectorAll('[data-add-block]').forEach((control) => {
        const type = control.dataset.addBlock;
        control.prepend(icon(type.startsWith('heading') ? 'heading' : type));
        control.addEventListener('click', () => {
            if (type === 'image') {
                fileInput.click();
            } else if (type === 'table') {
                add({ type: 'table', headers: ['Aspek', 'Pilihan A', 'Pilihan B'], rows: [['', '', ''], ['', '', '']] });
            } else if (type.startsWith('heading')) {
                add({ type: 'heading', level: Number(type.slice(-1)), text: '' });
            } else {
                add({ type: 'paragraph', text: '' });
            }
        });
    });
    fileInput.addEventListener('change', async () => {
        const file = fileInput.files?.[0];
        if (!file) return;
        if (!['image/jpeg', 'image/png'].includes(file.type) || file.size > 2 * 1024 * 1024) {
            status.textContent = 'Pilih JPG atau PNG dengan ukuran maksimal 2 MB.';
            fileInput.value = '';
            return;
        }
        status.textContent = 'Mengunggah gambar…';
        const data = new FormData();
        data.append('image', file);
        try {
            const response = await fetch('/api/admin/articles/' + articleId + '/images', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                credentials: 'same-origin',
                body: data,
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(result.errors?.image?.[0] || result.message || 'Unggah gagal.');
            add({ type: 'image', file: result.file, alt: '', caption: '' });
            status.textContent = 'Gambar ditambahkan. Isi teks alternatif sebelum menyimpan.';
        } catch (error) {
            status.textContent = error.message;
        } finally {
            fileInput.value = '';
        }
    });

    form.addEventListener('submit', (event) => {
        documentInput.value = JSON.stringify({ blocks });
        contentInput.value = blocks.flatMap((block) => {
            if (block.text !== undefined) return block.text;
            if (block.type === 'table') return [...block.headers, ...block.rows.flat()].join(' ');
            return block.caption || '';
        }).join('\n').trim();
        if (form.querySelector('[name="status"]:checked')?.value === 'published' && !contentInput.value) {
            event.preventDefault();
            event.stopPropagation();
            status.textContent = 'Isi artikel wajib diisi sebelum diterbitkan.';
            editor.focus();
        }
    });

    contentInput.classList.add('hidden');
    contentInput.required = false;
    form.querySelector('[data-editor-fallback-help]')?.classList.add('hidden');
    editor.classList.remove('hidden');
    refresh();
}
