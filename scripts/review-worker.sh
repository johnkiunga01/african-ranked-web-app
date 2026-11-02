#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
source scripts/review-env.sh
exec php artisan queue:work database --sleep=2 --timeout=120 --tries=1
