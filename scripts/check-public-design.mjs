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
// This release adds two optional evidence modules to the existing article view.
// Permit only those exact include lines; every pre-existing byte remains protected.
const articlePath = 'resources/views/pages/article.blade.php';
const stripEvidenceIncludes = text => text.split('\n').filter(line => !/^\s*@include\('editorial\.(?:evidence|sources)', \['evidence' => \$article\['editorialEvidence'\] \?\? \[\](?:, 'reviewer' => \$article\['reviewer'\] \?\? null)?\]\)\s*$/.test(line)).join('\n');
const evidenceOnly = path => path === articlePath && stripEvidenceIncludes(execFileSync('git', ['show', `${base}:${path}`], { encoding: 'utf8' })) === stripEvidenceIncludes(execFileSync('git', ['show', `HEAD:${path}`], { encoding: 'utf8' }));
// Owner-approved dotted Africa artwork is allowed only at its reviewed exact blob hashes.
// This is an immutable, content-specific exception, not a general bypass of public-design protection.
const approvedBrandBlobs = new Map(Object.entries({
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
const approvedBrandFile = path => {
    const expected = approvedBrandBlobs.get(path);
    if (!expected) return false;
    try {
        return execFileSync('git', ['rev-parse', `HEAD:${path}`], { encoding: 'utf8' }).trim() === expected;
    } catch {
        return false;
    }
};
const violations = changed.filter(path => protectedPaths.some(rule => rule.test(path)) && !evidenceOnly(path) && !approvedBrandFile(path));
if (violations.length) {
    console.error('Protected public design/content/dependency files changed:\n' + violations.join('\n'));
    process.exit(1);
}
console.log(`Public design protection passed: zero protected files changed (${changed.length} files reviewed).`);
