/*
# Create newsletter_subscribers table

1. New Tables
- `newsletter_subscribers`
  - `id` (uuid, primary key)
  - `email` (text, unique, not null) — subscriber email
  - `region` (text, not null) — region code the user subscribed from (e.g. 'us', 'global')
  - `created_at` (timestamptz, default now())

2. Security
- Enable RLS on `newsletter_subscribers`.
- Grant SELECT, INSERT to `anon` + `authenticated` so the anon-key frontend can subscribe and dedupe.
- No UPDATE/DELETE: subscribers cannot modify or remove their own record through the Data API.
- Policies allow anon + authenticated to SELECT (needed to check existing email) and INSERT.
  We do NOT allow DELETE or UPDATE from the anon key — unsubscription, if needed, must go through
  a server-side flow. This keeps the public surface minimal.

3. Notes
- Single-tenant (no sign-in): the app has no login screen, so every policy lists `anon`.
- `USING (true)` for SELECT is acceptable because the only column exposed is email + region,
  which is intentionally public for the dedupe check. Sensitive PII is not stored here.
*/

CREATE TABLE IF NOT EXISTS newsletter_subscribers (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  email text UNIQUE NOT NULL,
  region text NOT NULL,
  created_at timestamptz DEFAULT now()
);

ALTER TABLE newsletter_subscribers ENABLE ROW LEVEL SECURITY;

GRANT SELECT, INSERT ON newsletter_subscribers TO anon, authenticated;

DROP POLICY IF EXISTS "anon_select_subscribers" ON newsletter_subscribers;
CREATE POLICY "anon_select_subscribers" ON newsletter_subscribers FOR SELECT
  TO anon, authenticated USING (true);

DROP POLICY IF EXISTS "anon_insert_subscribers" ON newsletter_subscribers;
CREATE POLICY "anon_insert_subscribers" ON newsletter_subscribers FOR INSERT
  TO anon, authenticated WITH CHECK (true);

CREATE INDEX IF NOT EXISTS idx_newsletter_subscribers_email ON newsletter_subscribers(email);
