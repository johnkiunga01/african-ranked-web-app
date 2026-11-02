import { test, expect } from '@playwright/test';

for (const viewport of [
    { width: 390, height: 844, label: 'mobile' },
    { width: 820, height: 1180, label: 'tablet' },
    { width: 1440, height: 900, label: 'desktop' },
]) {
    test(`immersive homepage is usable at ${viewport.label} size`, async ({ page }) => {
        await page.setViewportSize({ width: viewport.width, height: viewport.height });
        await page.goto('/');
        await expect(page.getByRole('heading', { level: 1 })).toContainText('Discover the best of Africa');
        await expect(page.locator('.ar-hero')).toBeVisible();
        await expect(page.locator('[data-hero-category]')).toHaveCount(6);
        await expect(page.getByRole('link', { name: /Explore rankings/i })).toHaveAttribute('href', '/rankings');
        await expect(page.getByRole('link', { name: /Browse categories/i })).toHaveAttribute('href', '#categories');

        const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
        expect(overflow, `Unexpected horizontal overflow on ${viewport.label}`).toBeLessThanOrEqual(1);

        await page.getByRole('link', { name: /Browse categories/i }).click();
        await expect(page.locator('#categories')).toBeVisible();
        const first = page.locator('[data-hero-category]').first();
        const href = await first.getAttribute('href');
        expect(href).toMatch(/^\/categories\/[a-z-]+$/);
        await first.click();
        await expect(page).toHaveURL(/\/categories\/[a-z-]+$/);
        await expect(page.locator('main h1')).toBeVisible();
    });
}

test('mobile navigation, edition choice and live search all lead somewhere', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto('/');
    await page.getByRole('button', { name: 'Open navigation' }).click();
    await expect(page.locator('#mobile-navigation')).toBeVisible();
    await expect(page.locator('#mobile-navigation').getByRole('link', { name: 'Practical guides' }))
        .toHaveAttribute('href', '/rankings?type=guide');
    await page.getByRole('button', { name: 'Close navigation' }).click();
    await expect(page.locator('#mobile-navigation')).toBeHidden();

    await page.getByLabel('Search African Ranked').click();
    await page.locator('#site-search').fill('safari');
    await page.getByRole('button', { name: 'Search', exact: true }).click();
    await expect(page).toHaveURL(/\/rankings\?q=safari/);
    await expect(page.locator('#ranking-search')).toHaveValue('safari');
});

test('reduced-motion users see the full hero without animated prerequisites', async ({ page }) => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.goto('/');
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
    await expect(page.locator('[data-hero-category]')).toHaveCount(6);
    await expect(page.getByRole('link', { name: /Explore rankings/i })).toBeVisible();
});
