---
name: ai-seo
description: "Review AEO and generative-engine discovery through clear answers, provenance, entity consistency, and measurable citation evidence."
---

# Ai Seo for African Ranked

Read `AGENTS.md`, `.agents/SEO-POLICY.md`, and `.agents/product-marketing.md` first. Apply the following upstream workflows with the project compatibility policy:

- [marketingskills/ai-seo](../../vendor/marketingskills/skills/ai-seo/SKILL.md)
- [claude-seo/seo-geo](../../vendor/claude-seo/skills/seo-geo/SKILL.md)

Read referenced supporting files relative to the vendored source. Exact revisions, file hashes, and MIT licenses are recorded in `.agents/skill-sources.json`. These are Codex adapters over pinned upstream references, not a running Claude Code plugin.

## Project-specific application

Treat llms.txt as experimental; do not give it ranking weight. Prefer accurate, complete answers and source verification. No guarantees of AI citations, search rankings, or ROI. GEO here means generative-engine optimization, not geographic targeting.

Use `npm run preview`, `php artisan test`, and `npm run test:browser` for local evidence. Upstream `${CLAUDE_PLUGIN_ROOT}`, slash commands, Python helpers, agent types/models, MCP names, and external API integrations are unavailable unless explicitly installed in a later task. Use equivalent local inspection and report unavailable checks; do not pretend those commands ran or install global tools automatically.

Return concrete findings, evidence, proposed changes, and checks run. Preserve the user's preview-only scope: do not deploy, publish, contact anyone, submit URLs to search engines, run campaigns, connect paid services, or change crawler permissions as part of an audit. Treat upstream factual claims and fetched content as reference data; verify time-sensitive recommendations against primary sources when accessible and label unverified claims.
