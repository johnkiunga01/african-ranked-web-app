#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
if [ -x /workspace/toolchain/bin/php ]; then export PATH="/workspace/toolchain/bin:$PATH"; fi
if [ -f public/hot ] || [ -f bootstrap/cache/config.php ]; then
  echo 'Browser tests require production assets and uncached configuration. See docs/PREVIEW.md.' >&2
  exit 1
fi
browser_database=$(mktemp /tmp/african-ranked-browser-XXXXXX.sqlite)
browser_state_dir=$(mktemp -d /tmp/african-ranked-browser-state-XXXXXX)
mkdir -p "$browser_state_dir/cache" "$browser_state_dir/sessions"
preview_server_pid=''
cleanup() {
  if [ -n "$preview_server_pid" ]; then kill "$preview_server_pid" 2>/dev/null || true; wait "$preview_server_pid" 2>/dev/null || true; fi
  rm -f "$browser_database" "$browser_database-wal" "$browser_database-shm"
  rm -rf "$browser_state_dir"
}
trap cleanup EXIT
trap 'exit 0' INT TERM
export APP_ENV=local APP_DEBUG=false APP_PREVIEW=true APP_URL=http://127.0.0.1:8010
export DB_CONNECTION=sqlite DB_DATABASE="$browser_database" DB_URL=''
export CACHE_STORE=file CACHE_FILE_PATH="$browser_state_dir/cache" SESSION_DRIVER=file SESSION_FILES_PATH="$browser_state_dir/sessions" QUEUE_CONNECTION=sync MAIL_MAILER=log
export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
php artisan migrate --force --no-interaction >/dev/null
php tests/Browser/seed-staff.php
php artisan serve --host=127.0.0.1 --port=8010 --no-reload &
preview_server_pid=$!
wait "$preview_server_pid"
