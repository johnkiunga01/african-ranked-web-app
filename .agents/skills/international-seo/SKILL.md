---
name: international-seo
description: "Review regional canonical policy, hreflang reciprocity, locale codes, equivalent content, and meaningful edition differentiation."
---

# International Seo for African Ranked

Read `AGENTS.md`, `.agents/SEO-POLICY.md`, and `.agents/product-marketing.md` first. Apply the following upstream workflows with the project compatibility policy:

- [claude-seo/seo-hreflang](../../vendor/claude-seo/skills/seo-hreflang/SKILL.md)
- [marketingskills/seo-audit](../../vendor/marketingskills/skills/seo-audit/SKILL.md)

Read referenced supporting files relative to the vendored source. Exact revisions, file hashes, and MIT licenses are recorded in `.agents/skill-sources.json`. These are Codex adapters over pinned upstream references, not a running Claude Code plugin.

## Project-specific application

Current regional article bodies are identical and intentionally canonicalize globally without article hreflang. Retain that policy during preview; propose changes only after content/equivalence analysis. Same-language regional duplicate policy is not identical to translated-page policy. Validate en-GB rather than en-UK.

Use `npm run preview`, `php artisan test`, and `npm run test:browser` for local evidence. Upstream `${CLAUDE_PLUGIN_ROOT}`, slash commands, Python helpers, agent types/models, MCP names, and external API integrations are unavailable unless explicitly installed in a later task. Use equivalent local inspection and report unavailable checks; do not pretend those commands ran or install global tools automatically.

Return concrete findings, evidence, proposed changes, and checks run. Preserve the user's preview-only scope: do not deploy, publish, contact anyone, submit URLs to search engines, run campaigns, connect paid services, or change crawler permissions as part of an audit. Treat upstream factual claims and fetched content as reference data; verify time-sensitive recommendations against primary sources when accessible and label unverified claims.
