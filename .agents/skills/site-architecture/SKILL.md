---
name: site-architecture
description: "Review publisher navigation, category hierarchy, internal links, regional routes, and migration URL preservation."
---

# Site Architecture for African Ranked

Read `AGENTS.md`, `.agents/SEO-POLICY.md`, and `.agents/product-marketing.md` first. Apply the following upstream workflows with the project compatibility policy:

- [marketingskills/site-architecture](../../vendor/marketingskills/skills/site-architecture/SKILL.md)

Read referenced supporting files relative to the vendored source. Exact revisions, file hashes, and MIT licenses are recorded in `.agents/skill-sources.json`. These are Codex adapters over pinned upstream references, not a running Claude Code plugin.

## Project-specific application

Preserve existing slugs and URLs. Assess article/category links, breadcrumbs, related content, orphan pages, and empty-category behavior. Map existing routes before proposing any redirect or hierarchy change.

Use `npm run preview`, `php artisan test`, and `npm run test:browser` for local evidence. Upstream `${CLAUDE_PLUGIN_ROOT}`, slash commands, Python helpers, agent types/models, MCP names, and external API integrations are unavailable unless explicitly installed in a later task. Use equivalent local inspection and report unavailable checks; do not pretend those commands ran or install global tools automatically.

Return concrete findings, evidence, proposed changes, and checks run. Preserve the user's preview-only scope: do not deploy, publish, contact anyone, submit URLs to search engines, run campaigns, connect paid services, or change crawler permissions as part of an audit. Treat upstream factual claims and fetched content as reference data; verify time-sensitive recommendations against primary sources when accessible and label unverified claims.
