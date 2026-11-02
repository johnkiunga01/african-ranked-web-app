# Continue in Codex on your desktop

This repository is the Laravel platform. The cloud chat cannot create a desktop app project or attach to your logged-in browser. Open a local checkout in Codex Desktop to run commands using your computer's network and authenticated tools. Desktop access remains governed by the permissions and connected tools you enable; opening the project does not grant access to all accounts or applications.

## Open the project

In Codex Desktop, clone `https://github.com/johnkiunga01/african-ranked-web-app.git` into a local folder you choose and open that folder as the project. Use branch `main`. Alternatively, in your terminal:

```sh
git clone https://github.com/johnkiunga01/african-ranked-web-app.git
cd african-ranked-web-app
```

Open this folder in Codex Desktop. If the folder already exists, check its Git status and preserve local changes before updating it. Do not replace an existing checkout blindly.

Grant the project access to its folder and the network. Connect the browser capability if your Codex app offers it; a desktop project alone does not connect existing browser tabs. Authenticate GitHub and Railway on your computer through their normal sign-in flows. Grant additional access when needed for a concrete operation rather than changing global filesystem permissions.

## First local task: finish Railway account setup

Paste the following into a new Codex thread in this local project:

> Read AGENTS.md, docs/RAILWAY-DEPLOYMENT.md and docs/LAUNCH-PLAN.md. Continue African Ranked's Laravel staging setup using my desktop network and authenticated Railway account. The repository main branch contains Laravel; Railway already has PostgreSQL, web, worker and scheduler services, stable APP_KEY, server credentials and noindex settings. Preserve them. The cloud environment could not reach Railway SSH/SFTP; finish that blocked step locally. Verify the existing Africa Ranked project (757421e4-670a-4465-a4ba-48cf0a54015b), environment production (e9584cd1-5734-4f4e-b9eb-f0e93eb163c8), and web service african-ranked-web-app (679eee19-1369-4f7f-a417-6f4d9cc9d55b). Despite its Railway environment name, this is staging: keep APP_ENV=staging and APP_PREVIEW=true. Check whether the owner account or an invitation already exists before creating anything. If needed, run php artisan staff:invite-admin johnkiunga01@gmail.com --name=John inside the web service. Retrieve the one-time setup URL from storage/app/private/super-admin-setup.url privately for me to open locally. Never commit it or put passwords, tokens, QR secrets or recovery codes in logs. Help me complete owner password setup and MFA, then verify author/admin permissions and the durable research-to-Gemini workflow with disposable content. Do not regenerate APP_KEY, reset existing passwords, copy the cloud review database, publish articles, change indexing, or launch production as part of account setup. Report the actual checks and remaining gaps.

The account command runs **inside the Railway service**, not against a new local SQLite database. The review password is not automatically present in Railway PostgreSQL.

If Railway CLI is missing, install the official CLI and sign in interactively:

```sh
npm install -g @railway/cli
railway login
railway link --project 757421e4-670a-4465-a4ba-48cf0a54015b --environment e9584cd1-5734-4f4e-b9eb-f0e93eb163c8 --service african-ranked-web-app
```

Railway SSH requires a local SSH key registered with your Railway account. Codex should inspect the CLI help and existing key availability, register an appropriate existing key or create a dedicated key with your approval, and keep the private key local. Do not send private keys or access tokens through chat. Once SSH works, the command is:

```sh
railway ssh --service african-ranked-web-app -- php artisan staff:invite-admin johnkiunga01@gmail.com --name=John
```

If a setup file already exists, inspect that invitation's state rather than overwriting it. The URL is a secret one-time capability and expires after 24 hours. Use the application's supported fresh private link-file option only when deliberately issuing a replacement.

## Continue development after account setup

Use PHP 8.4+, Composer, Node 22.12+ and npm for local development. Follow README.md for dependencies, the local environment and tests. Cloud-specific paths such as /workspace/toolchain/bin are not required on your desktop. Keep local development data separate from Railway. No cloud review secrets or databases are included in Git.

The next work is staged in docs/LAUNCH-PLAN.md: Railway persistence/backup and restore testing; deliberate transfer of curated review edits and research; admin/author acceptance; repair and expansion of the first 13 articles; then researched briefs and verified content toward 160 accepted articles. AI metadata improvements do not count as complete accepted articles.
