#!/bin/sh
set -eu
cd /app
umask 027
if [ -z "${APP_KEY:-}" ]; then
    echo 'APP_KEY must be configured explicitly; it is never generated during container startup.' >&2
    exit 1
fi
mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
# A new mounted storage volume starts empty. Keep private files outside the web root.
chown www-data:www-data storage storage/app storage/app/private storage/app/public storage/framework storage/framework/cache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
# Config is generated at runtime, after Railway injects the shared server variables.
gosu www-data php artisan config:cache
if [ "$#" -gt 0 ]; then
    exec gosu www-data "$@"
fi
case "${APP_ROLE:-web}" in
    web)
        case "${PORT:-8080}" in ''|*[!0-9]*) echo 'PORT must be numeric.' >&2; exit 1 ;; esac
        if [ "$PORT" -lt 1024 ] || [ "$PORT" -gt 65535 ]; then echo 'Use an unprivileged PORT between 1024 and 65535.' >&2; exit 1; fi
        PHP_HTTPS=off
        case "${APP_URL:-}" in https://*) PHP_HTTPS=on ;; esac
        export PHP_HTTPS PORT
        envsubst '${PORT} ${PHP_HTTPS}' < /etc/african-ranked/nginx.conf.template > /tmp/african-ranked-nginx.conf
        exec supervisord -n -c /etc/african-ranked/supervisord.conf
        ;;
    worker)
        exec gosu www-data php artisan queue:work database --sleep=2 --timeout=120 --tries=1
        ;;
    scheduler)
        exec gosu www-data php artisan schedule:work
        ;;
    *) echo 'APP_ROLE must be web, worker or scheduler.' >&2; exit 1 ;;
esac
