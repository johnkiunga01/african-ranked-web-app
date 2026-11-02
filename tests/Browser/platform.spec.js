import { test, expect } from '@playwright/test';
import { mkdir } from 'node:fs/promises';

const errors = new WeakMap();
test.beforeEach(async ({ page }) => {
    errors.set(page, []);
    page.on('pageerror', (error) => errors.get(page).push(error.message));
});
test.afterEach(async ({ page }) => {
    expect(errors.get(page)).toEqual([]);
});

async function capture(page, filename) {
    await mkdir('artifacts/previews', { recursive: true });
    // Settle image delivery/fallbacks for full-page captures, including below the fold.
    await page.locator('img').evaluateAll((images) => images.forEach((image) => { image.loading = 'eager'; }));
    await page.waitForLoadState('networkidle');
    await page.screenshot({ path: `artifacts/previews/${filename}.png`, fullPage: true });
}

test('desktop preview keeps the design, server content, metadata, and Lottie', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 1000 });
    const response = await page.goto('/');
    expect(response.headers()['x-robots-tag']).toBe('noindex, nofollow, noarchive');
    await expect(page.locator('h1')).toContainText('The best of Africa,');
    await expect(page.locator('#categories a')).toHaveCount(6);
    await expect(page.locator('link[rel="canonical"]')).toHaveAttribute('href', 'http://127.0.0.1:8010');
    expect(await page.locator('script[type="application/ld+json"]').evaluate((node) => JSON.parse(node.textContent)['@graph'].some((item) => item['@type'] === 'WebSite'))).toBe(true);
    await page.locator('#newsletter').scrollIntoViewIfNeeded();
    await expect(page.locator('[data-lottie-mail] span svg')).toBeVisible();
    await capture(page, 'desktop-home');
});

test('mobile menu, edition selection, article links, anchors, and share fallback', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/');
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    await capture(page, 'mobile-home');
    const toggle = page.getByRole('button', { name: 'Open navigation' });
    await toggle.click();
    await expect(page.getByRole('navigation', { name: 'Mobile navigation' })).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(toggle).toBeFocused();
    await expect(page.locator('#mobile-navigation')).toBeHidden();
    await page.locator('[data-region-menu] summary').click();
    await page.getByRole('navigation', { name: 'Regional editions' }).getByRole('link', { name: 'Kenya' }).click();
    await expect(page).toHaveURL(/\/ke$/);
    await page.locator('#featured h3 a').click();
    await expect(page).toHaveURL(/\/ke\/articles\//);
    await expect(page.locator('h1')).toHaveCount(1);
    await expect(page.locator('link[rel="canonical"]')).toHaveAttribute('href', /8010\/articles\//);
    await page.getByRole('navigation', { name: 'On this page' }).getByRole('link').first().click();
    await expect(page).toHaveURL(/#section-/);
    await page.evaluate(() => {
        Object.defineProperty(navigator, 'share', { value: undefined, configurable: true });
        Object.defineProperty(navigator, 'clipboard', { value: undefined, configurable: true });
    });
    await page.getByRole('button', { name: 'Share', exact: true }).click();
    await expect(page.locator('[data-share-status]')).toContainText('Share this article:');
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    await capture(page, 'mobile-article');
});

test('newsletter uses real CSRF, browser validation, consent, and a local test database', async ({ page, request }) => {
    await page.goto('/ke');
    const csrf = await request.post('/newsletter', { form: { email: 'csrf-test@example.test', region: 'ke', consent: '1' } });
    expect(csrf.status()).toBe(419);
    await page.locator('#newsletter').scrollIntoViewIfNeeded();
    await page.locator('[data-newsletter-form] button[type="submit"]').click();
    expect(await page.locator('#newsletter-email').evaluate((input) => input.validity.valueMissing)).toBe(true);
    await page.getByLabel('Email address', { exact: true }).fill('browser-preview@example.test');
    await page.getByRole('checkbox').check();
    await page.locator('[data-newsletter-form] button[type="submit"]').click();
    await expect(page.getByRole('status').filter({ hasText: 'Your subscription request has been saved' })).toBeVisible();
    await page.goto('/ke');
    await page.getByLabel('Email address', { exact: true }).fill('browser-preview@example.test');
    await page.getByRole('checkbox').check();
    await page.locator('[data-newsletter-form] button[type="submit"]').click();
    await expect(page.getByRole('status').filter({ hasText: 'Your subscription request has been saved' })).toBeVisible();
});

test('article reading and navigation work without JavaScript', async ({ browser, baseURL }) => {
    const context = await browser.newContext({ javaScriptEnabled: false, baseURL, viewport: { width: 390, height: 844 } });
    try {
        const page = await context.newPage();
        await page.goto('/articles/top-10-safari-destinations-africa-2026');
        await expect(page.locator('h1')).toContainText('Safari');
        await expect(page.locator('#frequently-asked-questions')).toBeVisible();
        await expect(page.locator('script[type="application/ld+json"]')).toHaveCount(1);
        await page.getByRole('navigation', { name: 'Categories', exact: true }).getByRole('link', { name: 'Travel', exact: true }).click();
        await expect(page).toHaveURL(/\/categories\/travel$/);
    } finally {
        await context.close();
    }
});

test.describe('reduced motion', () => {
    test.use({ reducedMotion: 'reduce' });
    test('retains the static envelope and readable content', async ({ page }) => {
        await page.goto('/');
        await page.locator('#newsletter').scrollIntoViewIfNeeded();
        await expect(page.locator('[data-lottie-mail] > svg')).toBeVisible();
        await expect(page.locator('[data-lottie-mail] span svg')).toHaveCount(0);
        expect(await page.evaluate(() => matchMedia('(prefers-reduced-motion: reduce)').matches)).toBe(true);
        await expect(page.locator('h1')).toBeVisible();
    });
});

test('discovery is inspectable and preview responses remain noindex', async ({ request }) => {
    for (const path of ['/sitemap.xml', '/feed.xml', '/robots.txt', '/llms.txt']) {
        const response = await request.get(path);
        expect(response.status()).toBe(200);
        expect(response.headers()['x-robots-tag']).toContain('noindex');
    }
    const missing = await request.get('/articles/not-a-real-article');
    expect(missing.status()).toBe(404);
    expect(missing.headers()['x-robots-tag']).toContain('noindex');
});
