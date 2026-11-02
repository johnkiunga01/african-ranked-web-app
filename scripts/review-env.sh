#!/usr/bin/env bash
# Source from the project root. Values stay process-local; .env is not rewritten.
if [ -x /workspace/toolchain/bin/php ]; then export PATH="/workspace/toolchain/bin:$PATH"; fi
review_state_dir="$PWD/storage/framework/review"
mkdir -p "$review_state_dir"
chmod 700 "$review_state_dir"
export REVIEW_KEY_FILE="$review_state_dir/application.key"
export APP_ENV=review APP_DEBUG=false APP_PREVIEW=true APP_REVIEW=true
export APP_URL="${REVIEW_URL:-http://localhost:8002}"
export ASSET_URL="$APP_URL"
export SESSION_COOKIE=african_ranked_review SESSION_DRIVER=file QUEUE_CONNECTION=sync CACHE_STORE=file
export DB_CONNECTION=sqlite DB_DATABASE="$review_state_dir/review.sqlite" DB_URL=''
export MAIL_MAILER=log
export SESSION_ENCRYPT=true
export APP_KEY="$(php -r '$file = getenv("REVIEW_KEY_FILE"); if (!is_file($file)) { file_put_contents($file, "base64:".base64_encode(random_bytes(32)), LOCK_EX); chmod($file, 0600); } echo file_get_contents($file);')"
case "$APP_URL" in https://*) export SESSION_SECURE_COOKIE=true ;; *) export SESSION_SECURE_COOKIE=false ;; esac
if [ ! -f "$DB_DATABASE" ]; then touch "$DB_DATABASE"; chmod 600 "$DB_DATABASE"; fi

export CACHE_FILE_PATH="$review_state_dir/cache" SESSION_FILES_PATH="$review_state_dir/sessions"
mkdir -p "$CACHE_FILE_PATH" "$SESSION_FILES_PATH"
chmod 700 "$CACHE_FILE_PATH" "$SESSION_FILES_PATH"
