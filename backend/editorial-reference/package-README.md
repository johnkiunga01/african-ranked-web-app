# Africa Ranked: Laravel CMS Package

Drop-in files for a Laravel 10/11 app (PHP 8.1+, Sanctum for the agent API). Nothing here was executed
in a sandbox, so run `php artisan test` after installing and expect to adjust namespaces/layout names.

## Install

1. Copy folders into your app (`app/`, `config/`, `database/migrations/`, `routes/`, `resources/views/`, `tests/`).
2. Merge `routes/web.php` and `routes/api.php` into yours. Install Sanctum if you don't have it: `php artisan install:api`.
3. Register the middleware globally (`bootstrap/app.php`, Laravel 11):
   `->withMiddleware(fn ($m) => $m->append(\App\Http\Middleware\NoIndexOutsideProduction::class))`
   (Laravel 10: add to `$middleware` in `app/Http/Kernel.php`.)
4. Schedule the stale check (`routes/console.php` or `Console/Kernel.php`): `Schedule::command('articles:flag-stale')->daily();`
5. Set `.env`: `APP_ENV=production` and `APP_URL=https://africaranked.com` on the live site only;
   `AI_TRAINING_ALLOWED=true|false` (business decision).
6. `php artisan migrate`, seed `categories` from `config('africaranked.categories')`, create authors
   (at least one with `is_reviewer = true`).
7. Create the agent token: `$user->createToken('editorial-agent', ['drafts:create'])->plainTextToken`.

## What each piece does

| File | Purpose |
|---|---|
| `config/africaranked.php` | Positioning statement, 1,300-word gate, bot policy, editions, re-verification intervals |
| `migrations/..._create_core_tables.php` | authors, categories, entities, sources, articles, pivots, ranking_items, corrections |
| `Models/Article::publish()` | The only path to `published`; runs every gate |
| `Services/PublishingGate` | 1,300 words, answer block, key facts, sources, FAQ, scored items, "Top N" really has N, disclosures, no placeholders, no unresolved agent flags, near-duplicate guard |
| `Models/Article::markIndexable()` | Separate step so pages stay `noindex` and out of sitemaps until audited (staged scale) |
| `Services/SchemaBuilder` | One linked JSON-LD `@graph` per article |
| `Controllers/RobotsController` | Dynamic robots.txt; blanket disallow on staging/tunnel hosts |
| `Middleware/NoIndexOutsideProduction` | `X-Robots-Tag: noindex` on any non-canonical host |
| `Controllers/LlmsTxtController` | `/llms.txt` generated from config and latest indexable articles |
| `Controllers/SitemapController` | Segmented sitemaps by edition; only published + indexable |
| `Controllers/AgentDraftController` | `POST /api/agent/drafts`: agents create drafts only, never publish |
| `Commands/FlagStaleArticles` | Moves overdue articles to `needs_update` and noindexes them |
| `resources/views/articles/show.blade.php` | Page template in the required content order |

## Rollout path for scale

Draft → in_review → approved → `publish()` (gates) → `markIndexable()` (audit). Raise volume only when the
previous stage's pages are indexed and performing: ~200, 5k, 50k, 500k. Keep unproven pages published-but-not-indexable.

## Known gaps (decide before launch)

- Admin UI: Filament or Nova recommended; the models are ready for resources.
- Entity pages (`/e/{slug}`), author pages, category pages and the About/Methodology/Editorial/Monetization pages still need routes and views.
- `SimilarityChecker` is fine for tens of thousands of articles; swap for MinHash or a vector index at scale.
- Regional editions: the route prefix and `hreflang` tags are not included.
