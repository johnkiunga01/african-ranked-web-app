# Production activation evidence — 13 November 2026

This is an evidence record, not a substitute for CMS records or publications.

## CMS policy pages

Created all three real pages through the authenticated, audited CMS draft-save workflow using the existing verified owner actor. Each started at version 1, with footer placement enabled. Exact saved content was read back and verified. Subsequent owner-side work published these same revisions; preserve those decisions.

| Page | CMS page / revision ID | Current public URL |
|---|---|---|
| Privacy Policy | 1 / 1 | https://africanranked.com/pages/privacy-policy |
| Terms of Use | 2 / 2 | https://africanranked.com/pages/terms-of-use |
| Cookies & Analytics | 3 / 3 | https://africanranked.com/pages/cookies-and-analytics |

All three public URLs return HTTP 200 and self-canonical HTTPS URLs. All three footer links appear on the homepage. Saved previews were rendered at 390px and 1440px: one H1 each, no horizontal overflow, existing Plus Jakarta Sans styling. Preview documents and assets were fetched with TLS verification, then replayed locally for layout inspection; this is not evidence of authenticated shared-browser access.

Text was reconciled against implemented storage, consent, newsletter, staff, provider and mail configuration. Operator/contact: African Ranked, info@africanranked.com. No invented registration, address, governing-law clause or fixed retention period was added. Provider account retention and legal obligations remain owner responsibilities; CMS publication does not constitute independent legal review.

## Analytics

The current owner-configured production flags are GA4_ENABLED=true and GA4_PRIVACY_NOTICE_APPROVED=true, with G-8XMRKYBLQ6. The public consent interface is present. No duplicate tag was installed.

Isolated Chromium test of the actual Blade consent implementation, with a synthetic ID and intercepted Google SDK: zero SDK loads before consent; zero after rejecting and reloading; exactly one after accepting and accepting again; no further SDK loads after withdrawing and visiting another page. Default advertising permissions remain denied. This validates client control flow, not genuine event delivery or Google property ownership.

Tag Assistant / GA4 Realtime / DebugView access is unavailable in this executor. Do not describe them as verified. Numeric property: 563981898; stream: 16326474386.

## Newsletter: public signup remains disabled

The existing SMTP2GO transport accepted one real owner-authorized confirmation email to the dated Gmail QA alias. The owner explicitly reported **not received**, including a request to check spam. Therefore inbox delivery has not passed. Do not enable public signup or represent the newsletter as operational.

Live production DOI checks on the isolated QA subscriber passed:

- Confirmation GET leaves the address pending.
- CSRF-protected explicit confirmation POST activates it.
- Replayed confirmation POST returns 404.
- Signed unsubscribe GET does not unsubscribe.
- Unsigned unsubscribe returns 403.
- Explicit signed unsubscribe POST clears verification and sets unsubscribe status; the QA address is suppressed.

The test subscriber was left unsubscribed. No campaign or external list was sent. No campaign sender exists in this release; eligibility state is not proof of a nonexistent campaign workflow.

Validation, honeypot, consent, rate limits and duplicate/replay tests pass locally. Added a separate regression ensuring genuinely expired tokens reject GET and POST without activation or mail, using only the disposable test database.

Configured mail sender remains john@salibay.com at mail-eu.smtp2go.com:587; the official public contact remains info@africanranked.com. Do not replace the sender until SMTP2GO verifies the intended sender/domain. SMTP acceptance alone does not expose Gmail delivery, bounce or suppression status: inspect SMTP2GO delivery activity for the QA recipient and verify sender authentication before another authorized test.

The temporary policy intake and owner-test flags were disabled after use. No raw tokens, credentials or capability URLs are included here.

## Deployment and handoff

PR31 merged after all checks passed: PostgreSQL and SQLite PHP, Chromium, public design protection and inherited-content audit. Production subsequently advanced through owner cleanup PR32/33; observed 9c5717c deployment SUCCESS, all four Railway services SUCCESS. This task must preserve the newer regional-indexing and sitemap changes.

Existing REST editorial list/get reads returned HTTP 200. Native OAuth Editorial list_drafts/get_article tools are not exposed in this executor; REST success does not establish native connector readiness. No editorial article writes or administrator MFA changes were performed.

Owner-provided PageSpeed baseline remains 99 performance and 100 accessibility/best practices/SEO. The attempted new PageSpeed API measurement returned HTTP 429, so no new score or performance improvement is claimed.

The direct live Chromium check was attempted and failed with ERR_CERT_AUTHORITY_INVALID under this executor's HTTPS inspection proxy. TLS verification was not disabled. Python's configured CA trust successfully fetched the public pages and assets. The preview layout and isolated consent tests above do not establish live Google event collection. A retained Railway mail-log query returned no matching application logs; SMTP2GO delivery logs are not connected here.
