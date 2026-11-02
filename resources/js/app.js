import { animate, inView } from 'motion';

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const stopAnimations = [];
// Content is visible by default, including before hydration and when JS is unavailable.
if (!reducedMotion.matches) {
    inView('[data-reveal]', (element) => {
        if (reducedMotion.matches) return;
        const controls = animate(element, { transform: ['translateY(16px)', 'translateY(0px)'] }, { duration: 0.45, ease: 'easeOut' });
        stopAnimations.push(() => controls.complete());
    });
}
reducedMotion.addEventListener('change', ({ matches }) => {
    if (matches) stopAnimations.forEach((stop) => stop());
});

const menuToggle = document.querySelector('[data-menu-toggle]');
const menu = document.querySelector('#mobile-navigation');
function closeMenu() {
    if (!menu || !menuToggle) return;
    menu.hidden = true;
    menuToggle.setAttribute('aria-expanded', 'false');
    menuToggle.setAttribute('aria-label', 'Open navigation');
}
menuToggle?.addEventListener('click', () => {
    const open = menuToggle.getAttribute('aria-expanded') !== 'true';
    menu.hidden = !open;
    menuToggle.setAttribute('aria-expanded', String(open));
    menuToggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
});
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        if (menuToggle?.getAttribute('aria-expanded') === 'true') {
            closeMenu();
            menuToggle.focus();
        }
        document.querySelectorAll('[data-region-menu][open]').forEach((details) => {
            details.open = false;
            details.querySelector('summary').focus();
        });
    }
});
document.addEventListener('click', (event) => {
    document.querySelectorAll('[data-region-menu][open]').forEach((details) => {
        if (!details.contains(event.target)) details.open = false;
    });
});

const shareButton = document.querySelector('[data-share]');
shareButton?.addEventListener('click', async () => {
    const url = document.querySelector('link[rel="canonical"]')?.href || window.location.href;
    const status = document.querySelector('[data-share-status]');
    try {
        if (navigator.share) {
            await navigator.share({ title: document.title, url });
        } else if (navigator.clipboard) {
            await navigator.clipboard.writeText(url);
            status.textContent = 'Article link copied.';
        } else {
            status.textContent = `Share this article: ${url}`;
        }
    } catch (error) {
        if (error.name !== 'AbortError') status.textContent = `Share this article: ${url}`;
    }
});

document.querySelectorAll('[data-image-fallback]').forEach((image) => {
    const fallback = () => {
        image.removeEventListener('error', fallback);
        image.src = '/image-placeholder.svg';
    };
    image.addEventListener('error', fallback, { once: true });
    if (image.complete && image.naturalWidth === 0) fallback();
});

// Lottie is loaded only when the decorative newsletter icon enters the viewport.
// The bundled animation is original; no third-party animation URLs or trackers.
const lottieTarget = document.querySelector('[data-lottie-mail]');
if (lottieTarget && !reducedMotion.matches) {
    inView(lottieTarget, async () => {
        try {
            const [{ default: lottie }, { default: animationData }] = await Promise.all([
                import('lottie-web/build/player/lottie_light'),
                import('./mail-animation.json'),
            ]);
            if (reducedMotion.matches) return;
            const container = document.createElement('span');
            container.className = 'block h-8 w-8';
            const fallback = lottieTarget.firstElementChild;
            container.hidden = true;
            lottieTarget.append(container);
            const animation = lottie.loadAnimation({ container, renderer: 'svg', loop: false, autoplay: true, animationData });
            animation.addEventListener('DOMLoaded', () => {
                container.hidden = false;
                fallback?.remove();
            });
            animation.addEventListener('data_failed', () => container.remove());
            stopAnimations.push(() => animation.goToAndStop(45, true));
        } catch {
            // The server-rendered envelope remains useful if the optional animation fails.
        }
    });
}

document.querySelector('[data-newsletter-form]')?.addEventListener('submit', (event) => {
    if (!event.target.checkValidity()) return;
    const button = event.target.querySelector('button[type="submit"]');
    button.disabled = true;
    button.textContent = 'Saving…';
});

// Staff editing tools enhance the native form; no private draft is stored in the browser.
const articleEditor = document.querySelector('[data-article-editor]');
if (articleEditor) {
    let dirty = false;
    articleEditor.addEventListener('input', () => { dirty = true; });
    articleEditor.addEventListener('submit', () => { dirty = false; });
    window.addEventListener('beforeunload', (event) => {
        if (dirty) { event.preventDefault(); event.returnValue = ''; }
    });
    articleEditor.querySelectorAll('[data-insert-block]').forEach((button) => {
        button.addEventListener('click', () => {
            const body = articleEditor.querySelector('[name="body_markdown"]');
            body.setRangeText(`\n\n${button.dataset.insertBlock}\n\n`, body.selectionStart, body.selectionEnd, 'end');
            body.dispatchEvent(new Event('input', { bubbles: true }));
            body.focus();
        });
    });
    articleEditor.querySelectorAll('[data-character-count]').forEach((input) => {
        const output = articleEditor.querySelector(`[data-count-for="${input.id}"]`);
        const update = () => { output.textContent = `${[...input.value].length} characters · length alone does not determine search performance`; };
        input.addEventListener('input', update); update();
    });
}
const selectArticles = document.querySelector('[data-select-articles]');
selectArticles?.addEventListener('change', () => {
    document.querySelectorAll('[data-article-selection]').forEach((checkbox) => { checkbox.checked = selectArticles.checked; });
});

document.querySelector('[data-curation-form]')?.addEventListener('submit', (event) => {
    if (!event.target.checkValidity()) return;
    const button = event.target.querySelector('button[type="submit"]');
    button.disabled = true;
    button.textContent = 'Generating private draft…';
});

if (location.hash === '#ai-assistant') {
    const assistant = document.getElementById('ai-assistant');
    if (assistant?.tagName === 'DETAILS') assistant.open = true;
}
document.querySelectorAll('[data-copy-field]').forEach((button) => {
    button.addEventListener('click', async () => {
        const field = document.getElementById(button.dataset.copyField);
        const status = document.querySelector('[data-copy-status]');
        if (!field) return;
        try {
            await navigator.clipboard.writeText(field.value);
            if (status) status.textContent = 'Copied. Paste it into your chosen publishing tool.';
        } catch {
            field.focus(); field.select();
            if (status) status.textContent = 'Text selected. Use your browser’s copy command.';
        }
    });
});

const batchForm = document.getElementById('article-batch');
if (batchForm) {
    const selection = [...document.querySelectorAll('[data-article-selection]')];
    const update = () => {
        const count = selection.filter((input) => input.checked).length;
        batchForm.querySelector('[data-selection-count]').textContent = count ? `${count} selected on this page` : 'Select articles to apply a bulk action.';
        batchForm.querySelector('button[type="submit"]').disabled = count === 0;
        if (selectArticles) {
            selectArticles.indeterminate = count > 0 && count < selection.length;
            selectArticles.checked = count > 0 && count === selection.length;
        }
    };
    selection.forEach((input) => input.addEventListener('change', update));
    selectArticles?.addEventListener('change', update);
    update();
}

document.querySelectorAll('[data-bulk-confirm], [data-seo-action]').forEach((form) => {
    form.addEventListener('submit', () => {
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        button.textContent = 'Saving…';
    });
});
const seoBatch = document.querySelector('[data-seo-batch]');
if (seoBatch) {
    const poll = async () => {
        if (document.hidden) { setTimeout(poll, 5000); return; }
        try {
            const response = await fetch(seoBatch.dataset.statusUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' });
            if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) return;
            const data = await response.json();
            let pending = 0;
            for (const item of data.items) {
                const row = seoBatch.querySelector(`[data-batch-item="${Number(item.id)}"]`);
                if (row) {
                    row.querySelector('[data-item-status]').textContent = item.status.charAt(0).toUpperCase() + item.status.slice(1);
                    row.querySelector('[data-item-message]').textContent = item.message || 'Waiting for the background worker.';
                }
                if (['queued', 'researching', 'running'].includes(item.status)) pending++;
            }
            seoBatch.querySelector('[data-batch-summary]').textContent = `${data.counts.completed || 0} completed · ${data.counts.failed || 0} failed · ${data.counts.skipped || 0} skipped · ${pending} pending`;
            if (pending) setTimeout(poll, 5000);
        } catch {
            // Preserve the server-rendered results and manual refresh link.
        }
    };
    setTimeout(poll, 3000);
}
