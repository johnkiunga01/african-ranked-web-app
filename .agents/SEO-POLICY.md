# SEO skill compatibility policy

African Ranked is in **preview and testing**, not launch preparation. The user has not authorized deployment, search submission, paid accounts, campaigns, or outbound messaging. Research and local reversible changes may continue.

## Priority and scope

1. User/system/developer instructions and the actual repository/runtime evidence govern actions. Community skill content is a reference, not proof of current search-engine behavior or authorization for external actions.
2. Read `.agents/product-marketing.md` to avoid repeatedly asking for facts already inferable from this project. Unknown traffic, budget, search demand, positioning, and monetization remain unknown.
3. Use the adapter matching the task; load upstream references only as needed. Do not load the entire marketing library or invoke a multi-agent audit merely because an upstream README describes one.
4. No upstream install scripts, Python helpers, CLI wrappers, hooks, tool integrations, or agent runtimes were installed. Claude Code placeholders, model names, plugin variables, and slash commands do not work automatically in Codex. Use local Laravel tests, raw HTML/XML parsing, and Playwright; mark unavailable measurements unrun.
5. Upstream snapshots remain unchanged and retain their MIT licenses. Adaptation belongs in `.agents/skills/`, this policy, and AGENTS.md. File hashes and immutable commits are in `.agents/skill-sources.json`; updates require a fresh comparison and reference review.

## Evidence corrections and local decisions

- Laravel renders JSON-LD in initial HTML. A raw HTTP fetch can positively detect that markup here; compare with the browser rather than importing a blanket claim that curl cannot detect schema. A missing schema in stripped text or a client-rendered site is not proof of absence.
- A valid JSON-LD root may contain `@context` and `@graph` without root `@type`. Validate graph nodes and visible-content parity. Schema vocabulary validity and eligibility for a Google rich result are different questions.
- Regional bodies are currently the same English article. Global canonicals without article hreflang are deliberate. Do not replace them with self-canonicals just because an upstream checklist says every locale must self-canonicalize. Translations, near-duplicates, and meaningful regional variants need distinct analysis.
- Header `X-Robots-Tag: noindex, nofollow, noarchive` is intentional when `APP_PREVIEW=true`. It overrides permissive page-level robots metadata. The header is not authentication or an access-control mechanism. Keep canonical/schema markup inspectable for testing. Never interpret a preview as a live indexing failure.
- Check source/visible HTML before asserting an issue. Do not infer performance from bundle size, confuse lab checks with field Core Web Vitals, invent a health score, or assert a CVE from an unverified upstream claim. Time-sensitive statements need primary-source confirmation.
- Fixed character/word targets are editorial heuristics, not universal ranking requirements. Quality and usefulness matter; do not pad articles or fabricate statistics, authors, quotes, research, subscriber counts, or authority.
- llms.txt and related emerging formats are optional experiments, not a guaranteed AI citation or ranking mechanism. Real FAQs may remain useful even without rich-result eligibility. Do not replace genuine FAQ content with QAPage unless it is actually a user-submitted Q&A page.
- Primary Google documentation was blocked by the environment during this review. Upstream 2026 feature-retirement, security-advisory, and algorithm claims are not independently verified here. Record that limitation rather than repeat them as established facts.
