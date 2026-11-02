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
const violations = changed.filter(path => protectedPaths.some(rule => rule.test(path)));
if (violations.length) {
    console.error('Protected public design/content/dependency files changed:\n' + violations.join('\n'));
    process.exit(1);
}
console.log(`Public design protection passed: zero protected files changed (${changed.length} files reviewed).`);
