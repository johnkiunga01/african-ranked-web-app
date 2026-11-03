import { publicationBody, publicationFaqs, publicationEvidence } from './publication-fixture.js';
import { test, expect } from '@playwright/test';
import { mkdir } from 'node:fs/promises';

async function signIn(page, email) {
    await page.goto('/staff/login');
    await page.getByLabel('Email address', { exact: true }).fill(email);
    await page.getByLabel('Password', { exact: true }).fill('BrowserOnlyPassword123!');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await expect(page).toHaveURL(/\/staff$/);
}

test('author and administrator complete a reviewed publishing cycle with revision isolation', async ({ browser, baseURL, request }) => {
    const authorContext = await browser.newContext({ baseURL });
    const adminContext = await browser.newContext({ baseURL });
    const otherContext = await browser.newContext({ baseURL });
    const author = await authorContext.newPage();
    const admin = await adminContext.newPage();
    const other = await otherContext.newPage();
    const errors = [];
    for (const page of [author, admin, other]) page.on('pageerror', (error) => errors.push(error.message));
    try {
        await signIn(author, 'author@browser.example.test');
        await author.getByRole('link', { name: 'New article', exact: true }).click();
        await author.getByLabel('Article title', { exact: true }).fill('Browser-reviewed safari planning checklist');
        await author.getByLabel('Page address', { exact: true }).fill('browser-reviewed-safari-checklist');
        await author.getByLabel('Short introduction', { exact: true }).fill('A fake browser fixture demonstrating safe editorial publication.');
        await author.getByLabel('Category', { exact: true }).selectOption('travel');
        await author.getByLabel('Article body', { exact: true }).fill(publicationBody('Fixture content, not editorial reporting.\n\n## Before you go\n\n- Confirm your research\n- Review your sources'));
        await author.getByLabel('Hero image URL', { exact: true }).fill('https://images.pexels.com/photos/4577793/pexels-photo-4577793.jpeg?w=600');
        await author.getByLabel('Reader FAQs', { exact: true }).fill(publicationFaqs);
        await author.getByLabel('Sources and research notes', { exact: true }).fill('Test fixture only. Used to validate review controls in a disposable database.');
        await publicationEvidence(author);
        await author.getByRole('button', { name: 'Create draft', exact: true }).click();
        await expect(author).toHaveURL(/\/staff\/articles\/\d+\/edit$/);
        const id = new URL(author.url()).pathname.match(/articles\/(\d+)/)[1];
        const privateResponse = await request.get('/articles/browser-reviewed-safari-checklist');
        expect(privateResponse.status()).toBe(404);
        const token = await author.locator('form input[name="_token"]').first().inputValue();
        const revision = await author.locator('input[name="revision_id"]').first().inputValue();
        const forged = await authorContext.request.post(`/staff/articles/${id}/publish`, { form: { _token: token, revision_id: revision } });
        expect(forged.status()).toBe(403);
        await signIn(other, 'other@browser.example.test');
        expect((await otherContext.request.get(`/staff/articles/${id}/edit`)).status()).toBe(403);
        if (!(await author.locator('#publishing-options').getAttribute('open') !== null)) await author.locator('#publishing-options > summary').click();
        await author.getByRole('button', { name: 'Submit for review', exact: true }).click();
        await expect(author.getByRole('status').first()).toContainText('submitted for review');
        await signIn(admin, 'admin@browser.example.test');
        await admin.goto(`/staff/articles/${id}/edit`);
        await admin.getByLabel('Feedback for the author', { exact: true }).fill('Add the missing practical checklist detail.');
        await admin.getByRole('button', { name: 'Request changes', exact: true }).click();
        await author.reload();
        await expect(author.getByText('Add the missing practical checklist detail.', { exact: true })).toBeVisible();
        await author.getByLabel('Article body', { exact: true }).fill(publicationBody('Fixture content revised for testing.\n\n## Before you go\n\n- Confirm your research\n- Review your sources\n- Check completeness'));
        await publicationEvidence(author);
        await author.getByRole('button', { name: 'Save article', exact: true }).click();
        if (!(await author.locator('#publishing-options').getAttribute('open') !== null)) await author.locator('#publishing-options > summary').click();
        await author.getByRole('button', { name: 'Submit for review', exact: true }).click();
        await admin.reload();
        for (const label of ['Facts and sources verified', 'Content and promised ranking complete', 'Author and attribution confirmed', 'Image permission confirmed']) {
            await admin.getByLabel(label, { exact: true }).check();
        }
        await admin.getByRole('button', { name: 'Approve this revision', exact: true }).click();
        await admin.getByRole('button', { name: 'Publish approved revision', exact: true }).click();
        const published = await request.get('/articles/browser-reviewed-safari-checklist');
        expect(published.status()).toBe(200);
        expect(await published.text()).toContain('Browser-reviewed safari planning checklist');
        await author.reload();
        await author.getByLabel('Article title', { exact: true }).fill('Private unpublished replacement title');
        await publicationEvidence(author);
        await author.getByRole('button', { name: 'Save article', exact: true }).click();
        const unchanged = await request.get('/articles/browser-reviewed-safari-checklist');
        expect(await unchanged.text()).toContain('Browser-reviewed safari planning checklist');
        expect(await unchanged.text()).not.toContain('Private unpublished replacement title');
        await mkdir('artifacts/staff', { recursive: true });
        await author.screenshot({ path: 'artifacts/staff/author-editor.png', fullPage: true });
        await admin.goto('/staff');
        await admin.screenshot({ path: 'artifacts/staff/admin-overview.png', fullPage: true });
        expect(errors).toEqual([]);
    } finally {
        await authorContext.close(); await adminContext.close(); await otherContext.close();
    }
});

test('administrator invites an author who sets a password; new account cannot manage staff', async ({ browser, baseURL }) => {
    const adminContext = await browser.newContext({ baseURL });
    const invitedContext = await browser.newContext({ baseURL, viewport: { width: 390, height: 844 } });
    try {
        const admin = await adminContext.newPage();
        await signIn(admin, 'admin@browser.example.test');
        await admin.getByRole('link', { name: 'Team', exact: true }).click();
        await admin.getByLabel('Author name', { exact: true }).fill('Invited Browser Author');
        await admin.getByLabel('Author email', { exact: true }).fill('invited@browser.example.test');
        await admin.getByRole('button', { name: 'Create author invitation', exact: true }).click();
        const link = await admin.getByRole('link').filter({ hasText: /\/staff\/invitations\// }).getAttribute('href');
        const page = await invitedContext.newPage();
        await page.goto(link);
        await page.getByLabel('Your name', { exact: true }).fill('Invited Browser Author');
        await page.getByLabel('Choose a password', { exact: true }).fill('InvitedBrowserPassword123!');
        await page.getByLabel('Confirm password', { exact: true }).fill('InvitedBrowserPassword123!');
        await page.getByRole('button', { name: 'Create my account', exact: true }).click();
        await expect(page).toHaveURL(/\/staff$/);
        await expect(page.getByRole('navigation', { name: 'Staff navigation' }).getByRole('link', { name: 'Team', exact: true })).toHaveCount(0);
        expect((await invitedContext.request.get('/staff/users')).status()).toBe(403);
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
        await page.getByRole('button', { name: 'Sign out', exact: true }).click();
        await expect(page).toHaveURL(/\/staff\/login$/);
        expect((await invitedContext.request.get(link)).status()).toBe(404);
    } finally { await adminContext.close(); await invitedContext.close(); }
});

test('login throttling persists across separate HTTP requests', async ({ page }) => {
    const request = page.context().request;
    await page.goto('/staff/login');
    const token = await page.locator('input[name="_token"]').inputValue();
    for (let i = 0; i < 5; i++) {
        const response = await request.post('/staff/login', { form: { _token: token, email: 'rate-check@browser.example.test', password: 'wrong-password' }, maxRedirects: 0 });
        expect(response.status()).toBe(302);
    }
    const blocked = await request.post('/staff/login', { form: { _token: token, email: 'rate-check@browser.example.test', password: 'wrong-password' }, maxRedirects: 0 });
    expect(blocked.status()).toBe(429);
});

test('expired login form recovers with a fresh CSRF token and retains CSRF protection', async ({ page, context }) => {
    await page.goto('/staff/login');
    const staleToken = await page.locator('input[name="_token"]').inputValue();
    await context.clearCookies();
    const response = await context.request.post('/staff/login', {
        form: { _token: staleToken, email: 'admin@browser.example.test', password: 'BrowserOnlyPassword123!' },
        maxRedirects: 0,
    });
    expect(response.status()).toBe(303);
    await page.goto('/staff/login');
    await expect(page.getByRole('status').first()).toContainText('sign-in form expired');
    await page.getByLabel('Email address', { exact: true }).fill('admin@browser.example.test');
    await page.getByLabel('Password', { exact: true }).fill('BrowserOnlyPassword123!');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await expect(page).toHaveURL(/\/staff$/);
    await page.getByRole('link', { name: 'SEO', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Search & content quality', exact: true })).toBeVisible();
    await page.getByRole('link', { name: 'Integrations', exact: true }).first().click();
    await expect(page.getByRole('heading', { name: 'Google Analytics 4' })).toBeVisible();
});

test('article inventory supports search, bulk choices and mobile navigation', async ({ page }) => {
    await signIn(page, 'admin@browser.example.test');
    await page.goto('/staff/articles');
    await expect(page.getByRole('heading', { name: 'Articles', exact: true })).toBeVisible();
    await expect(page.locator('#batch-operation option[value=publish]')).toHaveText('Publish selected…');
    await expect(page.locator('#batch-operation option[value=gemini]')).toHaveText('Research & optimise with Gemini…');
    await expect(page.getByText('Import to CMS', { exact: true })).toHaveCount(0);
    await page.screenshot({ path: 'artifacts/design/03-articles-desktop.png', fullPage: true });
    await page.getByLabel('Search title or URL').fill('browser-inventory-01');
    await page.getByRole('button', { name: 'Search', exact: true }).last().click();
    await expect(page.getByRole('link', { name: 'Inventory fixture 01', exact: true })).toBeVisible();
    await page.setViewportSize({ width: 390, height: 844 });
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    await page.screenshot({ path: 'artifacts/design/04-articles-mobile.png', fullPage: true });
    await page.getByText('Workspace menu', { exact: true }).click();
    await page.getByRole('link', { name: 'Integrations', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'OpenSEO MCP connection', exact: true })).toBeVisible();
    await page.screenshot({ path: 'artifacts/design/05-integrations-mobile.png', fullPage: true });
});

test('editor manages SEO fields and FAQs, analyzes saved content and restores historical drafts', async ({ page }) => {
    await signIn(page, 'admin@browser.example.test');
    await page.goto('/staff/articles/create');
    await page.getByLabel('Article title', { exact: true }).fill('Top 5 fixture planning checks');
    await page.getByLabel('Page address', { exact: true }).fill('advanced-editor-browser-fixture');
    await page.getByLabel('Short introduction', { exact: true }).fill('An introduction for a disposable editor fixture.');
    await page.getByLabel('Category', { exact: true }).selectOption('travel');
    await page.getByLabel('Article body', { exact: true }).fill('## 1. One supported fixture entry\n\nA clear answer for a fixture reader.\n\n[Related reading](/articles/missing-fixture-target)');
    await page.getByLabel('Hero image URL', { exact: true }).fill('https://example.test/fixture.jpg');
    await page.getByLabel('Hero image description', { exact: true }).fill('Fixture image only');
    await page.getByText('Adjust search appearance (optional)', { exact: true }).click();
    await page.getByLabel('Search title override', { exact: true }).fill('Fixture search title');
    await page.getByLabel('Search description override', { exact: true }).fill('Fixture search description.');
    await page.getByLabel('Research target query', { exact: true }).fill('fixture planning checks');
    await page.getByLabel('Reader FAQs', { exact: true }).fill('What is this?\nA disposable browser fixture.');
    await page.getByLabel('Sources and research notes', { exact: true }).fill('Fixture notes; not real editorial evidence.');
    await page.getByRole('button', { name: 'Create draft', exact: true }).click();
    await expect(page.getByRole('status').first()).toContainText('Draft created');
    const editUrl = page.url();
    await expect(page.getByLabel('Reader FAQs', { exact: true })).toHaveValue('What is this?\nA disposable browser fixture.');
    await page.screenshot({ path: 'artifacts/design/06-advanced-editor.png', fullPage: true });
    await page.getByRole('link', { name: 'Open SEO analysis', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Ranking completeness needs checking', exact: true })).toBeVisible();
    await expect(page.getByText('Fixture search title', { exact: true })).toBeVisible();
    await page.screenshot({ path: 'artifacts/design/07-article-seo.png', fullPage: true });
    await page.goto(editUrl);
    await page.getByLabel('Article title', { exact: true }).fill('A newer working fixture title');
    await page.getByRole('button', { name: 'Save article', exact: true }).click();
    await page.getByRole('link', { name: 'Compare / restore', exact: true }).last().click();
    await expect(page.getByRole('heading', { name: 'Compare revisions', exact: true })).toBeVisible();
    await page.getByRole('button', { name: 'Restore as new private draft', exact: true }).click();
    await expect(page.getByRole('status').first()).toContainText('restored as a new private draft');
    await expect(page.getByLabel('Article title', { exact: true })).toHaveValue('Top 5 fixture planning checks');
    await page.goto('/staff/articles?per_page=10&sort=title');
    await page.getByRole('link', { name: /Next/ }).first().click();
    await expect(page).toHaveURL(/page=2/);
});

test('provider keys are saved through a private admin form and never redisplayed', async ({ page }) => {
    await signIn(page, 'credentials@browser.example.test');
    await page.goto('/staff/growth/integrations');
    const provider = page.getByRole('heading', { name: 'OpenAI GPT', exact: true }).locator('..');
    await provider.getByLabel('New OpenAI GPT key', { exact: true }).fill('browser-fixture-key-not-a-real-key');
    await provider.getByRole('button', { name: 'Encrypt and save key', exact: true }).click();
    await expect(page.getByRole('status').first()).toContainText('Credential encrypted and saved');
    await expect(provider.getByLabel('New OpenAI GPT key', { exact: true })).toHaveValue('');
    await expect(provider.getByText('Credential configured; provider verification is separate.', { exact: true })).toBeVisible();
    await expect(page.locator('body')).not.toContainText('browser-fixture-key-not-a-real-key');
    await provider.getByRole('button', { name: 'Remove stored key', exact: true }).click();
    await expect(page.getByRole('status').first()).toContainText('Stored credential removed');
});

test('authenticator enrollment and single-use recovery protect the full browser sign-in', async ({ page, context }) => {
    const { createHmac } = await import('node:crypto');
    const otp = (secret) => {
        const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        const bits = [...secret].map(c => alphabet.indexOf(c).toString(2).padStart(5, '0')).join('');
        const bytes = Buffer.from((bits.match(/.{8}/g) || []).map(b => parseInt(b, 2)));
        const counter = Buffer.alloc(8); counter.writeBigUInt64BE(BigInt(Math.floor(Date.now() / 30000)));
        const hash = createHmac('sha1', bytes).update(counter).digest();
        return ((hash.readUInt32BE(hash[hash.length - 1] & 15) & 0x7fffffff) % 1000000).toString().padStart(6, '0');
    };
    const violations = [];
    page.on('console', message => { if (message.type() === 'error' && /Content Security Policy|Refused to execute/i.test(message.text())) violations.push(message.text()); });
    await signIn(page, 'other@browser.example.test');
    await page.getByRole('link', { name: 'Account security', exact: true }).click();
    await page.getByRole('button', { name: 'Set up authenticator', exact: true }).click();
    await expect(page.getByRole('img', { name: 'Authenticator setup QR code' })).toBeVisible();
    const secret = (await page.locator('[data-setup-key]').textContent()).trim();
    await page.getByLabel('New authenticator code', { exact: true }).fill(otp(secret));
    await page.getByRole('button', { name: 'Confirm authenticator', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Save these recovery codes now', exact: true })).toBeVisible();
    const recoveryCodes = await page.locator('[data-recovery-code]').allTextContents();
    expect(recoveryCodes).toHaveLength(10);
    await page.reload();
    await expect(page.locator('[data-recovery-code]')).toHaveCount(0);
    await expect(page.getByText('Enabled', { exact: true })).toBeVisible();
    await page.setViewportSize({ width: 390, height: 844 });
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    await page.screenshot({ path: 'artifacts/security/account-enabled.png', fullPage: true });
    await page.getByRole('button', { name: 'Sign out', exact: true }).click();
    const passwordStep = async () => {
        await page.goto('/staff/login');
        await page.getByLabel('Email address', { exact: true }).fill('other@browser.example.test');
        await page.getByLabel('Password', { exact: true }).fill('BrowserOnlyPassword123!');
        await page.getByRole('button', { name: 'Sign in', exact: true }).click();
        await expect(page).toHaveURL(/\/staff\/two-factor-challenge$/);
    };
    await passwordStep();
    expect((await context.request.get('/staff', { maxRedirects: 0 })).status()).toBe(302);
    await page.getByLabel('Authenticator or recovery code', { exact: true }).fill(recoveryCodes[0].trim());
    await page.getByRole('button', { name: 'Verify and sign in', exact: true }).click();
    await expect(page).toHaveURL(/\/staff$/);
    await page.getByRole('button', { name: 'Sign out', exact: true }).click();
    await passwordStep();
    await page.getByLabel('Authenticator or recovery code', { exact: true }).fill(recoveryCodes[0].trim());
    await page.getByRole('button', { name: 'Verify and sign in', exact: true }).click();
    await expect(page.getByRole('alert')).toContainText('already used');
    await page.getByLabel('Authenticator or recovery code', { exact: true }).fill(recoveryCodes[1].trim());
    await page.getByRole('button', { name: 'Verify and sign in', exact: true }).click();
    await expect(page).toHaveURL(/\/staff$/);
    expect(violations).toEqual([]);
    await page.getByRole('button', { name: 'Sign out', exact: true }).click();
    await page.setViewportSize({ width: 1440, height: 1000 });
    await signIn(page, 'credentials@browser.example.test');
    await page.getByRole('link', { name: 'Launch readiness', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Launch readiness', exact: true })).toBeVisible();
    await page.screenshot({ path: 'artifacts/security/launch-readiness.png', fullPage: true });
});
