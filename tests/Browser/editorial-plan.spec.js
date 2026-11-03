import { test, expect } from '@playwright/test';
import { mkdir } from 'node:fs/promises';

test('publisher opens the plan and evidence editor while authors cannot access the plan', async ({ page, browser, baseURL }) => {
    await page.goto('/staff/login');
    await page.getByLabel('Email address', { exact: true }).fill('admin@browser.example.test');
    await page.getByLabel('Password', { exact: true }).fill('BrowserOnlyPassword123!');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await expect(page).toHaveURL(/\/staff$/);
    await page.getByRole('link', { name: 'Editorial plan', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Editorial plan', exact: true })).toBeVisible();
    await expect(page.getByLabel('Import the master guide or team workbook')).toBeVisible();
    await page.getByRole('link', { name: 'Dashboard', exact: true }).click();
    await page.getByRole('link', { name: 'New article', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Answer and evidence', exact: true })).toBeVisible();
    await expect(page.getByLabel('Dated key facts (four to eight)', { exact: true })).toBeVisible();
    await expect(page.getByLabel('Cited sources (five or more)', { exact: true })).toBeVisible();
    await mkdir('artifacts/editorial', { recursive: true });
    await page.screenshot({ path: 'artifacts/editorial/evidence-editor.png', fullPage: true });
    const authorContext = await browser.newContext({ baseURL });
    try {
        const author = await authorContext.newPage();
        await author.goto('/staff/login');
        await author.getByLabel('Email address', { exact: true }).fill('author@browser.example.test');
        await author.getByLabel('Password', { exact: true }).fill('BrowserOnlyPassword123!');
        await author.getByRole('button', { name: 'Sign in', exact: true }).click();
        expect((await authorContext.request.get('/staff/editorial-plan')).status()).toBe(403);
    } finally {
        await authorContext.close();
    }
});
