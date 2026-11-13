# Newsletter lifecycle — implementation and launch gates

The current SMTP connection handles transactional mail. It does **not** prove campaign/newsletter delivery. New features are disabled by default under NEWSLETTER_SIGNUP_ENABLED=false and require verified site privacy policy.

Implementation:
- Signup stores normalized email + edition preference and consent timestamp, requires explicit agreement, honeypot and throttling.
- A confirmation email contains a one-use token (only its SHA-256 digest is stored) expiring after 24 hours. Repeated requests are cooled down 15 minutes per email.
- Subscription becomes verified only on explicit POST from confirmation page, not from opening the link.
- Signed unsubscribe links can be generated for newsletter campaigns using URL::signedRoute('newsletter.unsubscribe', ['subscriber' => $id]). Unsubscribe requires POST and cancels verification; repeated form requests do not reactivate.
- Newsletter signup is disabled by default. Do not claim an outbound bulk newsletter system, campaign scheduling or tested sender deliverability from these features.
- Current lifecycle pages do not use public React/Blade design components deliberately; they are private noindex verification/opt-out utilities. Customize once the browser tests pass.

Before enabling:
1. Approve and publish /pages/privacy-policy and a valid privacy contact email.
2. Verify the configured mail transport can send the opt-in confirmation using an authorized test recipient.
3. Validate link expiration, double opt-in, unsubscribe and no re-mail to opted-out addresses. Include unsubscribe URL in every future campaign email.
4. Enforce unsubscribe suppression in all future campaign senders; never send to nonverified or unsubscribed records.
5. Determine lawful processing, retention and deletion practices, and consider a formal email vendor with bounce management and unsubscribe compliance.
6. Re-run SQLite/PostgreSQL, staff/public browser and delivery smoke tests.

Do not set NEWSLETTER_SIGNUP_ENABLED=true until these operations are verified and the owner approves the public policy.
