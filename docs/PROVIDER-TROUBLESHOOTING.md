# Editorial provider checks

Run in the deployed web container and worker container, using the same release:

```
php artisan editorial:check-providers
php artisan editorial:check-providers --live
```

The first command makes no network requests. It reports whether effective credentials are configured, whether environment values take precedence over secure storage, the configured project/market/model and whether Laravel configuration is cached. It never prints keys. An old environment credential takes precedence over a replacement saved through the staff UI.

`--live` makes free OpenSEO handshake/account/project reads and one small Gemini structured-generation request, which may use provider quota. It sends no article, creates no revision, publishes nothing, schedules no job and makes no automatic retry. Both providers are checked independently. A nonzero exit means at least one check failed. Successful connection probes do not establish that a paid research call or full article generation will succeed.

Check web and worker results separately. Do not dump environment variables or provider error bodies. Do not rotate credentials speculatively. If environment configuration is changed, redeploy all affected services and rerun the checks before requesting a new research job. The old failed job remains an accurate historical failure.

## Observed 3 November 2026

Railway web, worker, scheduler and database were online. Both application services listed the required Gemini/OpenSEO variable names; OAuth withheld their values. Worker logs confirm ResearchArticleSeo ran for approximately 20 seconds; its exception was handled, so the queue's DONE line does not mean the research succeeded. No application-side failure reason was retained by the original catch blocks.

The connected OpenSEO account exposed the Africa Ranked project, reported 55 credits before the focused research probe, returned no metrics for “African Cultural Festivals by Month” and returned ten live search results for the query. These checks used the agent connection, not the application's credential. They do not establish that Railway's credential, project or Gemini model works.

This change preserves sanitized OpenSEO failure categories through the research job, resets the MCP session before a new handshake, and adds independent application-side diagnostics. It does not declare the original incident resolved without a passing deployed probe and a successful private research/generation request.
