# syntax=docker/dockerfile:1

# ── app: PHP extensions + composer deps, optimized ─────────────────────
# Debian (glibc) base, not alpine: turso/libsql ships only *-linux-gnu
# native libraries (loaded via ext-ffi), which cannot run on musl.
FROM dunglas/frankenphp:1.12.7-php8.4-bookworm AS app

RUN install-php-extensions ffi intl bcmath pcntl posix zip opcache exif gd

# ffi.enable=1: the libSQL client (turso/libsql) loads its native library via FFI.
RUN printf '%s\n' 'ffi.enable=1' > /usr/local/etc/php/conf.d/99-ffi.ini

ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /app

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Install deps first for a stable layer cache. Build context is the repo
# root (not app/) so composer path repositories under ../packages resolve.
COPY app/composer.json app/composer.lock ./
COPY packages /packages
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist

COPY app/ .
RUN mkdir -p storage/framework/views \
              storage/framework/cache/data \
              storage/framework/sessions \
              storage/app/public \
              storage/logs \
    && composer run-script post-autoload-dump \
    && cp .env.example .env \
    && php artisan key:generate \
    && php artisan view:cache \
    && rm .env /usr/local/bin/composer \
    # The runtime user (uid 1000) must be able to write storage and cache;
    # named volumes are seeded from these directories on first use.
    && chown -R 1000:1000 storage bootstrap/cache

# ── dev: dev dependencies + composer kept, used by docker-compose.dev.yml
# Source is bind-mounted over /app at runtime; named volume composer_vendor
# on /app/vendor is empty until the entrypoint runs composer install.
FROM app AS dev
# The app stage removed composer (production hygiene); bring it back for dev.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/dev-entrypoint.sh /usr/local/bin/dev-entrypoint.sh
RUN chmod +x /usr/local/bin/dev-entrypoint.sh \
    && composer install --no-interaction --prefer-dist
ENTRYPOINT ["/usr/local/bin/dev-entrypoint.sh"]

# ── assets: build the frontend ─────────────────────────────────────────
FROM node:22-alpine AS assets
WORKDIR /app
COPY app/.npmrc app/package.json app/package-lock.json ./
RUN npm ci
COPY app/ .
RUN npm run build

# ── runtime: slim, non-root, no capabilities ───────────────────────────
FROM app AS runtime

# HTTP only on an unprivileged port; TLS is terminated upstream.
ENV SERVER_NAME=:8080 \
    SERVER_ROOT=/app/public \
    APP_ENV=production \
    APP_DEBUG=false

WORKDIR /app

# Non-root with no capabilities (port 8080 is unprivileged, so none needed).
RUN useradd --uid 1000 --system appuser \
    && setcap -r /usr/local/bin/frankenphp \
    && chown -R appuser:appuser /config/caddy /data/caddy

# Code is baked into this immutable image and never changes at runtime,
# so opcache can skip re-statting files on every request.
RUN printf '%s\n' \
    'opcache.enable=1' \
    'opcache.enable_cli=1' \
    'opcache.validate_timestamps=0' \
    'opcache.interned_strings_buffer=16' \
    'opcache.max_accelerated_files=20000' \
    'opcache.memory_consumption=128' \
    > /usr/local/etc/php/conf.d/99-opcache.ini

COPY --from=assets --chown=appuser:appuser /app/public/build /app/public/build

USER appuser
EXPOSE 8080

HEALTHCHECK --interval=10s --timeout=3s --start-period=20s --retries=3 \
    CMD curl -fsS -o /dev/null http://127.0.0.1:8080/up || exit 1
