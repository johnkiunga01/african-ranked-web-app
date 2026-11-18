import { test, expect } from '@playwright/test';

test('approach has working section navigation and no mobile overflow', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/strategy');
    await expect(page.locator('h1')).toHaveCount(1);
    await page.getByRole('navigation', { name: 'On this page' }).getByRole('link', { name: 'Corrections', exact: true }).click();
    await expect(page).toHaveURL(/#corrections$/);
    await expect(page.getByRole('heading', { name: /Spotted something/ })).toBeInViewport();
    await expect(page.getByRole('link', { name: /Suggest a correction/ })).toHaveAttribute('href', /^mailto:info@africanranked.com/);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
});

test('YouTube stays disconnected until a keyboard activation', async ({ page }) => {
    const youtubeRequests = [];
    const errors = [];
    page.on('pageerror', e => errors.push(e.message));
    page.on('request', request => {
        if (/youtube|ytimg/.test(new URL(request.url()).hostname)) youtubeRequests.push(request.url());
    });
    await page.route('https://www.youtube-nocookie.com/**', route => route.fulfill({ contentType: 'text/html', body: '<p>Synthetic player</p>' }));
    await page.goto('/staff/login');
    await page.getByLabel('Email address', { exact: true }).fill('author@browser.example.test');
    await page.getByLabel('Password', { exact: true }).fill('BrowserOnlyPassword123!');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.goto('/staff/articles?q=Current+workflow+demonstration');
    await page.locator('a[href$="/edit"]').first().click();
    const id = page.url().match(/articles\/(\d+)\/edit/)[1];
    const response = await page.goto(`/staff/articles/${id}/preview`);
    expect(response.headers()['content-security-policy']).toContain('frame-src https://www.youtube-nocookie.com');
    await expect(page.locator('[data-youtube-video]')).toHaveCount(1);
    await expect(page.locator('iframe')).toHaveCount(0);
    expect(youtubeRequests).toEqual([]);
    const load = page.getByRole('button', { name: 'Load video: Synthetic video demonstration' });
    await load.focus();
    await page.keyboard.press('Enter');
    await expect(page.locator('iframe')).toHaveCount(1);
    await expect(page.locator('iframe')).toHaveAttribute('src', 'https://www.youtube-nocookie.com/embed/AbCdEf123_-');
    await expect(page.locator('[data-load-video]')).toHaveCount(0);
    expect(youtubeRequests).toHaveLength(1);
    expect(errors).toEqual([]);
    expect((await page.request.get('/articles/recommendation-browser-demo')).status()).toBe(404);
});

test('planning hub sends readers to a real published guide', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/plan-safari');
    await expect(page.locator('h1')).toHaveCount(1);
    await expect(page.getByRole('navigation', { name: 'Safari planning steps' })).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    await page.locator('a[href^="/articles/"]').first().click();
    await expect(page).toHaveURL(/\/articles\//);
    await expect(page.locator('h1')).toHaveCount(1);
});
