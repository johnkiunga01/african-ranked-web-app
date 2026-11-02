#!/usr/bin/env python3
"""Freeze review code while retaining the existing private review state.

Usage: python3 scripts/review-snapshot.py /absolute/new/snapshot/directory
Run the normal checks before building. This neither starts nor promotes a server.
"""
import pathlib
import shutil
import sys

source = pathlib.Path(__file__).resolve().parent.parent
destination = pathlib.Path(sys.argv[1]).resolve()
if destination.exists() or destination == source or source in destination.parents:
    raise SystemExit('Use a new directory outside the source checkout.')
if not (source / 'public/build/manifest.json').is_file():
    raise SystemExit('Build frontend assets before creating a snapshot.')
if (source / 'public/hot').exists() or (source / 'bootstrap/cache/config.php').exists():
    raise SystemExit('Remove stale Vite/config caches before creating a snapshot.')
destination.mkdir(parents=True, mode=0o700)
for name in ['app', 'config', 'content', 'database/migrations', 'resources', 'routes', 'vendor', 'public', 'scripts']:
    shutil.copytree(source / name, destination / name, ignore=shutil.ignore_patterns('.git', '__pycache__'))
for name in ['artisan', 'composer.json', 'composer.lock', 'package.json', 'bootstrap/app.php', 'bootstrap/providers.php', 'docs/CONTENT-REGISTER.csv']:
    target = destination / name
    target.parent.mkdir(parents=True, exist_ok=True)
    shutil.copy2(source / name, target)
(destination / 'bootstrap/cache').mkdir(parents=True, exist_ok=True)
# Share state, never duplicate or replace the database, encryption key or sessions.
(destination / 'storage').symlink_to(source / 'storage', target_is_directory=True)
print(destination)
