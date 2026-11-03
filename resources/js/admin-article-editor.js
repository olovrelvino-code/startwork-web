const editor = document.querySelector('[data-article-editor]');
const adminArticleForm = document.querySelector('form[data-admin-form]');

// 1. FAQ Accordion Manager for Article Create and Edit
function initArticleFaqManager(form) {
    const container = form?.querySelector('[data-article-faq-container]');
    if (!container) return;

    const jsonInput = container.querySelector('#article-faq-json');
    const listElement = container.querySelector('[data-faq-list]');
    const emptyElement = container.querySelector('[data-faq-empty]');
    const addBtns = container.querySelectorAll('[data-add-faq], [data-add-faq-prompt]');

    let faqs = [];
    try {
        if (jsonInput?.value) {
            const parsed = JSON.parse(jsonInput.value);
            if (Array.isArray(parsed)) {
                faqs = parsed.map((f) => ({
                    question: typeof f?.question === 'string' ? f.question : '',
                    answer: typeof f?.answer === 'string' ? f.answer : '',
                    open: false,
                }));
            }
        }
    } catch {
        faqs = [];
    }

    const syncJson = () => {
        if (!jsonInput) return;
        const clean = faqs
            .map((f) => ({ question: (f.question || '').trim(), answer: (f.answer || '').trim() }))
            .filter((f) => f.question !== '' || f.answer !== '');
        jsonInput.value = clean.length > 0 ? JSON.stringify(clean) : '';
    };

    const render = () => {
        listElement.replaceChildren();
        if (faqs.length === 0) {
            emptyElement?.classList.remove('hidden');
            listElement.classList.add('hidden');
        } else {
            emptyElement?.classList.add('hidden');
            listElement.classList.remove('hidden');
        }

        faqs.forEach((faq, index) => {
            const details = document.createElement('details');
            details.className = 'jobseeker-faq rounded-xl border border-slate-200 bg-white p-4 transition-all shadow-xs';
            if (faq.open) details.open = true;

            const summary = document.createElement('summary');
            summary.className = 'flex cursor-pointer list-none items-center justify-between gap-3 font-semibold text-slate-900';

            const summaryLeft = document.createElement('div');
            summaryLeft.className = 'flex items-center gap-2.5 min-w-0';

            const badge = document.createElement('span');
            badge.className = 'grid h-6 w-6 shrink-0 place-items-center rounded-full bg-slate-200 text-xs font-bold text-slate-700';
            badge.textContent = String(index + 1);

            const title = document.createElement('span');
            title.className = 'truncate font-semibold text-sm text-slate-800';
            title.textContent = faq.question.trim() ? faq.question : `Pertanyaan ${index + 1} (belum diisi)`;

            summaryLeft.append(badge, title);

            const summaryRight = document.createElement('div');
            summaryRight.className = 'flex items-center gap-2 shrink-0';

            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'article-editor-icon-button article-editor-icon-button--danger h-8 w-8';
            deleteBtn.title = 'Hapus pertanyaan ini';
            deleteBtn.setAttribute('aria-label', `Hapus pertanyaan ${index + 1}`);
            deleteBtn.innerHTML = '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v7m4-7v7M6 7l1 14h10l1-14M9 7V4h6v3"/></svg>';
            deleteBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (window.confirm(`Hapus pertanyaan ${index + 1}?`)) {
                    faqs.splice(index, 1);
                    syncJson();
                    render();
                }
            });

            const indicator = document.createElement('span');
            indicator.className = 'jobseeker-faq-indicator text-xl font-normal leading-none text-slate-400';
            indicator.setAttribute('aria-hidden', 'true');
            indicator.textContent = '+';

            summaryRight.append(deleteBtn, indicator);
            summary.append(summaryLeft, summaryRight);

            const content = document.createElement('div');
            content.className = 'pt-4 space-y-3 border-t border-slate-100 mt-3';

            const qGroup = document.createElement('div');
            const qLabel = document.createElement('label');
            qLabel.className = 'admin-label text-xs';
            qLabel.textContent = 'Pertanyaan';
            const qInput = document.createElement('input');
            qInput.type = 'text';
            qInput.className = 'admin-input text-sm';
            qInput.value = faq.question;
            qInput.placeholder = 'Contoh: Apa persiapan dokumen utama?';
            qInput.maxLength = 255;
            qInput.addEventListener('input', () => {
                faq.question = qInput.value;
                title.textContent = faq.question.trim() ? faq.question : `Pertanyaan ${index + 1} (belum diisi)`;
                syncJson();
            });
            qGroup.append(qLabel, qInput);

            const aGroup = document.createElement('div');
            const aLabel = document.createElement('label');
            aLabel.className = 'admin-label text-xs';
            aLabel.textContent = 'Jawaban';
            const aTextarea = document.createElement('textarea');
            aTextarea.className = 'admin-input min-h-20 text-sm';
            aTextarea.value = faq.answer;
            aTextarea.placeholder = 'Tuliskan jawaban lengkap yang informatif...';
            aTextarea.maxLength = 2000;
            aTextarea.addEventListener('input', () => {
                faq.answer = aTextarea.value;
                syncJson();
            });
            aGroup.append(aLabel, aTextarea);

            content.append(qGroup, aGroup);
            details.append(summary, content);

            details.addEventListener('toggle', () => {
                faq.open = details.open;
            });

            listElement.append(details);
        });
    };

    addBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            faqs.push({ question: '', answer: '', open: true });
            syncJson();
            render();
            const lastDetails = listElement.lastElementChild;
            const input = lastDetails?.querySelector('input');
            input?.focus();
        });
    });

    form.addEventListener('submit', () => {
        syncJson();
    });

    render();
}

if (adminArticleForm) {
    initArticleFaqManager(adminArticleForm);
}

// 2. Article Block Editor & Hyperlink Tool
if (editor) {
    const form = editor.closest('form');
    const contentInput = form.querySelector('[name="content"]');
    const documentInput = editor.querySelector('[name="body_json"]');
    const blocksElement = editor.querySelector('[data-editor-blocks]');
    const status = editor.querySelector('[data-editor-status]');
    const fileInput = editor.querySelector('[data-editor-image-file]');
    const articleId = editor.dataset.articleId;

    // Hyperlink Dialog Elements
    const linkDialog = editor.querySelector('[data-link-dialog]');
    const linkDialogTitle = linkDialog?.querySelector('[data-link-dialog-title]');
    const linkTextInput = linkDialog?.querySelector('[data-link-text]');
    const linkUrlInput = linkDialog?.querySelector('[data-link-url]');
    const linkSaveBtn = linkDialog?.querySelector('[data-link-save]');
    const linkDeleteBtn = linkDialog?.querySelector('[data-link-delete]');
    const linkCancelBtns = linkDialog?.querySelectorAll('[data-link-cancel]');

    let linkTargetField = null;
    let linkTargetBlock = null;

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
        link: 'M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71',
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
        path.setAttribute('d', iconPaths[name] || iconPaths.paragraph);
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

    const handleHyperlink = (field, block) => {
        linkTargetField = field;
        linkTargetBlock = block;
        const text = field.value;
        const start = field.selectionStart ?? 0;
        const end = field.selectionEnd ?? 0;

        // Check if cursor/selection intersects an existing markdown link [label](url)
        const regex = /\[([^\]]+)\]\(([^)]+)\)/g;
        let match;
        let existing = null;
        while ((match = regex.exec(text)) !== null) {
            const mStart = match.index;
            const mEnd = match.index + match[0].length;
            if ((start >= mStart && start <= mEnd) || (end >= mStart && end <= mEnd) || (start <= mStart && end >= mEnd)) {
                existing = {
                    full: match[0],
                    label: match[1],
                    url: match[2],
                    start: mStart,
                    end: mEnd,
                };
                break;
            }
        }

        const selectedText = text.substring(start, end).trim();

        if (existing) {
            if (linkDialogTitle) linkDialogTitle.textContent = 'Edit Hyperlink';
            if (linkTextInput) linkTextInput.value = existing.label;
            if (linkUrlInput) linkUrlInput.value = existing.url;
            linkDeleteBtn?.classList.remove('hidden');
            if (linkDialog) {
                linkDialog.dataset.mode = 'edit';
                linkDialog.dataset.start = String(existing.start);
                linkDialog.dataset.end = String(existing.end);
            }
        } else {
            if (linkDialogTitle) linkDialogTitle.textContent = 'Tambah Hyperlink';
            if (linkTextInput) linkTextInput.value = selectedText;
            if (linkUrlInput) linkUrlInput.value = '';
            linkDeleteBtn?.classList.add('hidden');
            if (linkDialog) {
                linkDialog.dataset.mode = 'add';
                linkDialog.dataset.start = String(start);
                linkDialog.dataset.end = String(end);
            }
        }

        if (linkDialog && typeof linkDialog.showModal === 'function') {
            linkDialog.showModal();
            if (linkTextInput?.value) {
                linkUrlInput?.focus();
            } else {
                linkTextInput?.focus();
            }
        } else {
            const defaultText = selectedText || (existing ? existing.label : '');
            const label = window.prompt('Teks Tautan:', defaultText);
            if (!label) return;
            const url = window.prompt('URL Tautan:', existing ? existing.url : 'https://');
            if (url) {
                let safeUrl = url.trim();
                if (!safeUrl.startsWith('http://') && !safeUrl.startsWith('https://') && !safeUrl.startsWith('/') && !safeUrl.startsWith('mailto:')) {
                    safeUrl = 'https://' + safeUrl;
                }
                const replacement = `[${label.trim()}](${safeUrl})`;
                const rStart = existing ? existing.start : start;
                const rEnd = existing ? existing.end : end;
                field.setRangeText(replacement, rStart, rEnd, 'select');
                block.text = field.value;
            }
        }
    };

    linkSaveBtn?.addEventListener('click', () => {
        const label = linkTextInput?.value.trim() || '';
        let url = linkUrlInput?.value.trim() || '';
        if (!label) {
            linkTextInput?.focus();
            return;
        }
        if (!url) {
            linkUrlInput?.focus();
            return;
        }
        if (!url.startsWith('http://') && !url.startsWith('https://') && !url.startsWith('/') && !url.startsWith('mailto:')) {
            url = 'https://' + url;
        }
        const start = Number(linkDialog?.dataset.start || 0);
        const end = Number(linkDialog?.dataset.end || 0);
        const replacement = `[${label}](${url})`;
        if (linkTargetField) {
            linkTargetField.setRangeText(replacement, start, end, 'select');
            if (linkTargetBlock) linkTargetBlock.text = linkTargetField.value;
            linkTargetField.dispatchEvent(new Event('input'));
        }
        linkDialog?.close();
        linkTargetField?.focus();
    });

    linkDeleteBtn?.addEventListener('click', () => {
        const start = Number(linkDialog?.dataset.start || 0);
        const end = Number(linkDialog?.dataset.end || 0);
        const label = linkTextInput?.value.trim() || '';
        if (linkTargetField) {
            linkTargetField.setRangeText(label, start, end, 'select');
            if (linkTargetBlock) linkTargetBlock.text = linkTargetField.value;
            linkTargetField.dispatchEvent(new Event('input'));
        }
        linkDialog?.close();
        linkTargetField?.focus();
    });

    linkCancelBtns?.forEach((btn) => {
        btn.addEventListener('click', () => {
            linkDialog?.close();
            linkTargetField?.focus();
        });
    });

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

            let field = null;
            if (block.type === 'paragraph' || block.type === 'heading') {
                field = element('textarea', 'admin-input');
                field.rows = block.type === 'paragraph' ? 4 : 2;
                field.value = block.text;
                field.setAttribute('aria-label', names[block.type] + ' ' + (index + 1));
                field.addEventListener('input', () => { block.text = field.value; });

                actions.append(
                    button('Hyperlink teks pada blok ' + (index + 1), () => handleHyperlink(field, block), false, 'link'),
                );
            }

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
                fileInput?.click();
            } else if (type === 'table') {
                add({ type: 'table', headers: ['Aspek', 'Pilihan A', 'Pilihan B'], rows: [['', '', ''], ['', '', '']] });
            } else if (type.startsWith('heading')) {
                add({ type: 'heading', level: Number(type.slice(-1)), text: '' });
            } else {
                add({ type: 'paragraph', text: '' });
            }
        });
    });

    const formatLinkBtn = editor.querySelector('[data-format="link"]');
    if (formatLinkBtn) {
        formatLinkBtn.prepend(icon('link'));
        formatLinkBtn.addEventListener('click', () => {
            const targetSection = activeIndex >= 0 ? blocksElement.children[activeIndex] : null;
            const field = targetSection?.querySelector('textarea');
            const block = activeIndex >= 0 ? blocks[activeIndex] : null;
            if (field && block && (block.type === 'paragraph' || block.type === 'heading')) {
                handleHyperlink(field, block);
            } else {
                const firstTextField = blocksElement.querySelector('textarea');
                const firstBlock = blocks.find((b) => b.type === 'paragraph' || b.type === 'heading');
                if (firstTextField && firstBlock) {
                    handleHyperlink(firstTextField, firstBlock);
                } else {
                    add({ type: 'paragraph', text: '' });
                    const newField = blocksElement.children[activeIndex]?.querySelector('textarea');
                    if (newField) handleHyperlink(newField, blocks[activeIndex]);
                }
            }
        });
    }

    fileInput?.addEventListener('change', async () => {
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
