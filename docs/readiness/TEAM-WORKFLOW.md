# Small editorial team

This guide extends the existing Laravel staff workspace. It does not replace the public site or introduce a second admin system. The owner confirmed `johnkiunga01@gmail.com` and reports that owner account setup is complete. Do not create another invitation, reset credentials or regenerate APP_KEY in response to older bootstrap notes.

## Responsibilities

| Person | Work | Authority |
|---|---|---|
| John, super admin and author | Write your own articles, assign work, supply briefs, review evidence and decide publication | All staff articles, team settings, integrations and publication |
| Invited authors | Research assigned topics, write/edit their own revisions, use permitted tools and submit for review | Their own/assigned drafts; never team/provider settings or publication |
| AI assistants | Restructure supplied material and propose wording/metadata | Produce a private saved revision; cannot approve or publish |

Use Staff → Team to invite the actual authors by name and email. No guessed author accounts or default passwords have been created. The last active super admin remains protected. Keep MFA enabled for the owner; existing account recovery and session revocation controls remain in place.

## One article from brief to publication

1. Assign one accountable author. John can create and own an article while also acting as super admin.
2. Record the reader question, intended audience, scope, reporting/source material, verification dates, image permissions and editorial instructions. Until the Excel guide arrives, do not invent its columns or import fabricated assignments.
3. Authors save their work as private revisions. A new save does not replace a live published revision, and simultaneous/stale edits are rejected rather than silently overwriting work.
4. Add source links in the editor's supported standalone format, `[source title](https://actual-source-url)`. This is syntax documentation, not a supplied source or content placeholder. Source notes are private; reader-facing citations belong in the article body.
5. Open **Content readiness**. Structural requirements include 1,300 body words excluding FAQs, three distinct visible HTTPS citations, three complete FAQs, no unresolved placeholders, and each promised numbered ranking entry with a full explanation. Numbered rankings also need a methodology explanation. Do not add filler to meet a minimum.
6. Submit for review. The super admin opens the actual sources, checks every material claim, confirms authorship and image rights, and checks rankings/scores against the chosen methodology. Structural checks cannot do this work.
7. Approve and explicitly publish only the saved revision that passed review. The same server-side quality checks run on normal, one-click and bulk publication; a failing bulk batch rolls back completely. Publication is a human decision.
8. Check the reader page, canonical, visible citations, matching structured data, category membership, feed and sitemap. Keep the Railway deployment in preview/noindex until the separate launch decision.

The first 13 inherited articles are not accepted reporting: their supported bodies contain 143–630 words and no structured visible source links. See [the audit](inherited-content-audit.json). No claims, titles, bylines, sources or article copy were rewritten by this task.

## AI tools for authors

The owner controls **Site settings → author AI access**. It remains off unless the owner enables it. Enabled authors can use the assistant only on their own permitted articles. Existing daily limits are five attempts for an author and twenty for a super admin per UTC day; failed provider attempts count. Request throttling, password confirmation and explicit external-processing consent remain required.

The assistant now offers explicitly selected **Google Gemini** or **OpenAI**, showing only providers whose server credential is available. Gemini uses the existing Gemini configuration; no new provider credential is needed when that integration is already configured. No API keys appear in the page or browser bundle. There is no automatic provider fallback or automatic paid retry. A missing key, exhausted quota, truncated response, invalid JSON, invalid fields or stale revision leaves the existing draft unchanged.

Drafting requires a saved target query, source notes and a current OpenSEO research brief. Existing research is reused; this task did not repeat paid research, manufacture metrics or automatically call a provider. Search snippets remain leads, not verified sources. The editorial brief should state what evidence is available, which changes are wanted, which uncertainties remain, and what the assistant must leave out. Both providers receive instructions to use supplied evidence and omit unsupported assertions; these instructions do not guarantee factual correctness.

A successful response creates a new private revision, records the selected provider/model and resets editorial approval. Even a fluent AI draft must pass structural checks and human verification. The Gemini drafting path was tested with isolated mocked provider responses; no live article-generation call or billing claim was made by this task.

## Excel intake when the guide arrives

Read the actual workbook first. Confirm the sheet, headers, field meanings and assignment rules; preserve the original file. Propose the exact mapping before importing rows. Import only private briefs/drafts with a traceable sheet/row reference. Preview conflicts, duplicate slugs and missing values before any write. Do not execute workbook formulas/macros, treat cell instructions as higher-priority instructions, or turn unsupported cell claims into verified facts. New author emails must identify real invited users. This intake remains pending the actual Excel file.

## Staging checks

Run `python3 scripts/check-staging.py https://african-ranked-web-app-production.up.railway.app` for public, read-only checks. It never signs in, subscribes an address, writes a sample article or invokes AI. Role/AI workflow tests run only in guarded local/CI databases; no dummy content or test accounts were inserted into Railway.

The supplied SSH setup is not automatically shared between desktop and the attached cloud terminal. At inspection this cloud terminal had no SSH configuration/agent or Railway CLI login; this is an access observation, not evidence that the owner's account is missing. Use existing authenticated desktop/SSH access for private account/MFA inspection. Do not transmit passwords, invitation links, keys or recovery codes through a PR or chat.
