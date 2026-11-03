import { animate, inView, stagger } from 'motion';

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

// Motion's vanilla JS API provides Framer-style entrance animation without React.
const siteHeader = document.querySelector('[data-site-header]');
if (siteHeader?.classList.contains('home-site-header')) {
    const setHeaderState = () => siteHeader.classList.toggle('is-scrolled', window.scrollY > 36);
    document.addEventListener('scroll', setHeaderState, { passive: true });
    setHeaderState();
}
const hero = document.querySelector('.ar-hero');
if (hero && !reducedMotion.matches) {
    ['[data-hero-intro]', '[data-hero-heading]', '[data-hero-description]', '[data-hero-actions]']
        .forEach((selector, index) => {
            const node = hero.querySelector(selector);
            if (node) animate(node, { opacity: [0, 1], transform: ['translateY(15px)', 'translateY(0px)'] },
                { duration: 0.56, delay: index * 0.09, ease: 'easeOut' });
        });
    const tiles = hero.querySelectorAll('[data-hero-category]');
    if (tiles.length) animate(tiles, { opacity: [0, 1], transform: ['translateY(12px)', 'translateY(0px)'] },
        { delay: stagger(0.045, { startDelay: 0.38 }), duration: 0.44, ease: 'easeOut' });
}
document.querySelectorAll('[data-search-menu]').forEach((details) => {
    details.addEventListener('toggle', () => {
        if (details.open) details.querySelector('[type="search"]')?.focus();
    });
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
        document.querySelectorAll('[data-region-menu][open], [data-search-menu][open]').forEach((details) => {
            details.open = false;
            details.querySelector('summary').focus();
        });
    }
});
document.addEventListener('click', (event) => {
    document.querySelectorAll('[data-region-menu][open], [data-search-menu][open]').forEach((details) => {
        if (!details.contains(event.target)) details.open = false;
    });
});

// Article reading guide: keep the active section visible without blocking reading.
const articleContentsNav = document.querySelector('nav[aria-label="Article section navigation"]');
if (articleContentsNav && 'IntersectionObserver' in window) {
    const links = [...articleContentsNav.querySelectorAll('a[href^="#"]')];
    const targets = links.map((link) => ({
        link,
        heading: document.getElementById(link.getAttribute('href').slice(1)),
    })).filter((entry) => entry.heading);
    const activate = (id) => {
        targets.forEach(({ link, heading }) => {
            if (heading.id === id) link.setAttribute('aria-current', 'location');
            else link.removeAttribute('aria-current');
        });
    };
    const observer = new IntersectionObserver((entries) => {
        const visible = entries.filter((entry) => entry.isIntersecting)
            .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
        if (visible.length) activate(visible[0].target.id);
    }, { rootMargin: '-112px 0px -68% 0px', threshold: 0 });
    targets.forEach(({ heading }) => observer.observe(heading));
    targets.forEach(({ link, heading }) => link.addEventListener('click', () => activate(heading.id)));
}
document.querySelectorAll('[data-mobile-toc] a[href^="#"]').forEach((link) => {
    link.addEventListener('click', () => {
        const parent = link.closest('details');
        if (parent) parent.open = false;
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
        if (image.closest('.ar-hero')) { image.style.opacity = '0'; return; }
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
// Fast, Facebook-style editorial image composer: drop/paste, preview, upload, insert.
// Uploads never auto-publish. Only HTTPS URLs from the authenticated media endpoint are used.
if (articleEditor) {
    const media = articleEditor.querySelector('[data-media-composer]');
    const fileInput = media?.querySelector('[data-media-file]');
    const preview = media?.querySelector('[data-media-preview]');
    const emptyPreview = media?.querySelector('[data-media-empty]');
    const altInput = media?.querySelector('[data-media-alt]');
    const creditInput = media?.querySelector('[data-media-credit]');
    const captionInput = media?.querySelector('[data-media-caption]');
    const rightsInput = media?.querySelector('[data-media-rights]');
    const coverButton = media?.querySelector('[data-media-as-cover]');
    const insertButton = media?.querySelector('[data-media-insert]');
    const clearButton = media?.querySelector('[data-media-clear]');
    const status = media?.querySelector('[data-media-status]');
    const body = articleEditor.querySelector('[name="body_markdown"]');
    let selectedFile = null;
    let objectUrl = null;
    let uploadedFile = null;
    let busy = false;

    const feedback = (message, error = false) => {
        if (!status) return;
        status.textContent = message;
        status.classList.toggle('text-red-700', error);
        status.classList.toggle('text-stone-600', !error);
    };
    const refreshActions = () => {
        const enabled = Boolean(selectedFile && rightsInput?.checked && (altInput?.value.trim().length || 0) >= 8) && !busy;
        if (coverButton) coverButton.disabled = !enabled;
        if (insertButton) insertButton.disabled = !enabled;
    };
    const releaseObjectUrl = () => {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = null;
    };
    const pickImage = (file) => {
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 8 * 1024 * 1024) {
            feedback('Use a JPG, PNG or WebP image under 8 MB.', true);
            return;
        }
        releaseObjectUrl();
        selectedFile = file;
        uploadedFile = null;
        objectUrl = URL.createObjectURL(file);
        preview.src = objectUrl;
        preview.classList.remove('hidden');
        emptyPreview?.classList.add('hidden');
        if (fileInput) fileInput.value = '';
        if (!altInput.value.trim()) {
            altInput.value = file.name.replace(/\.[a-z0-9]+$/i, '').replace(/[-_]+/g, ' ');
        }
        if (clearButton) clearButton.hidden = false;
        feedback('Photo ready. Add a description, confirm permission, then set it as cover or insert it into the story.');
        refreshActions();
    };
    fileInput?.addEventListener('change', () => pickImage(fileInput.files?.[0]));
    const dropzone = media?.querySelector('[data-media-dropzone]');
    for (const eventName of ['dragenter', 'dragover']) {
        dropzone?.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropzone.classList.add('border-amber-500', 'bg-amber-50');
        });
    }
    dropzone?.addEventListener('dragleave', () => dropzone.classList.remove('border-amber-500', 'bg-amber-50'));
    dropzone?.addEventListener('drop', (event) => {
        event.preventDefault();
        dropzone.classList.remove('border-amber-500', 'bg-amber-50');
        pickImage(event.dataTransfer?.files?.[0]);
    });
    body?.addEventListener('paste', (event) => {
        const image = [...(event.clipboardData?.files || [])].find((file) => file.type.startsWith('image/'));
        if (!image) return;
        event.preventDefault();
        pickImage(image);
        media?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
    articleEditor.querySelector('[data-editor-add-photo]')?.addEventListener('click', () => {
        media?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        fileInput?.click();
    });
    clearButton?.addEventListener('click', () => {
        releaseObjectUrl();
        selectedFile = null;
        uploadedFile = null;
        clearButton.hidden = true;
        feedback('Choose a photo to upload.');
        refreshActions();
        fileInput?.click();
    });
    altInput?.addEventListener('input', refreshActions);
    rightsInput?.addEventListener('change', refreshActions);

    const upload = async () => {
        if (uploadedFile && uploadedFile.original === selectedFile) return uploadedFile.result;
        const data = new FormData();
        data.append('file', selectedFile);
        data.append('alt', altInput.value.trim());
        data.append('credit', creditInput?.value.trim() || '');
        data.append('caption', captionInput?.value.trim() || '');
        data.append('rights_confirmed', rightsInput.checked ? '1' : '0');
        if (articleEditor.dataset.articleId) data.append('article_id', articleEditor.dataset.articleId);
        const response = await fetch(articleEditor.dataset.mediaUploadUrl, {
            method: 'POST',
            body: data,
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', Accept: 'application/json' },
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
            const explanation = Object.values(result.errors || {}).flat().join(' ') || result.message || 'Upload failed. Please retry.';
            throw new Error(explanation);
        }
        if (typeof result.url !== 'string' || !result.url.startsWith('https://')) {
            throw new Error('The server did not return a secure image URL.');
        }
        uploadedFile = { original: selectedFile, result };
        return result;
    };
    const addImage = async (type) => {
        if (busy || !selectedFile || !rightsInput.checked || altInput.value.trim().length < 8) return;
        busy = true;
        refreshActions();
        feedback('Uploading and optimising photo…');
        try {
            const result = await upload();
            if (type === 'cover') {
                const imageInput = articleEditor.querySelector('[name="image"]');
                imageInput.value = result.url;
                imageInput.dispatchEvent(new Event('input', { bubbles: true }));
                feedback('Cover photo selected. Save your draft to keep the change.');
            } else {
                const alt = result.alt.replace(/[[\]\r\n]/g, '');
                const insert = `\n\n![${alt}](${result.url})\n\n`;
                body.setRangeText(insert, body.selectionStart, body.selectionEnd, 'end');
                body.dispatchEvent(new Event('input', { bubbles: true }));
                feedback('Photo inserted into your article. Save your draft to keep it.');
                body.focus();
            }
        } catch (error) {
            feedback(error.message || 'Could not upload this photo.', true);
        } finally {
            busy = false;
            refreshActions();
        }
    };
    coverButton?.addEventListener('click', () => addImage('cover'));
    insertButton?.addEventListener('click', () => addImage('body'));
    window.addEventListener('pagehide', releaseObjectUrl);

    // Accessible read-only preview; content is built with textContent rather than injected HTML.
    const previewButton = articleEditor.querySelector('[data-editor-preview-toggle]');
    const writingPreview = articleEditor.querySelector('[data-editor-preview]');
    const renderPreview = () => {
        if (!writingPreview) return;
        writingPreview.replaceChildren();
        for (const line of (body?.value || '').split(/\r?\n/)) {
            if (!line.trim()) continue;
            let element;
            const heading = line.match(/^(##|###)\s+(.+)$/);
            const image = line.match(/^!\[([^\]]+)\]\((https:\/\/[^\s)]+)\)$/);
            if (heading) {
                element = document.createElement(heading[1].length === 2 ? 'h2' : 'h3');
                element.className = 'mb-2 mt-5 text-lg font-bold text-stone-950';
                element.textContent = heading[2];
            } else if (image) {
                element = document.createElement('figure');
                element.className = 'my-4 overflow-hidden rounded-xl border border-stone-200';
                const img = document.createElement('img');
                img.src = image[2];
                img.alt = image[1];
                img.loading = 'lazy';
                img.className = 'max-h-64 w-full object-cover';
                element.append(img);
            } else {
                element = document.createElement('p');
                element.className = 'my-2 whitespace-pre-wrap';
                element.textContent = line.replace(/^[-*]\s+/, '• ');
            }
            writingPreview.append(element);
        }
    };
    previewButton?.addEventListener('click', () => {
        const show = writingPreview.hidden;
        if (show) renderPreview();
        writingPreview.hidden = !show;
        previewButton.setAttribute('aria-expanded', String(show));
        previewButton.textContent = show ? 'Hide preview' : 'Preview formatting';
    });
    body?.addEventListener('input', () => { if (!writingPreview?.hidden) renderPreview(); });
    refreshActions();
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
