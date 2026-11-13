import { execFileSync } from 'node:child_process';

// Detect unintended changes; deliberate public-design work needs a separately reviewed scope.
const protectedPaths = [
    /^resources\/(?:css|sass)\//,
    /^resources\/views\/(?:layouts|components|pages|partials)\//,
    /^public\//,
    /^(?:tailwind|postcss|vite)\.config\./,
    /^(?:package\.json|package-lock\.json|composer\.json|composer\.lock)$/,
    /^content\/(?:articles|categories|regions)\.json$/,
];
const supplied = process.env.DESIGN_BASE_SHA;
if (supplied && ! /^[a-f0-9]{40,64}$/i.test(supplied)) throw new Error('DESIGN_BASE_SHA must be a full commit SHA.');
const base = supplied && ! /^0+$/.test(supplied)
    ? supplied
    : execFileSync('git', ['merge-base', 'origin/main', 'HEAD'], { encoding: 'utf8' }).trim();
execFileSync('git', ['rev-parse', '--verify', `${base}^{commit}`], { stdio: 'ignore' });
const changed = execFileSync('git', ['diff', '--name-only', '-z', base, 'HEAD'], { encoding: 'utf8' }).split('\0').filter(Boolean);
// Owner-approved reader-flow change: only move or split exact evidence/source
// includes. After those statements are stripped, every other template byte
// (including whitespace and design markup) must match the protected base.
const articlePath = 'resources/views/pages/article.blade.php';
const evidenceInclude = /^\s*@include\('editorial\.evidence', \['evidence' => \$article\['editorialEvidence'\] \?\? \[\](?:, 'section' => '(?:answer|facts)')?\]\)\s*$/;
const sourceInclude = /^\s*@include\('editorial\.sources', \['evidence' => \$article\['editorialEvidence'\] \?\? \[\], 'reviewer' => \$article\['reviewer'\] \?\? null\]\)\s*$/;
const stripEvidenceIncludes = text => text.split('\n').filter(line => !evidenceInclude.test(line) && !sourceInclude.test(line)).join('\n');
const evidenceOnly = path => path === articlePath && stripEvidenceIncludes(execFileSync('git', ['show', `${base}:${path}`], { encoding: 'utf8' })) === stripEvidenceIncludes(execFileSync('git', ['show', `HEAD:${path}`], { encoding: 'utf8' }));
// Owner-approved branding and audited dependency lock are allowed only at their reviewed exact blob hashes.
// This is an immutable, content-specific exception, not a general bypass of public-design protection.
const approvedBrandBlobs = new Map(Object.entries({
    "composer.lock": "d77db28648d6a1bc3296ed17113c4a65cb4c08ec",
    "public/africa-mark.svg": "840b1f16180fcf620343e9534176c52def82f13b",
    "public/apple-touch-icon.png": "e8e36ea96b52b29623bbe6d206b6fea9ef3ecf1d",
    "public/brand-icon.png": "b22dbc2affe959f3edc2e535d577a4c668970f63",
    "public/favicon-32.png": "095b0a7619357252e5a65a5d533709893ee978a7",
    "public/favicon.ico": "18de2dbcc178e581f6087cb2a6f799a0a3497edf",
    "public/favicon.svg": "840b1f16180fcf620343e9534176c52def82f13b",
    "resources/views/layouts/app.blade.php": "bb2eff0e0bdf70b8dac0af7977d3456d58350a2e",
    "resources/views/partials/footer.blade.php": "a6a6f6afc37b39b82cfeb45c9af6e8cadb9cfee8",
    "resources/views/partials/header.blade.php": "ccab3d3a5ff086fb292251b11f4e76df54cc59a1",
    "resources/views/staff/layout.blade.php": "02c2d6289c832e3c290a838a7f7e1b1f7c152afd",
    "resources/views/vendor/mail/html/header.blade.php": "473aee6e330a835b6b07a170d5ff3ba720a53f92"
}));
const approvedRecoveryFile = path => {
    const expected = approvedBrandBlobs.get(path);
    if (!expected) return false;
    try {
        return execFileSync('git', ['rev-parse', `HEAD:${path}`], { encoding: 'utf8' }).trim() === expected;
    } catch {
        return false;
    }
};
// The SEO launch gate may change only the exact robots-meta expression in
// the public layout. All other layout bytes remain protected and compared.
const layoutPath = 'resources/views/layouts/app.blade.php';
const oldRobotsMeta = "<meta name=\"robots\" content=\"{{ config('seo.preview') || config('review.enabled') ? 'noindex, nofollow, noarchive' : $seo['robots'] }}\">";
const gatedRobotsMeta = "<meta name=\"robots\" content=\"{{ config('seo.preview') || config('review.enabled') ? 'noindex, nofollow, noarchive' : (config('seo.indexing_enabled') ? $seo['robots'] : 'noindex, follow, noarchive') }}\">";
const indexingGateOnly = path => {
    if (path !== layoutPath) return false;
    const before = execFileSync('git', ['show', `${base}:${path}`], { encoding: 'utf8' });
    const after = execFileSync('git', ['show', `HEAD:${path}`], { encoding: 'utf8' });
    return before.includes(oldRobotsMeta) && after.includes(gatedRobotsMeta)
        && before === after.replace(gatedRobotsMeta, oldRobotsMeta);
};
// Explicitly approved generated homepage hero release (9 November 2026).
// Match exact Git blobs only; other public image/template/CSS edits remain guarded.
const approvedHeroBlobs = new Map(Object.entries({
    "public/images/african-ranked-savannah-hero-desktop.webp": "b76217897d89e21d324d6da205d8686e02043632",
    "public/images/african-ranked-savannah-hero-mobile.webp": "94cb8d0b1ba7ce0f016a591438095d73eefe4403",
    "resources/views/pages/home.blade.php": "e2d996f56c8ea747585f383dbf3790f0d6ff70c1",
    "resources/css/app.css": "eb1e596948d1fd991ca96f73034dd2f297a03be5"
}));
const approvedHeroChange = path => {
    const expected = approvedHeroBlobs.get(path);
    if (!expected) return false;
    try {
        return execFileSync('git', ['rev-parse', `HEAD:${path}`], { encoding: 'utf8' }).trim() === expected;
    } catch { return false; }
};
// Owner-authorized OAuth bridge dependencies; exact reviewed blobs only.
const editorialOAuthDependencies = new Map(Object.entries({"composer.json": "c750b611de29bc2ba09fee267b0e12f2632ff1a1", "composer.lock": "749a6e32b1f44f33b812021ddec33db0c80cb4a2"}));
const approvedOAuthDependency = path => editorialOAuthDependencies.has(path) && execFileSync('git', ['rev-parse', `HEAD:${path}`], { encoding: 'utf8' }).trim() === editorialOAuthDependencies.get(path);
// Owner-authorized 13 November 2026 technical SEO amendment: add the exact
// category-list section heading needed for an H1 -> H2 -> H3 hierarchy.
// Retain the immutable blob check; no other category template changes are allowed.
const approvedRegionalCategoryHeading = path => {
    if (path !== 'resources/views/pages/category.blade.php') return false;
    return execFileSync('git', ['rev-parse', 'HEAD:' + path], { encoding: 'utf8' }).trim()
        === '9953b06b7221335249c09369be5b86d7d163d580';
};

// Owner-requested 13 November 2026 GA4 consent integration, reviewed at exact blob
// hashes. Does not authorize any unrelated public layout/design changes.
const approvedAnalyticsBlobs = new Map(Object.entries({
    "resources/views/layouts/app.blade.php": "d4a02add353836688d3bfb79976527dc4c77ce53",
    "resources/views/partials/footer.blade.php": "d02d2e60c211f0e8ad39e3f1a0b5ce64933b6c5b",
    "resources/views/partials/analytics-consent.blade.php": "8b25130b92b5bf0387d70fc6077cb32e5ab826b0"
}));
const approvedAnalyticsChange = path => {
    const expected = approvedAnalyticsBlobs.get(path);
    return Boolean(expected) && execFileSync('git', ['rev-parse', 'HEAD:' + path], { encoding: 'utf8' }).trim() === expected;
};

// Owner-approved 13 Nov 2026 newsletter consent and confirmation page additions.
// Only the exact reviewed frontend blobs are allowed; other design remains protected.
const approvedNewsletterBlobs = new Map(Object.entries({
    "resources/views/partials/newsletter.blade.php": "a2239f508733bb0321cc956ea02b1164e6857d65",
    "resources/views/pages/newsletter-lifecycle.blade.php": "f112d978706c8a3ee8efad0e01815796745b1251"
}));
const approvedNewsletterChange = path => {
    const expected = approvedNewsletterBlobs.get(path);
    return Boolean(expected) && execFileSync('git', ['rev-parse', 'HEAD:' + path], { encoding: 'utf8' }).trim() === expected;
};

// Owner request: add About Us to desktop/mobile navigation and design its page (13 November 2026).
const approvedAboutBlobs = new Map(Object.entries({
    "resources/views/pages/about.blade.php": "6b1a32c56f7a30c3881cfc8715d47c8d21b80da4",
    "resources/views/partials/header.blade.php": "0f887e15109fc062f541b8b0d9b593535c09154c"
}));
const approvedAboutChange = path => approvedAboutBlobs.has(path) && execFileSync('git', ['rev-parse', `HEAD:${path}`], { encoding: 'utf8' }).trim() === approvedAboutBlobs.get(path);

const violations = changed.filter(path => protectedPaths.some(rule => rule.test(path)) && !evidenceOnly(path) && !indexingGateOnly(path) && !approvedRecoveryFile(path) && !approvedHeroChange(path) && !approvedOAuthDependency(path) && !approvedRegionalCategoryHeading(path) && !approvedAnalyticsChange(path) && !approvedNewsletterChange(path) && !approvedAboutChange(path));
if (violations.length) {
    console.error('Protected public design/content/dependency files changed:\n' + violations.join('\n'));
    process.exit(1);
}
console.log(`Public design protection passed: zero protected files changed (${changed.length} files reviewed).`);
