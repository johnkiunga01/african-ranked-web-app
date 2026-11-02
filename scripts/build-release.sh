#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
if [ -x /workspace/toolchain/bin/php ]; then export PATH="/workspace/toolchain/bin:$PATH"; fi
if [ ! -f public/build/manifest.json ] || [ -f public/hot ]; then
  echo 'Build production assets and stop the Vite hot server before packaging.' >&2
  exit 1
fi
release_parent=$(mktemp -d /tmp/african-ranked-release-XXXXXX)
trap 'rm -rf "$release_parent"' EXIT
release_tree="$release_parent/app"
mkdir -p "$release_tree" artifacts/releases
cp -R app config content public resources routes "$release_tree/"
cp deploy/apache-build-assets.htaccess "$release_tree/public/build/assets/.htaccess"
cp artisan composer.json composer.lock "$release_tree/"
mkdir -p "$release_tree/database" "$release_tree/bootstrap/cache" "$release_tree/docs"
cp -R database/migrations database/seeders "$release_tree/database/"
cp bootstrap/app.php bootstrap/providers.php "$release_tree/bootstrap/"
cp docs/CONTENT-REGISTER.csv "$release_tree/docs/"
mkdir -p "$release_tree/storage/app/private" "$release_tree/storage/app/public" "$release_tree/storage/framework/cache/data" "$release_tree/storage/framework/sessions" "$release_tree/storage/framework/views" "$release_tree/storage/logs"
# Never ship a development storage symlink or private runtime state.
rm -rf "$release_tree/public/storage"
cp deploy/cpanel.env.example "$release_tree/.env.example"
release_source_commit=$(git rev-parse HEAD)
release_dirty=false
if [ -n "$(git status --porcelain)" ]; then release_dirty=true; fi
export RELEASE_SOURCE_COMMIT="$release_source_commit" RELEASE_DIRTY="$release_dirty"
php -r 'file_put_contents($argv[1], json_encode(["built_at_utc"=>gmdate("c"),"source_commit"=>getenv("RELEASE_SOURCE_COMMIT"),"working_tree_dirty"=>getenv("RELEASE_DIRTY")==="true","purpose"=>"Reviewable cPanel runtime artifact; not an approved production release"], JSON_PRETTY_PRINT).PHP_EOL);' "$release_tree/release-build.json"
(
  cd "$release_tree"
  composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader > "$release_parent/composer.log" 2>&1 || { cat "$release_parent/composer.log" >&2; exit 1; }
)
release_name="african-ranked-cpanel-$(date -u +%Y%m%d-%H%M%S).tar.gz"
tar --exclude-vcs --exclude='.env' --exclude='*.sqlite' --exclude='*.sqlite-*' -czf "artifacts/releases/$release_name" -C "$release_tree" .
sha256sum "artifacts/releases/$release_name" > "artifacts/releases/$release_name.sha256"
printf 'Runtime artifact prepared: artifacts/releases/%s\nNo environment, database, provider credentials or private storage was copied.\n' "$release_name"
