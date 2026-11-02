# ADR 001: native Laravel staff workspace for the first editorial slice

Status: accepted for the first milestone; revisit when the media/rich-editor requirements justify additional tooling.

The public site already uses Laravel 12, PHP 8.4, Blade and Tailwind. The first deliverable is an auditable, policy-tested author → super-admin → public-article workflow. We use Laravel's existing session guard, password broker, policies, validation, notifications, commands and Blade views instead of adding a general admin framework now. This keeps the permissions and revision/publication boundaries explicit and avoids introducing React or an untested package stack.

Evidence: authentication/authorization and CMS feature tests plus real browser staff journeys on the actual pinned framework. No third-party package-compatibility claim is needed because no new production dependency was introduced.

Tradeoff: we own the editor/team UI and must still implement media management, richer structured content, scheduling, autosave, author profiles and two-factor authentication. A maintained compatible package can be introduced for a well-scoped feature later, with its own compatibility and policy regression checks. This decision is not permission to weaken server-side policies or to treat the initial editor as a finished CMS.
