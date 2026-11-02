# Staff workspace: first backend milestone

**Current update:** [Editorial automation and GPT drafting](EDITORIAL-AUTOMATION.md) supersedes earlier manual-import and environment-only credential guidance. All 13 review articles are now private CMS drafts; manual import controls have been removed.

## Implemented

- `/staff/login` and `/staff`: styled Blade workspace with separate super-admin/author navigation and role-aware article counts.
- Invite-only staff onboarding: random single-use links, SHA-256 token storage, 24-hour expiry, revocation/reissue, user-selected passwords and fixed invited email/role. Normal seeding creates no staff or default password.
- Session-based login/logout, password reset through Laravel's broker, recent-password confirmation for team management, and failed-login limits that persist across actual HTTP requests.
- Server-side article policies and staff access checks. Authors cannot read/edit another author's article, manage staff, approve/publish/withdraw, or forge ownership/publication through form data.
- User activation/role management, last-active-verified-super-admin protection and session-version invalidation after role changes, suspension or password reset.
- Database article, category, revision, invitation and audit tables. Native Laravel/Blade back-office components were selected for this first milestone; no new production package or React runtime was added.
- Author draft creation/editing, private preview, submission, review feedback, approval checklist, publication and withdrawal. Every save creates a new numbered draft revision with an optimistic concurrency check. Public readers continue seeing the prior published revision until a reviewed replacement is published.
- Public article/category/SEO/feed/sitemap reads include only published CMS revisions. A CMS record masks a legacy record with the same slug even when withdrawn. Legacy JSON remains sample material in non-production preview; production never implicitly publishes it.
- Audit records for login, invitation, access, password-reset and editorial actions. Public rendering excludes private source notes, review feedback, checklist and revision state.

## Review access and first owner account

Use `/staff/login` on the current verified staging origin. The owner-specific administrator invitation was created only in the isolated review database after taking a local backup and applying migrations. The owner chooses the password and display name; no account is active until the invitation is accepted. The one-time URL is kept in `storage/app/private/owner-super-admin-setup.url` with mode 0600, not in Git or a public page. Do not log or paste its token into documentation. It expires after 24 hours and becomes unusable after acceptance/reissue.

For subsequent operator-driven setup on a deliberately selected database:

```bash
php artisan staff:invite-admin YOUR_EMAIL
```

This command saves a private setup URL file and does not print a password/token. An optional `--link-file` must point to a new file under the private storage directory. Existing files are never overwritten. Do not run it blindly against a production database or invent a default administrator password.

The review helper `scripts/review-artisan.sh` selects the same isolated database/key/cache/session settings as the review server. Set `REVIEW_URL` to the current verified HTTPS staging origin when using it so setup links match that origin. It does not select the development `.env` database. The dedicated review cookie, cache and session directories keep request limits and sessions separate from browser-test fixtures and normal development.

## Browser walkthrough

1. Open the private owner setup link in an ordinary browser, choose your name and a password with at least 12 characters, mixed case and a number.
2. Open **Team**, create an author invitation, and copy its one-time link securely. In this review, email delivery is not enabled. To test a second identity yourself, use a separate/private browser session rather than the administrator's existing session.
3. As the author, create a draft with a stable slug, introduction, category, body, HTTPS hero image and research notes. Use ##/### for headings and - for lists. Save, preview, and submit.
4. As super admin, open the submitted article. Request changes with feedback or complete the four editorial-review confirmations and approve the exact revision. Approval does not publish automatically.
5. Publish the approved revision and check its normal public article URL and discovery documents.
6. Edit again as author. Verify that the public article retains the earlier version; a new review is required. Try another author's private URL or team page and confirm access is denied.
7. Suspend/reactivate an author through Team and verify previous sessions no longer work. Do not demote/suspend the last active administrator.

Do not submit real subscriptions or treat sample content as fact-checked reporting. An approval checkbox records the reviewer's attestation; it does not automatically verify claims, keyword demand, image rights or launch readiness.

## Existing seed content

The 13 public inherited examples remain review-only JSON samples until explicitly migrated. The first staff dashboard can therefore show zero CMS articles while the public preview still shows those samples. The operator command:

```bash
php artisan content:import-seeds
```

imports the current JSON articles as **private, unassigned drafts** and the category definitions idempotently. It preserves existing CMS records and does not publish or create fictional author accounts. Imported drafts mask the same sample URLs, so their preview pages disappear publicly until appropriately assigned, reviewed and published. Do not run this command on the shared review just to populate a dashboard without accounting for that effect. Original historical claims/bylines/dates stay unverified; assignment tooling and historical-metadata review are follow-up work.

## Validation evidence

- PHP suite: 42 tests, 1,607 assertions passed at this milestone, including original public routes plus staff authentication, invitations, reset/session revocation, direct-request authorization, last-admin checks, editorial transitions, stale revision rejection, draft discovery exclusion, import idempotency, production seed exclusion, private setup files and mail-failure fallback.
- Browser suite: nine tests passed. The staff tests use a disposable SQLite database and test-only fixtures guarded to `/tmp/african-ranked-browser-*`; no actual owner account/password is seeded. They exercise the author/admin publishing cycle, revision isolation, new-author onboarding, mobile access and rate limits across real HTTP requests.
- Review database migration applied only after a local backup. Existing owner invitation remains unused; it was not consumed by automated browser tests.
- The editorial migration passed up → down → up on a disposable SQLite database. Both the existing development and review SQLite databases were backed up before migration. Intended production-database concurrency/performance checks remain outstanding. Editorial activity/history displays Africa/Nairobi time; persisted timestamps remain UTC.

## Deliberately unfinished launch tasks

This is a usable first workflow, not the completed launch backend. Required follow-up includes super-admin two-factor authentication/recovery, real mail delivery and inbox verification, a richer block/table/FAQ editor, media uploads/storage and rights records, real author profile pages and assignment, autosave/revision restoration, scheduled publishing/queues, more complete operational audit views and production database/deployment checks. The 160-article acceptance program has not started. No articles are automatically counted as launch-ready and no OpenSEO research results are claimed.

## Upstream sync — 2 November 2026

Synced upstream main `59ffdad`: seven new wildlife articles and revisions to six existing examples, for 13 preview articles. The local safari hero-image repair was preserved. See `UPSTREAM-CONTENT-SYNC.json`. CMS records, staff users and publication decisions were preserved. These articles remain unverified preview material and do not count toward the 160 accepted launch articles.

## Growth workspace — 2 November 2026

Super-admin overview now separates CMS records from unverified preview samples. Admin-only SEO, analytics, revenue and integration-planning sections provide an article-readiness queue, isolated newsletter request counts, measurement requirements and ad/affiliate/email implementation plans. Integration stages and non-secret references persist in `growth_plans`, require recent password confirmation, reject stale updates and create audit events. Authors cannot access these pages or mutations.

These records do not connect external services: OAuth, OpenSEO tool access, analytics ingestion, consent/tracking, advertising activation, affiliate links and revenue imports remain outstanding. No traffic, revenue, SEO score or acceptance count is fabricated. The acceptance register is explicitly not yet linked to the dashboard. Readiness checks examine current revision fields and recorded review, not factual correctness.

Expired POSTs to staff login now redirect with HTTP 303 to a fresh form and explanatory message. CSRF verification remains enabled; credentials are not replayed or flashed. A browser test expires the session, confirms recovery and then signs in successfully with the fresh form. No owner password was changed or used for testing.

Validation: 45 PHP tests / 2,888 assertions and 10 browser tests passed; production asset build passed. Review database backed up before the additive migration. Fresh and expired login submissions were also checked through the real HTTPS tunnel with invalid fixture credentials only.

## Article management and search integrations — 2 November 2026

See [implementation and design evidence](ADMIN-UX-HARDENING.md). Article inventory now includes repository examples, search/category/status/source filters, stable sorting, pagination and explicit management actions. Super admins can deliberately import an example as a private assigned draft and reassign CMS articles in a new revision. Public-pointer isolation and author ownership remain enforced.

Search Console now has working manual Pages CSV import/reporting without API credentials. OpenSEO now has a server-side Streamable HTTP MCP client and a protected free verification action, using `OPENSEO_API_KEY`. This is protocol-tested but not yet authenticated against the real account; no key was supplied and no research was run. These additions supersede earlier statements that all integrations are merely planning records. Remaining planning-only providers are labeled accordingly.

Validation: 51 PHP tests / 2,952 assertions, 11 browser tests, asset build and formatter passed. Browser evidence uses disposable fixture identities, never the owner's password. Backups preceded additive review migrations; no shared-review articles were automatically imported or published.
