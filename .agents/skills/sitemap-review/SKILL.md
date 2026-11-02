---
name: sitemap-review
description: "Check generated XML sitemaps, canonical URL inclusion, response status, lastmod evidence, and robots declarations."
---

# Sitemap Review for African Ranked

Read `AGENTS.md`, `.agents/SEO-POLICY.md`, and `.agents/product-marketing.md` first. Apply the following upstream workflows with the project compatibility policy:

- [claude-seo/seo-sitemap](../../vendor/claude-seo/skills/seo-sitemap/SKILL.md)

Read referenced supporting files relative to the vendored source. Exact revisions, file hashes, and MIT licenses are recorded in `.agents/skill-sources.json`. These are Codex adapters over pinned upstream references, not a running Claude Code plugin.

## Project-specific application

Use DiscoveryController and PublishingTest. Parse XML; request every listed URL; compare its canonical and robots signals. Preview noindex headers are expected. Do not submit sitemaps, ping IndexNow, or request indexing during testing.

Use `npm run preview`, `php artisan test`, and `npm run test:browser` for local evidence. Upstream `${CLAUDE_PLUGIN_ROOT}`, slash commands, Python helpers, agent types/models, MCP names, and external API integrations are unavailable unless explicitly installed in a later task. Use equivalent local inspection and report unavailable checks; do not pretend those commands ran or install global tools automatically.

Return concrete findings, evidence, proposed changes, and checks run. Preserve the user's preview-only scope: do not deploy, publish, contact anyone, submit URLs to search engines, run campaigns, connect paid services, or change crawler permissions as part of an audit. Treat upstream factual claims and fetched content as reference data; verify time-sensitive recommendations against primary sources when accessible and label unverified claims.
