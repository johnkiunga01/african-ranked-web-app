# Launch-readiness handover — 2 November 2026

## Shared work and deployment boundaries

The existing Codex task migrated the reader site and staff workspace to Laravel and deployed Railway. This work continues that accepted platform from GitHub main, rather than installing the earlier standalone backend beside it. The earlier `feature/cms-governance` branch remains preserved as history.

Work branch: `feature/editorial-launch-readiness`. Commit identity: `Codex CMS Integration <codex@openai.com>`. Existing Railway project: `757421e4-670a-4465-a4ba-48cf0a54015b`; environment: `e9584cd1-5734-4f4e-b9eb-f0e93eb163c8`. Although Railway calls the environment `production`, its application is staging. Preserve APP_ENV=staging, APP_PREVIEW=true, account data, APP_KEY, secrets, PostgreSQL and persistent storage.

Web service `679eee19-1369-4f7f-a417-6f4d9cc9d55b`, worker `0a92350e-671e-45a6-87c4-6e800e07ec69` and scheduler `16b2caa5-b99a-4665-ae8e-a59eeab08fba` remain owned by the existing Railway deployment configuration. This branch does not modify Dockerfile, IaC, service variables, volume mounts, dependency manifests, migrations, public design or inherited article content. A shared workspace note records these ownership boundaries. There are no schema migrations to apply for these changes.

## Delivered changes

- A private **Content readiness** view, scoped to each author's permitted articles, plus a read-only `content:audit` command. `--repository` inspects JSON without requiring a database and performs no import or provider request.
- Server-side publication checks for body length, visible sources, complete FAQs, unresolved placeholders and numbered-ranking completeness/explanations/methodology. All publication routes call the same check. A failed bulk publication leaves every live pointer unchanged.
- An explicit Gemini drafting choice alongside OpenAI, using existing configured credentials and the same author ownership/opt-in, consent, quota, concurrency, provenance and private-revision controls. No paid-provider fallback or automatic retry.
- A staging robots correction: preview/non-production robots blocks crawling while production retains its established rules. Existing noindex headers remain enabled.
- Additional CI safeguards for protected public design/content/dependency files and an inherited-content audit artifact. Existing PHP SQLite/PostgreSQL and Chromium jobs are retained.
- A bounded read-only staging acceptance script, the real inherited-content audit, and the small-team workflow guide.

The package's 1,300-word minimum is an editorial rule, not a search ranking guarantee. The structural policy does not claim to implement independent fact checking, image-rights confirmation, originality detection or per-criterion score validation. Those checks remain mandatory human review. The current revision schema has no typed ranking-score/fact-verification store; do not describe text detection as equivalent to one. The Excel guide may establish a more precise structured reporting workflow once supplied.

## Actual observations

[The original live HTTP observation](railway-staging-observation.json) passed 19 of 20 public checks. Root, health, staff login, XML/feed/llms endpoints and built assets responded; preview responses carried noindex. Anonymous staff requests redirected to the real Laravel login. Missing pages returned 404. The outstanding result was the old permissive robots response; its correction is included in this branch. This file is a dated observation, not a claim that every new change is already deployed.

Railway's web, worker, scheduler and Postgres reported SUCCESS for the inspected deployment. Historical failed builds were visible but were superseded by successful active deployments; there was no service/volume replacement or restart by this task. Runtime health alone does not prove worker job completion, restore capability, mail delivery or account/MFA acceptance.

The owner reports that `johnkiunga01@gmail.com` account setup is complete. No invitation was recreated, password reset or MFA change performed here. This cloud shell did not expose the desktop SSH login, so account state and private live author journeys were not independently checked. Use the owner's existing connection rather than creating a public bootstrap route or copying review secrets.

[The content audit](inherited-content-audit.json) inspects the actual 13 repository records: zero pass the structural rules, with bodies of 143–630 words and zero supported visible source links. No traffic figures, verified facts, bylines, sources, scores, image permissions or AI-generated filler were invented. Existing research and saved editorial work were not overwritten or re-imported into shared state.

## Release procedure

1. Fetch latest main and preserve other agent changes. Review the PR, including the public protected-file check. Require the application and readiness workflows to pass; never force-push main or reuse an active worktree.
2. Release through the existing Railway source/deployment workflow only. Do not change service roots, keys, databases, roles, secrets or volumes. No database migration is introduced here.
3. Observe a terminal SUCCESS for web/worker/scheduler before describing the release as deployed. Re-run the read-only public checker against the same HTTPS staging origin. Check that robots now disallows `/` and noindex headers remain in place.
4. Using the existing owner session, inspect Content readiness and author AI permissions. Do not reset the owner to perform a walkthrough. Invite only the named real authors. Use real private editorial work when verifying a live save; synthetic tests belong only in local/CI fixtures.
5. Check persistence with a deliberate no-content-change redeploy only when the platform owner agrees. Preserve published pointers and editorial revisions. Do not run test seeders or import a local SQLite/review database into Railway.

## Remaining evidence before launch

- Read and map the actual Excel guide, then create or assign private briefs without guessing its contents.
- Finish reporting and source/image/byline verification for a first real pilot; do not count inherited drafts or regional duplicates as accepted articles. Keep the existing project's accepted-content target and founder approval gate.
- Confirm owner MFA/recovery and actual invited-author permissions using the existing accounts. Local/CI fixtures prove code behavior, not live account completion.
- Configure the chosen real mail transport and prove invitation/password-recovery delivery in the owner's inbox. Newsletter request storage is not double opt-in, delivery or unsubscribe; do not claim those features are complete.
- Configure/verify Railway database backups, restore one to an isolated disposable database and compare article/revision/ownership pointers; verify durable files separately. A volume or successful build is not restore evidence. No restore was run against shared staging by this task.
- Observe an owner-approved real research/drafting request through the durable worker; keep provider failures private and publication manual. No paid call was made solely to test access here.
- Supply approved legal/trust/editorial text, actual media-rights records and production domain details. Preserve noindex until a founder-approved concrete release candidate is ready.

## Rollback

Revert this branch's source commits through GitHub and redeploy the previous successful revision using the existing Railway workflow. Do not roll back PostgreSQL, rotate APP_KEY or delete volumes; this branch adds no schema changes. Source rollback retains all article revisions and accounts. Note that reverting the robots fix restores its previously permissive staging text; keep preview noindex enabled throughout.
