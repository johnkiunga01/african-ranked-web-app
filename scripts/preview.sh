#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
if [ -x /workspace/toolchain/bin/php ]; then export PATH="/workspace/toolchain/bin:$PATH"; fi
if [ ! -f vendor/autoload.php ] || [ ! -f public/build/manifest.json ] || [ ! -f .env ]; then
  echo 'Initialize the app using README.md, then run npm run build before previewing.' >&2
  exit 1
fi
if [ -f public/hot ]; then
  echo 'A Vite hot file is present. Stop your Vite server and remove a stale public/hot file before production-build preview.' >&2
  exit 1
fi
if [ -f bootstrap/cache/config.php ]; then
  echo 'Clear cached configuration with php artisan config:clear before previewing so the noindex setting takes effect.' >&2
  exit 1
fi
export APP_PREVIEW=true
printf '%s\n' 'Starting a development preview with noindex response headers. Newsletter requests are stored locally; email is not sent.'
exec php artisan serve --host=0.0.0.0 --port="${PREVIEW_PORT:-8000}" --no-reload
