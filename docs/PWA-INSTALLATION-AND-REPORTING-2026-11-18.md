# African Ranked PWA installation and metrics

## What this change provides

- Public pages link a same-origin web app manifest, maintain the approved Africa icon, and register a minimal offline-only service worker.
- The manifest includes PNG icons at 192 and 512 pixels, generated from the existing approved Apple touch icon. Maskable variants add a white safe area. The icon endpoints only accept those four size variants.
- Android browsers that support `beforeinstallprompt` offer their native install dialogue from an opt-in UI. Other Android browsers show their browser-menu instructions. iPhone/iPad users see Share → Add to Home Screen guidance.
- On iOS the user must install the app manually. There is no reliable installation-complete browser event. The first standalone launch is reported separately as an *estimate*.
- `/staff/pwa-installs` is limited to staff users who satisfy `can:manage-staff`. It shows 30-day platform/event aggregates.

## Event definitions

- `installed`: Chromium `appinstalled` notification (Android primarily, may be desktop); browser signal, not a unique-human guarantee.
- `standalone_first_open`: browser began in standalone display mode and a one-time local flag was not yet set. Counts installed-app *first opens* as an estimate and does not prove that a particular user installed the icon on that day.
- `prompt_accepted`: user accepted a supported native install prompt; **not** equivalent to completed installation.
- `help_opened`: mobile install instructions or controls opened; not an installation.

Only day, platform, event and counter are kept in PostgreSQL; the telemetry request contains **no** visitor ID, email, account, IP, browser user agent or device fingerprint. A device-local storage flag suppresses repeat first-open requests; this key is never transmitted. The database stores daily aggregates without a built-in expiry. Counts can under- or over-estimate installations (multiple browser profiles, storage clearing, reinstalls, offline use, disabled scripts and platform-specific differences). Do not add installed and standalone-first-open figures together as unique users.

Requests use the same-origin CSRF token and strict platform/event validation, with a 12/minute throttle. No advertising or other remote analytics service is introduced. Have the owner/legal editor review how aggregate PWA measurement is described in the published Privacy Policy before enabling public promotion.

## Caching policy

`/sw.js` caches only `/offline.html`. It **never caches HTML**, private staff pages, login/API paths or article content. It uses the network for every public navigation and falls back to the offline page only on a network failure. Consequently, PWA installations should not cause the public homepage to stay stale after an admin change. No background sync, push notifications or offline copies of copyrighted articles are introduced.

## Homepage diagnosis

The homepage's hero heading/highlight/description and featured ID are stored in `site_settings` and rendered server-side by `PageController::home`. Changes to those values do not require a new GitHub deployment. The hero artwork, colour treatment, category cards and section layout are hard-coded in `resources/views/pages/home.blade.php`, so dashboard text settings cannot change those design elements. A featured article that has not been published cannot appear on the public homepage. A feature test proves that saving the homepage intro is immediately reflected by `GET /`. If a specific production change still does not appear, compare the saved `site_settings` version/values to live HTML and browser cache; do not silently overwrite the owner's settings.

## Release checklist

1. Run PHP/sqlite and PostgreSQL feature tests, browser tests, JS build, and PWA manifest/icon tests in CI.
2. Verify GET `/manifest.webmanifest`, `/sw.js`, `/offline.html`, and `/pwa/icon/512.png` return 200 with correct types after deployment.
3. Verify Chrome Android offers the install action; verify iPhone Safari Share → Add to Home Screen. Confirm app opens standalone and works after offline navigation.
4. From the admin, check `/staff/pwa-installs` for events without disclosing any user data.
5. Confirm homepage changes and newly published articles update on app reload (no stale cached HTML).
6. Preserve published article revisions, staff access, search indexing, active integrations and existing custom domains.
7. Verify the release created no /staff or /api responses in Cache Storage. Verify private pages do not show the install widget.
