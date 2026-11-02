# CMS integration report

Branch: `feature/cms-governance`. Base: `59ffdad` (GitHub main at inspection). Commit author and committer: `Codex CMS Integration <codex@openai.com>`. No deployment, production migration, remote data edit, or change to the Supabase newsletter was performed.

## Findings and approved mapping

The actual site is React 18, TypeScript, Vite 7 and Tailwind, with Supabase for newsletter subscriptions. It is not a Laravel application. The owner approved using the separate Laravel scaffold as its backend. The scaffold is Laravel 12; the installed lock resolves Laravel 12.70.2 and Sanctum 4.3.3. Validation used PHP 8.4.24; runtime minimum is PHP 8.2. No Filament, Nova, CMS or SEO package was installed. The package originally targets Laravel 10/11 and has been adapted to Laravel 12 bootstrap conventions.

| Package file/area | Existing equivalent | Final mapping |
|---|---|---|
| Core migration | No CMS tables in Laravel scaffold; only users/password resets/sessions, cache/locks, jobs/batches/failed jobs | New CMS tables only; no alterations to existing or Supabase tables |
| Article model | TypeScript `Article`, static article arrays and structured `ArticleBlock[]` bodies | New Laravel model plus original JSON frontend snapshot and Markdown editorial body |
| Category model | Six categories in `src/data/categories.ts` | Preserve slugs/names/descriptions; seed matching records |
| Author model | Embedded author name/title in each article; no author table | Import existing names/titles; do not invent biographies or reviewers |
| Editions | Eight region codes in `src/data/regions.ts` | Preserve codes and prefixed URLs; global legacy snapshots stay available on existing edition article URLs |
| Newsletter | Supabase `newsletter_subscribers(id uuid, email text unique, region text, created_at timestamptz)` | Unchanged, including its migration and client |
| Package Blade view | `ArticlePage.tsx`, with existing FAQ, body renderer and inline typography | Structural reference only; no Blade replacement. Add four module insertion points and keep existing FAQ rendering |
| Layout/head stack | React `App.tsx` and `SeoHead.tsx`; no Blade head section/stack | Retain header/footer and existing metadata handling; add CMS canonical/noindex/linked JSON-LD |
| SchemaBuilder | Existing Organization/WebSite/Article/FAQ/Breadcrumb/ItemList helpers | Preserve existing schema for legacy snapshots; one linked CMS graph for reviewed articles |
| Agent draft API | None | Sanctum token-only, exact `drafts:create` ability, transactional drafts only |
| PublishingGate/SimilarityChecker | None | Adopt supplied thresholds and shingle comparison; fix placeholder scanner's JSON-array false positive |
| Stale command | No scheduler in React; standard Laravel console routes in scaffold | Daily schedule using Laravel 12 `routes/console.php` |
| robots/sitemaps | Root static `public/robots.txt`, `public/sitemap.xml`, `public/sitemaps/{edition}.xml`; generator script | Leave originals untouched; backend dynamic endpoints plus compatibility aliases; hosting switch pending |
| llms.txt | None | Exact package positioning; only implemented links; missing editorial pages omitted |
| Article/category/edition/strategy routes | Root and eight edition paths handled by `App.tsx` | Equivalent Laravel routes serve the same built React app |
| feed.xml | No route/file found | Nothing replaced |
| Editorial pages | None | Owner Markdown files, no placeholder content, 404 until supplied |
| Author/entity/corrections pages | None | Database-backed semantic pages, using the same frontend shell |

No design/brand Markdown files were present in the site repository. Existing design rules are embodied in `src/index.css`, Tailwind configuration, category colors, region definitions, and the existing React components. They were read and left unchanged. The package README and editorial prompts are preserved under `backend/editorial-reference/` as reference material; no attached prompt was executed as an autonomous content-writing instruction.

All 13 original articles import as draft/non-indexable. A separate `legacy_public` flag preserves their original public rendering, while unreviewed database revisions remain private. The original published and modified dates survive import. The source has no separate created timestamp, so `created_at` is initialized to its existing published timestamp. A rerun skips existing slug/edition records. No existing frontend copy was rewritten.

## Evidence

```text
$ cd backend && php artisan test --compact
Tests: 28 passed (305 assertions)

$ npm run typecheck
passed
$ npm run lint
passed
$ npm run build
built successfully (existing >500 kB chunk-size advisory remains)

$ composer validate --no-check-publish
./composer.json is valid

$ php artisan schedule:list  # explicitly local SQLite/file-cache settings
0 0 * * *  php artisan articles:flag-stale
```

Tests use in-memory SQLite, a known test-only application key, and synthetic facts/sources confined to test fixtures. A separate preview used only `/workspace/work/cms-preview.sqlite`. Migration rollback was verified on empty tables inside the test database, preserving scaffold tables. No migrations or rollbacks ran against a non-local database.

| Requirement | Evidence/result |
|---|---|
| Non-production robots | Feature requests on Tunnelmole, ngrok and staging hosts return exactly `User-agent: *` / `Disallow: /`, with noindex header |
| Production robots | Simulated canonical production request allows `/`, advertises canonical sitemap and applies the training-bot setting |
| llms positioning/links | Exact config positioning asserted; every advertised URL resolves with 200 through the local application's HTTP kernel; unwritten policy routes return 404 and are omitted |
| Sitemap exclusion | Draft, non-indexable, future-dated and canonical-copy records excluded; XML parsed with PHP SimpleXML; pagination boundaries tested |
| 1,300-word gate | A 1,299-word body fails even when other requirements pass |
| Top 10 gate | Nine complete scored items cannot publish a Top 10 article |
| Separate indexing | Publication defaults to non-indexable; direct indexing assignment fails; audit method and human CLI confirmation tested |
| Agent limitations | Missing token 401; session/wrong/wildcard/extra-ability token 403; publishing/indexing fields 422; valid draft 201; duplicate 409; transaction rollback tested |
| JSON-LD | PHP JSON_THROW_ON_ERROR encode/decode plus graph/type/item-count assertions; Playwright JSON.parse on rendered scripts. No external Google rich-result eligibility claim |
| Existing public URLs | All 13 article URLs tested across all eight editions; strategy/category and old sitemap URLs also return 200 |
| Stale governance | Stale content becomes needs_update/non-indexable and leaves sitemap while remaining publicly readable |
| Review workflow | Review → human confirmation → approve → gates → publish → separate confirmed index action tested |
| Design protection | Empty protected-file diff; identical CSS SHA-256; ranking and guide render comparisons below |

## Render comparison

Chromium/Playwright compared the original build at `59ffdad` with the integrated backend, 1280×900 viewport. Third-party network requests were disabled on both sides, so images use the existing image fallback and remote fonts use the same fallback. Builds used explicitly test-only public Supabase configuration, because the existing newsletter module throws at startup without its two build variables. These test values were not written to `.env` or committed as production configuration.

After scrolling to settle existing animations, the root markup is identical for both pages after removing only transient animation opacity/transform styles. The before/after PNG files are also byte-identical for each page. Both return HTTP 200, emit four parseable legacy JSON-LD scripts, and have zero JavaScript page errors. The JSON result and before/after screenshots are in [evidence](evidence/).

- Ranking: `top-10-safari-destinations-africa-2026`
- Guide: `safari-packing-etiquette-first-timer-guide`

CSS SHA-256 before and after:

```text
e5681be8a58354881f76307b9df9d477d6eaddd0a125da2144c032c68181bc7a
```

Article-page changes only import `ArticleGovernance` and insert `before`, `body`, `after` and `end` module calls. No existing markup, styling class, FAQ label or text was removed/replaced. Legacy snapshot modules produce no additional DOM.

## Protected-file diff

```sh
git diff --stat origin/main -- src/index.css tailwind.config.js postcss.config.js public package.json package-lock.json vite.config.ts src/components/Header.tsx src/components/Footer.tsx src/components/Newsletter.tsx src/components/RegionSelector.tsx src/components/CategoryGrid.tsx src/components/ArticleCard.tsx src/data/categories.ts src/data/regions.ts src/data/articles-wildlife.ts src/data/articles-wildlife-guides.ts
```

Output:

```text
(empty; zero changes)

```

The base is the exact GitHub main commit fetched during this task. Existing React article/head files have only the permitted content/head additions. `App.tsx`, the article-data selector and type declaration provide the backend bridge approved by the owner after the React/Laravel mismatch was identified. The original content literals in all three article data files remain unchanged.

## Open items and activation limits

1. Supply actual About, Methodology, Editorial Policy and Monetization Disclosure text. Routes and named Markdown sources exist; no placeholder page is live.
2. Assign actual reviewers and author biographies, add verified sources/facts/scores, and review each legacy article before publishing/indexing it. The 19-post launch wave was preserved as a plan; no posts were fabricated or automatically published.
3. **Needs design decision:** the new facts/scores/methodology/source/corrections/disclosure modules and new editorial/author/entity pages currently use semantic HTML without new styling classes. The existing FAQ is reused.
4. There is no new admin UI. Trusted server-side tooling plus `cms:article` provides the editorial workflow without introducing another dependency.
5. Confirm the hosting switch for static robots/sitemaps before activation. Until requests are routed through Laravel, a purely static deployment retains the old SEO behavior. Original files were not deleted or rewritten, and this branch was not deployed.
6. Preserve `APP_URL=https://africanranked.com` on the canonical deployment, provide a real secret `APP_KEY`, and keep real Supabase browser build settings in the environment. Owner decides `AI_TRAINING_ALLOWED`; supplied default remains true.
7. Keep the supplied recent-200 same-category similarity scan for now; the package's scale-related limitation remains. The initial catalog bootstrap is intended for this small launch corpus; large-scale pagination/data delivery is future work.
8. SQLite is verified; other database engines have not been integration-tested.
9. The existing frontend remains a client-rendered app. Laravel supplies SEO head/data bootstrap and HTTP crawler controls; it does not introduce a redesigned or fully server-rendered article body.

## Rollback

Revert this branch's commits in reverse order, or switch hosting back to the previous static build. The original frontend content and public SEO files are still present. Keep a database backup before discarding any new editorial work. No Supabase migration or newsletter change needs reverting.

On an explicitly local database only, inspect `php artisan migrate:status`; roll back `2026_11_02_000002_create_personal_access_tokens_table` with one `migrate:rollback --step=1`, then the CMS migration with the next `--step=1`. The former drops only the new token table; the latter drops only the new CMS tables in foreign-key-safe order. The three original Laravel scaffold migrations can each be reversed with their own next `--step=1` only if you are intentionally discarding the entire new backend database. Do not roll back shared/production databases as part of this task.

## File inventory

All `backend/` files are additions to the existing site. The scaffold's Laravel dependencies remain in the new backend manifest, with Sanctum added; the original frontend manifests/locks were not modified.

| Change | File | Reason |
|---|---|---|
| A | `backend/.editorconfig` | Adopt the supplied Laravel scaffold runtime/test configuration. |
| A | `backend/.env.example` | Document runtime keys without editing a real environment file. |
| A | `backend/.env.testing` | Provide an explicitly public test-only encryption key. |
| A | `backend/.gitattributes` | Adopt the supplied Laravel scaffold runtime/test configuration. |
| A | `backend/.gitignore` | Adopt the supplied Laravel scaffold runtime/test configuration. |
| A | `backend/README.md` | Document mapping, setup, limits, rollback and validation evidence. |
| A | `backend/app/Console/Commands/FlagStaleArticles.php` | Legacy import, human review actions or stale-content governance. |
| A | `backend/app/Console/Commands/ImportLegacyContent.php` | Legacy import, human review actions or stale-content governance. |
| A | `backend/app/Console/Commands/ReviewArticle.php` | Legacy import, human review actions or stale-content governance. |
| A | `backend/app/Enums/ArticleStatus.php` | Preserve supplied article types and workflow states. |
| A | `backend/app/Enums/ArticleType.php` | Preserve supplied article types and workflow states. |
| A | `backend/app/Http/Controllers/AgentDraftController.php` | Serve draft API, crawler endpoints or original frontend shell/data. |
| A | `backend/app/Http/Controllers/Controller.php` | Serve draft API, crawler endpoints or original frontend shell/data. |
| A | `backend/app/Http/Controllers/FrontendController.php` | Serve draft API, crawler endpoints or original frontend shell/data. |
| A | `backend/app/Http/Controllers/LlmsTxtController.php` | Serve draft API, crawler endpoints or original frontend shell/data. |
| A | `backend/app/Http/Controllers/PublicContentController.php` | Serve draft API, crawler endpoints or original frontend shell/data. |
| A | `backend/app/Http/Controllers/RobotsController.php` | Serve draft API, crawler endpoints or original frontend shell/data. |
| A | `backend/app/Http/Controllers/SitemapController.php` | Serve draft API, crawler endpoints or original frontend shell/data. |
| A | `backend/app/Http/Middleware/AgentTokenOnly.php` | Host-based noindex or exact bearer-token permissions. |
| A | `backend/app/Http/Middleware/NoIndexOutsideProduction.php` | Host-based noindex or exact bearer-token permissions. |
| A | `backend/app/Http/Requests/StoreAgentDraftRequest.php` | Validate agent input and prohibit publication controls. |
| A | `backend/app/Models/Article.php` | CMS relationships, casts and guarded publishing/indexing operations. |
| A | `backend/app/Models/Author.php` | CMS relationships, casts and guarded publishing/indexing operations. |
| A | `backend/app/Models/Category.php` | CMS relationships, casts and guarded publishing/indexing operations. |
| A | `backend/app/Models/Correction.php` | CMS relationships, casts and guarded publishing/indexing operations. |
| A | `backend/app/Models/Entity.php` | CMS relationships, casts and guarded publishing/indexing operations. |
| A | `backend/app/Models/RankingItem.php` | CMS relationships, casts and guarded publishing/indexing operations. |
| A | `backend/app/Models/Source.php` | CMS relationships, casts and guarded publishing/indexing operations. |
| A | `backend/app/Models/User.php` | CMS relationships, casts and guarded publishing/indexing operations. |
| A | `backend/app/Providers/AppServiceProvider.php` | Adopt the supplied Laravel scaffold runtime/test configuration. |
| A | `backend/app/Services/CanonicalUrl.php` | Publishing, similarity, canonical, schema or public-data mapping. |
| A | `backend/app/Services/PublicArticleData.php` | Publishing, similarity, canonical, schema or public-data mapping. |
| A | `backend/app/Services/PublishingGate.php` | Publishing, similarity, canonical, schema or public-data mapping. |
| A | `backend/app/Services/SchemaBuilder.php` | Publishing, similarity, canonical, schema or public-data mapping. |
| A | `backend/app/Services/SimilarityChecker.php` | Publishing, similarity, canonical, schema or public-data mapping. |
| A | `backend/artisan` | Adopt the supplied Laravel scaffold runtime/test configuration. |
| A | `backend/bootstrap/app.php` | Laravel 12 bootstrapping, API routes and middleware. |
| A | `backend/bootstrap/cache/.gitignore` | Retain required writable Laravel runtime directories without generated files. |
| A | `backend/bootstrap/providers.php` | Laravel 12 bootstrapping, API routes and middleware. |
| A | `backend/composer.json` | Lock Laravel scaffold and required Sanctum dependencies; backend-only scripts. |
| A | `backend/composer.lock` | Lock Laravel scaffold and required Sanctum dependencies; backend-only scripts. |
| A | `backend/config/africaranked.php` | Laravel runtime defaults and supplied CMS rules. |
| A | `backend/config/app.php` | Laravel runtime defaults and supplied CMS rules. |
| A | `backend/config/auth.php` | Laravel runtime defaults and supplied CMS rules. |
| A | `backend/config/cache.php` | Laravel runtime defaults and supplied CMS rules. |
| A | `backend/config/cms.php` | Laravel runtime defaults and supplied CMS rules. |
| A | `backend/config/database.php` | Laravel runtime defaults and supplied CMS rules. |
| A | `backend/config/filesystems.php` | Laravel runtime defaults and supplied CMS rules. |
| A | `backend/config/logging.php` | Laravel runtime defaults and supplied CMS rules. |
| A | `backend/config/mail.php` | Laravel runtime defaults and supplied CMS rules. |
| A | `backend/config/queue.php` | Laravel runtime defaults and supplied CMS rules. |
| A | `backend/config/sanctum.php` | Laravel runtime defaults and supplied CMS rules. |
| A | `backend/config/services.php` | Laravel runtime defaults and supplied CMS rules. |
| A | `backend/config/session.php` | Laravel runtime defaults and supplied CMS rules. |
| A | `backend/database/.gitignore` | Adopt the supplied Laravel scaffold runtime/test configuration. |
| A | `backend/database/factories/UserFactory.php` | Adopt the supplied Laravel scaffold runtime/test configuration. |
| A | `backend/database/migrations/0001_01_01_000000_create_users_table.php` | Reversible new tables in the new backend; no existing table alteration. |
| A | `backend/database/migrations/0001_01_01_000001_create_cache_table.php` | Reversible new tables in the new backend; no existing table alteration. |
| A | `backend/database/migrations/0001_01_01_000002_create_jobs_table.php` | Reversible new tables in the new backend; no existing table alteration. |
| A | `backend/database/migrations/2026_11_02_000001_create_core_tables.php` | Reversible new tables in the new backend; no existing table alteration. |
| A | `backend/database/migrations/2026_11_02_000002_create_personal_access_tokens_table.php` | Reversible new tables in the new backend; no existing table alteration. |
| A | `backend/database/seeders/CmsCategorySeeder.php` | Seed/import only actual existing categories and article snapshots. |
| A | `backend/database/seeders/DatabaseSeeder.php` | Seed/import only actual existing categories and article snapshots. |
| A | `backend/database/seeders/legacy-content.json` | Lossless export of the original site content for repeatable import. |
| A | `backend/editorial-reference/editorial-agent-system-prompt.md` | Preserve supplied package/editorial reference; no automated publication. |
| A | `backend/editorial-reference/launch-wave-1.md` | Preserve supplied package/editorial reference; no automated publication. |
| A | `backend/editorial-reference/package-README.md` | Preserve supplied package/editorial reference; no automated publication. |
| A | `backend/phpunit.xml` | Adopt the supplied Laravel scaffold runtime/test configuration. |
| A | `backend/public/.htaccess` | Laravel HTTP entrypoint/rewrite; no copied scaffold brand assets. |
| A | `backend/public/index.php` | Laravel HTTP entrypoint/rewrite; no copied scaffold brand assets. |
| A | `backend/routes/api.php` | Add API, original URL compatibility and scheduler registration. |
| A | `backend/routes/console.php` | Add API, original URL compatibility and scheduler registration. |
| A | `backend/routes/web.php` | Add API, original URL compatibility and scheduler registration. |
| A | `backend/storage/app/.gitignore` | Retain required writable Laravel runtime directories without generated files. |
| A | `backend/storage/app/private/.gitignore` | Retain required writable Laravel runtime directories without generated files. |
| A | `backend/storage/app/public/.gitignore` | Retain required writable Laravel runtime directories without generated files. |
| A | `backend/storage/framework/.gitignore` | Retain required writable Laravel runtime directories without generated files. |
| A | `backend/storage/framework/cache/.gitignore` | Retain required writable Laravel runtime directories without generated files. |
| A | `backend/storage/framework/cache/data/.gitignore` | Retain required writable Laravel runtime directories without generated files. |
| A | `backend/storage/framework/sessions/.gitignore` | Retain required writable Laravel runtime directories without generated files. |
| A | `backend/storage/framework/testing/.gitignore` | Retain required writable Laravel runtime directories without generated files. |
| A | `backend/storage/framework/views/.gitignore` | Retain required writable Laravel runtime directories without generated files. |
| A | `backend/tests/Feature/CmsIntegrationTest.php` | Validate package gates, integration, safety boundaries and existing URLs. |
| A | `backend/tests/Feature/ExampleTest.php` | Validate package gates, integration, safety boundaries and existing URLs. |
| A | `backend/tests/Feature/PublishingGateTest.php` | Validate package gates, integration, safety boundaries and existing URLs. |
| A | `backend/tests/Fixtures/frontend/index.html` | Validate package gates, integration, safety boundaries and existing URLs. |
| A | `backend/tests/TestCase.php` | Validate package gates, integration, safety boundaries and existing URLs. |
| A | `backend/tests/Unit/ExampleTest.php` | Validate package gates, integration, safety boundaries and existing URLs. |
| A | `docs/cms/evidence/guide-after.png` | Document mapping, setup, limits, rollback and validation evidence. |
| A | `docs/cms/evidence/guide-before.png` | Document mapping, setup, limits, rollback and validation evidence. |
| A | `docs/cms/evidence/ranking-after.png` | Document mapping, setup, limits, rollback and validation evidence. |
| A | `docs/cms/evidence/ranking-before.png` | Document mapping, setup, limits, rollback and validation evidence. |
| A | `docs/cms/evidence/render-comparison.json` | Document mapping, setup, limits, rollback and validation evidence. |
| A | `docs/cms/integration-report.md` | Document mapping, setup, limits, rollback and validation evidence. |
| M | `src/App.tsx` | Serve owner-authored pages and use backend-backed navigation when bootstrapped. |
| A | `src/components/ArticleGovernance.tsx` | Add unstyled semantic modules in package content order. |
| M | `src/components/SeoHead.tsx` | Add CMS canonical/noindex/linked graph while preserving legacy metadata. |
| M | `src/data/articles.ts` | Use backend public catalog when present; preserve all static content literals/fallback. |
| A | `src/lib/cms.ts` | Parse/cache the backend JSON bootstrap and declare governance fields. |
| M | `src/pages/ArticlePage.tsx` | Add module insertion points without replacing existing markup or FAQ. |
| M | `src/types/index.ts` | Add optional CMS data type without changing original article fields. |

Commits: `56bddf9` (backend), `0af690e` (frontend bridge and human workflow), followed by the documentation/evidence commit containing this report.
