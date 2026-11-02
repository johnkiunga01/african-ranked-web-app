# OpenSEO setup handoff

**Live connection verified — 2 November 2026:** the encrypted review credential completed MCP initialization, discovery of 81 tools, and successful free `whoami` / `list_projects` reads. The existing Africa Ranked project was found and its context read. This supersedes earlier statements that the review credential was missing or unverified. No paid keyword/competitor research was used as a connection test. The earlier failure could not be reproduced, so its root cause is unconfirmed. Diagnostics now distinguish authentication, access, rate limiting, transport, provider availability, missing tools and protocol errors without leaking upstream bodies or credentials.

**Current update:** [Editorial automation and GPT drafting](EDITORIAL-AUTOMATION.md) supersedes earlier manual-import and environment-only credential guidance. All 13 review articles are now private CMS drafts; manual import controls have been removed.

## What was checked

This is a managed Codex session. Installed CLI: `codex-cli 0.163.0`. The native help supports `codex plugin marketplace add`, `codex plugin add`, and `codex mcp login`. Both `codex plugin list --json` and `codex mcp list --json` reported no locally installed entries. The active session's tool catalog contains no OpenSEO tools. The earlier ChatGPT plugin tag does not demonstrate a working connection in this Codex session.

The managed Codex configuration filesystem is mounted read-only. No permission escalation, authentication-file copying, alternate CODEX_HOME, or replacement of other integrations was attempted. The native plugin command has no project-scope installation option in its help, so global installation cannot be completed here.

Official documentation was read from the public `every-app/open-seo` repository at commit `99d9a49c8cb742d54eddd64d09efbc5302f6db89`, including `web/content/docs/codex-plugin.md`, `web/content/docs/mcp.md`, and `web/content/docs/skills/setup.md`. The direct documentation site was blocked, but authenticated HTTPS Git worked.

## Prepared locally

- Copied only the twelve **public** workflows from `plugins/openseo/skills/`, unchanged. No internal repository skills, hooks, executables, or helper scripts were installed.
- Preserved the existing Laravel-specific `seo-audit` adapter as `laravel-seo-audit`, updating its local references. The official OpenSEO `seo-audit` now retains its actual name. No command aliases were invented and no existing skill was overwritten.
- Retained the upstream MIT license and recorded source revision/file hashes in `.agents/openseo-installation.json`.
- Added the hosted OAuth endpoint as an additive project-local MCP entry in `.codex/config.toml`. It contains no credentials. TOML syntax is validated, but `codex mcp list` still did not expose a server from this managed session; project configuration is not proof of an active connection.
- Saved additive environment draft allowlist entries for `app.openseo.so` and `openseo.so`, preserving existing destinations. This does not apply runtime changes or publish anything.

## What remains unverified

- The endpoint check failed at the egress proxy: CONNECT denied, HTTP 403. It did **not** reach OpenSEO, so this is not evidence of a failed sign-in or missing account.
- OAuth sign-in was not attempted; there is no reason to request an API key in chat.
- Native skill discovery awaits the next Codex chat/reload. Files copied to the project do not prove that this active agent loaded them.
- `whoami` and `list_projects` were not run because their tools are absent. No project was created, no SEO workflow or paid research was run, and no account state was changed.

## User steps

1. Review/save the OpenSEO network additions in environment settings, then publish the **development environment** when ready to apply/snapshot that configuration. This is not a website deployment. Recheck endpoint connectivity after the runtime actually changes.
2. Complete the connection in the **Codex client**, not a regular ChatGPT conversation. The preferred [official Codex plugin guide](https://openseo.so/docs/codex-plugin) describes Plugins → OpenSEO → Install, approve sign-in if prompted, then start a new Codex chat. On a writable CLI client the documented commands are:

   ```bash
   codex plugin marketplace add every-app/open-seo
   codex plugin add openseo@openseo
   ```

   Start a new Codex session after plugin installation so its bundled server registers. If needed, run `codex mcp login openseo` and approve OAuth in your browser. Do not log out existing integrations as a first step.
3. Since public skills are already staged in this project, avoid leaving duplicate manual and plugin-bundled skills. Once the official plugin's skills are actually discovered, let the agent remove only the matching manual copies using `.agents/openseo-installation.json` hashes. Preserve the Laravel adapter and other project skills. Do not remove the fallback before the plugin works.
4. If the managed client cannot install the plugin, use the documented **Codex Settings → MCP servers → Add server** fallback: name `openseo`, Streamable HTTP, `https://app.openseo.so/mcp`; save, select Restart when prompted, then Authenticate if needed. The staged public skills supply workflows for this fallback. Do not add the same server again if the plugin already exposes it.
5. Say: **“Check that OpenSEO is connected.”** The agent should run only the free `whoami` and `list_projects` reads and check skill discovery. If no projects exist, confirm the website/domain before creating a free project. The development site's configured localhost origin is not proof of the intended production domain.

## Workflows after verification

Codex supports the `$` skill picker or natural-language requests. Use the actual discovered skill and plugin namespace rather than assuming Claude-style slash commands:

- “Run OpenSEO SEO Audit for my site.”
- “Run OpenSEO SEO Project Setup.”
- “Run OpenSEO Keyword Research.”
- “Run OpenSEO Local SEO.”

Current business context is already in `.agents/product-marketing.md`: ads plus affiliate revenue, global English-speaking readers interested in Africa, preview-only stage. Reuse it instead of repeating the business interview. The public SEO workflow instructions do not authorize launch, outbound messages, paid research during installation, or invented keyword metrics.

## Application connector added — 2 November 2026

The Laravel admin now includes its own server-side MCP verification client, separate from whether Codex exposes native OpenSEO tools. Official MCP docs were rechecked: https://openseo.so/docs/mcp documents bearer API keys for headless use at `https://app.openseo.so/mcp`.

Set `OPENSEO_API_KEY` privately in the application's server environment or `.env`. Open Staff → Integrations → Verify MCP connection. Recent-password confirmation and rate limiting apply. This performs initialization, tool discovery and only `whoami`/`list_projects`; no research credits are spent. Missing credentials, provider failures and tool errors do not produce a successful connection status. No secret value/raw account result is displayed or stored in audit records. This does not authenticate the Codex plugin or expose its tools to this chat.

The protocol client has JSON/SSE/session/error tests. Live authenticated verification has not been performed because a real credential has not been configured. Do not interpret test fixtures as proof that the real account is connected. Detailed implementation/limits: ADMIN-UX-HARDENING.md.
