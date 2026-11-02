#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
if [ -x /workspace/toolchain/bin/php ]; then export PATH="/workspace/toolchain/bin:$PATH"; fi
if [ ! -f vendor/autoload.php ] || [ ! -f public/build/manifest.json ]; then
  echo 'Install dependencies and run npm run build before starting review.' >&2
  exit 1
fi
if [ -f public/hot ] || [ -f bootstrap/cache/config.php ]; then
  echo 'Review requires production assets and uncached configuration. Stop Vite/remove its stale hot file and clear Laravel configuration first.' >&2
  exit 1
fi
source scripts/review-env.sh
php artisan migrate --force --no-interaction
php artisan content:sync --no-interaction
php artisan content:optimize-onsite --no-interaction
printf '%s\n' 'Starting review with isolated test data, noindex headers, and debug output disabled.'
exec php artisan serve --host=0.0.0.0 --port="${REVIEW_PORT:-8002}" --no-reload
