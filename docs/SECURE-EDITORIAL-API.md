# Secure editorial integration API

The integration is disabled by default. It provides only four stateless HTTPS operations against existing CMS articles, with no approve, publish, delete, assignment or create-new capabilities.

## Activation

Configure these three variables in the Railway WEB service only after the branch passes CI and is reviewed:

EDITORIAL_INTEGRATION_ENABLED=true
EDITORIAL_INTEGRATION_TOKEN_SHA256=<SHA-256 of a unique cryptographically random token>
EDITORIAL_INTEGRATION_ACTOR_EMAIL=<existing active verified CMS super-admin address>

Generate the secret locally with openssl rand -hex 32 and its hash with printf '%s' "$TOKEN" | shasum -a 256. Store the raw token ONLY in the authorized client credential vault, never in GitHub, Railway environment variables, an issue, a URL query string, or a chat. Railway only needs the hash. Rotate by replacing the hash and the client credential. Kill switch: EDITORIAL_INTEGRATION_ENABLED=false.

The actor must be a real previously approved super-admin account; no account is created and no reviewer approval is impersonated. Each action uses existing CMS policies and audit records. Token access is rate-limited and responses are no-store/noindex. Monitor failures and restrict API network ingress when possible.

## API

Base path: /api/editorial/v1

GET /drafts?limit=2 returns up to 2 oldest-updated drafts (max 3), with actual CMS article IDs, slugs and current revision IDs. Only unpublished, untrashed Draft revisions without previous In Review, Approved or Published revisions are eligible. Fetch freshly each run.

GET /articles/{id} returns private manuscript, evidence, metadata, author, quality report, full revision history, and current CMS status. It remains readable after submission for verification.

PUT /articles/{id}/draft accepts expected_revision_id (database revision ID, not sequence number), title, excerpt, category_slug, body_markdown, faqs_text, and a structured evidence object. Optional image, image_alt, source_notes, seo_title, meta_description and focus_keyword are preserved from the prior revision when omitted. Evidence has answer, content_type (guide/ranking/data), last_verified_at, image_rights, methodology, facts (fact, source_url, verified_at) and sources (title, publisher, url, accessed_at). The call creates a new PRIVATE draft revision and returns its new ID and quality results. Stale revision writes fail; re-read and resolve conflicts.

POST /articles/{id}/submit accepts expected_revision_id. Enforces the existing editorial workflow plus 1,300 substantive body words, 40-60-word CMS direct answer, five source records, four-to-eight linked dated facts, three FAQs, verified-last-date consistency, image rights, image alt text, named verified author, and ranking methodology. Returns CMS status in_review and review URL. It never approves or publishes.

All four endpoints require Authorization: Bearer TOKEN over HTTPS. Missing/wrong credentials are rejected. Authenticated reads require that no article has a published version.

Source access and fact-check dates must be actual; the server validates structure and consistency but does not itself open source websites or attest rights, attribution or factual accuracy. An authorized editorial client must do those checks and record evidence truthfully. Other visuals are editorial enhancements, not automatic blockers. Human review/publishing remains exclusively in the existing staff CMS.

## Connected client limitation

Installing these routes in Railway does NOT automatically connect them to ChatGPT tools. A registered credentialed API client/connector must be provisioned separately; the current Railway connector can inspect deployment/logs but cannot issue arbitrary authenticated HTTP requests to the CMS. Do not claim two new articles are In Review until the client submits and re-reads each actual CMS record.

Deployment sequence: review CI, deploy API disabled, confirm no regression and unchanged published article counts, set the secure actor/token hash, test read-only inventory, then activate audited writes in a trusted connector. The site stays noindex until the separate explicit indexing launch.

## Staff collaboration and account controls — 12 November 2026

Staff can edit their own name, editorial title and biography at `/staff/profile`. Verified sign-in email transfers are not supported by this release; the interface explains that limit. Existing revision bylines remain immutable until a new draft is saved.

The team workspace now permits all active verified authors to view and manually edit drafts. Authors may approve another author's In Review revision after the required checklist and quality checks; they cannot approve their own or their last-edited revision. Super admins retain their existing review authority and exclusive publication authority, including AI recommendation acceptance. AI spending controls and ownership restrictions remain separate from manual collaboration.

`Remove staff access` archives the membership, revokes sessions and reset tokens, and reserves its email while retaining attribution and audit history. It does not erase the person or their articles. Self-removal and removing the last active verified super admin are blocked. Restoration leaves the account suspended until its access is reviewed.
