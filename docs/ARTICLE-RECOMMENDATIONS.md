# Article improvements

Authors save their article and choose **Improve article** (or **Suggest improvements** in the sidebar). The application uses the saved editorial plan, reuses current OpenSEO research or obtains it, and prepares a private suggestion using the configured AI provider. Authors do not choose a provider or write a separate prompt.

The review screen lists changed sections, shows the suggested article and lets the author expand the original for comparison. **Accept and publish** requires human confirmation of facts, sources, ranking and image permissions. Authors may publish their own accepted suggestions; administrators may manage all articles. The existing standalone publication endpoints remain administrator-only.

Unresolved evidence, content or photo checks block publication. **Continue editing this version** adopts the suggestion as a private draft. **Keep my original** discards it. Generating a suggestion never changes the saved or live revision. Acceptance creates a new revision, retains history and preserves the first publication date.

The queue reserves the existing AI allowance, deduplicates requests per saved revision, and makes no automatic provider retry. Changing the source, ownership, permissions or editorial plan invalidates acceptance. Search snippets are research leads, not factual verification. No score or ranking improvement is promised.

No database migration or new service is required. Web and queue workers must run the same release. Provider credentials remain server-side. Browser fixtures run only in the existing guarded disposable test database.

Validation: 154 backend tests passed locally, including 22 recommendation tests. JavaScript syntax and production build passed. The focused browser test covers original comparison, mobile overflow and author acceptance. The full inherited browser suite has unrelated interface-selector failures and is not green; see pull request validation notes before release.
