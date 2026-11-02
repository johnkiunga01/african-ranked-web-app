# syntax=docker/dockerfile:1
FROM node:22-bookworm-slim AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
COPY deploy/railway/build-network.sh /tmp/ar-build-network.sh
RUN . /tmp/ar-build-network.sh && npm ci && rm -f /tmp/african-ranked-build-ca.crt
COPY resources ./resources
COPY public ./public
COPY vite.config.js tailwind.config.js postcss.config.js ./
RUN npm run build

FROM php:8.4-fpm-bookworm AS php-base
COPY deploy/railway/build-network.sh /usr/local/lib/ar-build-network.sh
RUN . /usr/local/lib/ar-build-network.sh \
    && sed -i 's|http://deb.debian.org|https://deb.debian.org|g' /etc/apt/sources.list.d/debian.sources \
    && apt-get -o Acquire::https::CaInfo="$AR_BUILD_CA_FILE" update --error-on=any && apt-get -o Acquire::https::CaInfo="$AR_BUILD_CA_FILE" install -y --no-install-recommends \
    ca-certificates curl git gosu gettext-base nginx supervisor unzip \
    libicu-dev libpq-dev libzip-dev \
    && docker-php-ext-install -j"$(nproc)" intl pdo_mysql pdo_pgsql pcntl zip opcache \
    && rm -rf /var/lib/apt/lists/* && rm -f /tmp/african-ranked-build-ca.crt
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /app

FROM php-base AS application
COPY composer.json composer.lock artisan ./
COPY app ./app
COPY config ./config
COPY bootstrap/app.php bootstrap/providers.php ./bootstrap/
COPY routes ./routes
COPY resources ./resources
COPY database/migrations ./database/migrations
COPY database/seeders ./database/seeders
COPY content ./content
COPY docs/CONTENT-REGISTER.csv ./docs/CONTENT-REGISTER.csv
COPY public ./public
COPY --from=frontend /app/public/build ./public/build
RUN mkdir -p bootstrap/cache storage/app/private storage/app/public storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs

# Disposable DB validation target. Never deployed as the production image.
FROM application AS testing
COPY tests ./tests
COPY phpunit.xml ./phpunit.xml
RUN . /usr/local/lib/ar-build-network.sh && composer install --no-interaction --prefer-dist --optimize-autoloader && rm -f /tmp/african-ranked-build-ca.crt
CMD ["php", "artisan", "test"]

FROM application AS runtime
RUN . /usr/local/lib/ar-build-network.sh && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader && rm -f /tmp/african-ranked-build-ca.crt \
    && chmod -R a+rX /app && chown -R www-data:www-data storage bootstrap/cache \
    && chmod 700 storage/app/private
COPY deploy/railway/nginx.conf.template /etc/african-ranked/nginx.conf.template
COPY deploy/railway/supervisord.conf /etc/african-ranked/supervisord.conf
COPY deploy/railway/php.ini /usr/local/etc/php/conf.d/production.ini
COPY deploy/railway/entrypoint.sh /usr/local/bin/african-ranked-entrypoint
RUN chmod 755 /usr/local/bin/african-ranked-entrypoint \
    && chmod 644 /etc/african-ranked/nginx.conf.template /etc/african-ranked/supervisord.conf /usr/local/etc/php/conf.d/production.ini
ENV APP_ROLE=web PORT=8080
EXPOSE 8080
ENTRYPOINT ["african-ranked-entrypoint"]
CMD []
