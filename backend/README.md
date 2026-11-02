# Africa Ranked CMS backend

Laravel 12 backend for the existing React/Vite site. PHP 8.2+ is required. The frontend, its styles, branding, newsletter and existing content remain in the repository root. Laravel Sanctum 4 is the only package added to the supplied Laravel scaffold.

## Local setup

1. Run `composer install` from `backend/`.
2. Supply environment values using `backend/.env.example` as the key reference. No real `.env` or credentials are committed. Use a new application encryption key from your secret manager. `.env.testing` contains a public, test-only key.
3. Build the frontend from the repository root with `npm ci` and `npm run build`. Preserve the site's existing `VITE_SUPABASE_URL` and `VITE_SUPABASE_ANON_KEY` build settings; the existing newsletter module requires them even before a form is submitted. Do not use a Supabase service-role key in a browser build.
4. Create an explicitly local SQLite database and migrate only that database:

   ```sh
   # Run from backend/. Do not point these commands at a remote database.
   touch database/database.sqlite
   APP_ENV=local DB_CONNECTION=sqlite DB_URL='' DB_DATABASE="$PWD/database/database.sqlite" php artisan migrate
   APP_ENV=local DB_CONNECTION=sqlite DB_URL='' DB_DATABASE="$PWD/database/database.sqlite" php artisan cms:import-legacy
   ```

5. Run `php artisan serve` with the same local database environment settings and your application key. Laravel serves the built frontend from `../dist`; there is no replacement Blade design. The default `FRONTEND_DIST` is relative to `backend/`.

The import reads `database/seeders/legacy-content.json`, exported from the original TypeScript data at commit `59ffdad`. It imports 13 articles and their actual category/author data. It never overwrites an existing slug/edition. It preserves original article text, structured body blocks, FAQs, image URLs, author titles and dates. Imported rows are draft/non-indexable, with a separate legacy visibility flag. Unreviewed edits stay private; the original snapshot remains public until publication. No reviewer biography, policy text, source or score is invented.

## Editorial operations

Use trusted Laravel tooling to assign real authors/reviewers, their biographies, sources, entities, scores and content. No admin UI dependency was added. `php artisan db:seed` seeds categories only; it does not create a default login.

```sh
php artisan cms:article ARTICLE_ID check
php artisan cms:article ARTICLE_ID review
php artisan cms:article ARTICLE_ID approve
php artisan cms:article ARTICLE_ID publish
php artisan cms:article ARTICLE_ID index
```

Approval asks the operator to confirm that they opened every source and resolved verification flags. Publishing runs every package gate. Indexing asks for the separate audit confirmation. There is no publish/index HTTP endpoint. As with any Laravel application, trusted code using raw query-builder SQL can bypass model events; the agent never receives that access.

For an existing, authorized Laravel user, create a Sanctum personal access token with exactly `['drafts:create']` using trusted server-side tooling. Store its plaintext value securely when issued; never commit it. POST JSON to `/api/agent/drafts` using a bearer token. Session authentication, wildcard abilities and tokens with additional abilities are rejected. The endpoint cannot set publication, indexing, author/reviewer or legacy-visibility controls.

## Owner-supplied editorial pages

The following files are the named content sources; none are populated with placeholder text:

- `storage/app/editorial/about.md`
- `storage/app/editorial/methodology.md`
- `storage/app/editorial/editorial-policy.md`
- `storage/app/editorial/monetization-disclosure.md`

Use the page title as a Markdown H1. Routes return 404 until the corresponding file exists. Once provided, the page renders inside the current React header/footer using unstyled semantic HTML. `/llms.txt` advertises only editorial files that exist. Author/entity pages use existing database text, and `/corrections` uses actual correction rows.

## Hosting and SEO activation

Nothing has been deployed. The existing root `public/robots.txt`, `public/sitemap.xml` and segmented sitemap files are unchanged. Their replacement requires an explicit hosting-routing decision. To activate Laravel's dynamic endpoints later, direct requests to `backend/public/index.php` and ensure the root static SEO files do not take precedence. Do not delete those files as part of this branch.

Production environment keys:

| Key | Setting/purpose |
|---|---|
| `APP_ENV` | `production` only on the live canonical host; `local`/`staging` elsewhere |
| `APP_URL` | `https://africanranked.com`, preserving the existing site's domain |
| `APP_KEY` | Secret application encryption key supplied by the operator |
| `AI_TRAINING_ALLOWED` | `true` preserves the package default; owner may choose `false` |
| `FRONTEND_DIST` | `../dist` by default |
| `DB_*`, cache/session/queue settings | Existing Laravel runtime configuration; keep the Supabase newsletter separate |

All generated CMS URLs use `APP_URL`, never the request host. Every non-production or non-canonical host receives `X-Robots-Tag: noindex, nofollow, noarchive`, and dynamic robots blocks all crawling there. Production robots follows the supplied bot policy. Only published, due, indexable canonical articles appear in sitemaps. The original `/sitemaps/{edition}.xml` URLs remain supported as first-page aliases. No `/feed.xml` existed; none was replaced or invented.

Run the Laravel scheduler once per minute through your hosting scheduler (`php artisan schedule:run`). `articles:flag-stale` runs daily, clears indexing, and marks overdue content `needs_update`. Previously published stale pages remain publicly readable.

The new governance modules use semantic HTML with no new styling classes. Their visual treatment, as well as new editorial/author/entity pages, is a **needs design decision** item. The existing article markup, typography, header, footer and newsletter remain intact.

## Validation and rollback

Run `composer test` from `backend/`, then root `npm run typecheck`, `npm run lint` and `npm run build`. See [the integration report](../docs/cms/integration-report.md) for evidence and the exact file inventory.

To undo the code, revert the three branch commits in reverse order or return hosting to the previous static build. Keep a backup of the new CMS database if it contains editorial work. On an explicitly local database, `php artisan migrate:rollback --step=1` reverses Sanctum, and the next `--step=1` reverses the CMS tables; run from `backend/` only after confirming migration order with `migrate:status`. Each migration has a `down()` method. Do not run rollback on a non-local database as part of this task. The Supabase newsletter and original TypeScript articles need no data rollback.
