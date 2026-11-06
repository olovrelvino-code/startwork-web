import { Browser, sleep } from '../../.agents/skills/design-taste-frontend/scripts/lib/cdp.mjs';

const browser = await Browser.launch();
try {
    for (const width of [375, 390, 768, 1440]) {
        const page = await browser.newPage({ width, height: 900 });
        await page.goto('http://startwork.test/loker');
        const initial = await page.eval(() => ({
            overflow: document.documentElement.scrollWidth - innerWidth,
            panel: getComputedStyle(document.querySelector('[data-filter-panel]')).display,
            toggle: getComputedStyle(document.querySelector('[data-filter-toggle]')).display,
            pages: [...document.querySelectorAll('[data-list-pages] a')].map((a) => a.getAttribute('href')),
            errors: document.querySelector('[data-list-status]').textContent,
        }));
        if (initial.overflow > 0 || (width < 768 ? initial.panel !== 'none' : initial.panel === 'none')) throw new Error(`${width}: ${JSON.stringify(initial)}`);
        if (width < 768) {
            const opened = await page.eval(() => {
                document.querySelector('[data-filter-toggle]').click();
                document.querySelectorAll('[data-filter-section]').forEach((button) => button.click());
                document.querySelector('#jobs-location').value = 'Jakarta';
                document.querySelector('#jobs-location').dispatchEvent(new Event('input', { bubbles: true }));
                document.querySelector('[name="category[]"]').click();
                return {
                    panel: getComputedStyle(document.querySelector('[data-filter-panel]')).display,
                    count: document.querySelector('[data-filter-count]').textContent,
                    sections: [...document.querySelectorAll('[data-filter-section]')].map((button) => button.getAttribute('aria-expanded')),
                    overflow: document.documentElement.scrollWidth - innerWidth,
                };
            });
            if (opened.panel === 'none' || opened.count !== '2' || opened.sections.some((value) => value !== 'true') || opened.overflow > 0) throw new Error(`${width}: ${JSON.stringify(opened)}`);
            await page.eval(() => document.querySelector('[data-filter-close]').click());
            const closed = await page.eval(() => getComputedStyle(document.querySelector('[data-filter-panel]')).display);
            if (closed !== 'none') throw new Error(`${width}: close failed`);
        }
        console.log(width, JSON.stringify(initial));
        await page.close();
    }
    const page = await browser.newPage({ width: 390, height: 900 });
    await page.goto('http://startwork.test/loker?category[]=Technology%2FIT');
    const pagination = await page.eval(() => [...document.querySelectorAll('[data-list-pages] a')].map((a) => a.getAttribute('href')));
    if (!pagination.some((href) => href.includes('category%5B%5D=Technology%2FIT') && href.includes('page=2'))) throw new Error(`pagination: ${pagination}`);
    await page.eval(() => {
        document.querySelector('[data-filter-toggle]').click();
        document.querySelector('#jobs-location').value = 'Jakarta';
        document.querySelector('[name="work_type[]"][value="Remote"]').checked = true;
        document.querySelector('[data-filter-panel] button[type="submit"]').click();
    });
    await sleep(1200);
    const applied = await page.eval(() => ({ search: location.search, count: document.querySelector('[data-filter-count]').textContent, q: document.querySelector('#jobs-q').value }));
    if (!applied.search.includes('location=Jakarta') || !applied.search.includes('work_type') || applied.q !== 'a' || applied.count !== '2') throw new Error(`apply: ${JSON.stringify(applied)}`);
    await page.goto('http://startwork.test/loker' + applied.search);
    const refreshed = await page.eval(() => ({ location: document.querySelector('#jobs-location').value, remote: document.querySelector('[name="work_type[]"][value="Remote"]').checked, count: document.querySelector('[data-filter-count]').textContent }));
    if (refreshed.location !== 'Jakarta' || !refreshed.remote || refreshed.count !== '2') throw new Error(`refresh: ${JSON.stringify(refreshed)}`);
    await page.eval(() => document.querySelector('[data-filter-reset]').click());
    await sleep(1200);
    const reset = await page.eval(() => location.search);
    if (!reset.includes('q=a') || reset.includes('location=') || reset.includes('work_type')) throw new Error(`reset: ${reset}`);
    console.log('apply/refresh/reset/pagination', JSON.stringify({ applied, refreshed, reset }));
    await page.close();
} finally {
    await browser.close();
}
