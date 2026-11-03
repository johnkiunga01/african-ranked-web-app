import { test, expect } from '@playwright/test';

// Protected public smoke tests run only against scripts/browser-server.sh's
// disposable SQLite fixture. Never import article fixtures into production.
const failures = new WeakMap();
test.beforeEach(async ({ page }) => {
    failures.set(page, []);
    page.on('pageerror', (error) => failures.get(page).push(error.message));
});
test.afterEach(async ({ page }) => {
    expect(failures.get(page)).toEqual([]);
});

test('responsive homepage exposes actual routes without prototype banners', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    const response = await page.goto('/');
    expect(response.status()).toBe(200);
    expect(response.headers()['x-robots-tag']).toContain('noindex');
    await expect(page.locator('h1')).toHaveCount(1);
    await expect(page.locator('h1')).toContainText('Discover the best of Africa');
    await expect(page.locator('[data-hero-category]')).toHaveCount(6);
    await expect(page.locator('a[href="/rankings"]').first()).toBeVisible();
    await expect(page.locator('meta[name="robots"]')).toHaveAttribute('content', /noindex/);
    await expect(page.locator('script[type="application/ld+json"]')).toHaveCount(1);
    await expect(page.locator('body')).not.toContainText('Review version');
    await expect(page.locator('[data-newsletter-form]')).toHaveCount(0);
});

test('mobile navigation, region switch, article reading and share work', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/');
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
    const toggle = page.getByRole('button', { name: 'Open navigation' });
    await toggle.click();
    await expect(page.getByRole('navigation', { name: 'Mobile navigation' })).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(toggle).toBeFocused();
    await expect(page.locator('#mobile-navigation')).toBeHidden();

    await page.locator('[data-region-menu] summary').click();
    await page.getByRole('navigation', { name: 'Regional editions' }).getByRole('link', { name: /Kenya/ }).click();
    await expect(page).toHaveURL(/\/ke$/);

    await page.goto('/articles/top-10-safari-destinations-africa-2026');
    await expect(page.locator('h1')).toHaveCount(1);
    await expect(page.locator('aside[aria-label="Explore more African Ranked content"]')).toBeVisible();
    const mobileToc = page.locator('[data-mobile-toc]');
    if (await mobileToc.count()) {
        await mobileToc.locator('summary').click();
        await mobileToc.locator('a[href^="#"]').first().click();
        await expect(mobileToc).not.toHaveAttribute('open');
        await expect(page).toHaveURL(/#section-/);
    }
    await page.evaluate(() => {
        Object.defineProperty(navigator, 'share', { value: undefined, configurable: true });
        Object.defineProperty(navigator, 'clipboard', { value: undefined, configurable: true });
    });
    await page.getByRole('button', { name: 'Share', exact: true }).click();
    await expect(page.locator('[data-share-status]')).toContainText(/Share this article:/);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
});

test('public stories and topic links work without JavaScript', async ({ browser, baseURL }) => {
    const context = await browser.newContext({ javaScriptEnabled: false, baseURL, viewport: { width: 390, height: 844 } });
    try {
        const page = await context.newPage();
        await page.goto('/articles/top-10-safari-destinations-africa-2026');
        await expect(page.locator('h1')).toContainText('Safari');
        await expect(page.locator('#frequently-asked-questions')).toBeVisible();
        await expect(page.locator('[data-mobile-toc]')).toBeVisible();
        await expect(page.locator('nav[aria-label="Browse article categories"] a').first()).toBeVisible();
        await page.locator('nav[aria-label="Browse article categories"] a').first().click();
        await expect(page).toHaveURL(/\/categories\//);
    } finally {
        await context.close();
    }
});

test('reduced-motion and keyboard reading controls remain available', async ({ page }) => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.setViewportSize({ width: 820, height: 1180 });
    await page.goto('/');
    await expect(page.locator('h1')).toBeVisible();
    await expect(page.locator('[data-hero-category]')).toHaveCount(6);
    expect(await page.evaluate(() => matchMedia('(prefers-reduced-motion: reduce)').matches)).toBe(true);
    await page.keyboard.press('Tab');
    const focused = await page.evaluate(() => document.activeElement?.tagName);
    expect(['A', 'BUTTON', 'SUMMARY', 'INPUT']).toContain(focused);
    expect(await page.locator('img:not([alt])').count()).toBe(0);
});

test('preview discovery endpoints are accessible but not indexed', async ({ request }) => {
    for (const path of ['/sitemap.xml', '/feed.xml', '/robots.txt', '/llms.txt']) {
        const response = await request.get(path);
        expect(response.status(), path).toBe(200);
        expect(response.headers()['x-robots-tag'], path).toContain('noindex');
    }
    const missing = await request.get('/articles/not-a-real-article');
    expect(missing.status()).toBe(404);
    expect(missing.headers()['x-robots-tag']).toContain('noindex');
});
