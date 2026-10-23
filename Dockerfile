FROM php:8.2.35-apache@sha256:fa8966fbaaa06981e549e7a33eb8a68f4cf677d6a82e1d6350adc5d85fe8f740 AS php-base

RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates curl git unzip libfreetype6-dev libicu-dev libjpeg62-turbo-dev \
    libpng-dev libpq-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install bcmath gd intl opcache pcntl pdo_pgsql zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

FROM php-base AS vendor

# Copy only Composer's executable; dependency resolution uses patched PHP and
# the refreshed OS trust store, not the Composer image's runtime.
COPY --from=composer:2.9.3@sha256:c404e6f07bdebf8a8c605be5b5fab88eef737f6e4acfba3651d39c824ce224d4 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader

COPY . .
RUN mkdir -p storage/framework/cache/data storage/framework/sessions \
    storage/framework/views storage/logs bootstrap/cache \
    && composer dump-autoload --no-dev --optimize

FROM node:22.23.4-bookworm-slim@sha256:de170b39439eb0e5a3444ad7d60b015614a381e10fecae2e30ec9e735122f38d AS frontend

WORKDIR /app

COPY scripts/check-node-runtime.mjs scripts/install-build-npm.mjs ./scripts/
RUN node scripts/install-build-npm.mjs /opt/empulse-build-npm \
    && rm -rf /usr/local/lib/node_modules/npm \
    && rm -f /usr/local/bin/npm /usr/local/bin/npx
ENV PATH="/opt/empulse-build-npm/bin:${PATH}"

COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
RUN npm run build

FROM php-base AS php-runtime

# Keep Composer's git/unzip and extension compilers in build stages only.
# PHP is not managed by dpkg: retain its linked libraries explicitly before
# apt removes development dependencies (same approach as the official image).
RUN set -eux; \
    find /usr/local -type f -executable -exec ldd '{}' ';' 2>/dev/null \
      | awk '/=>/ { so = $(NF-1); if (index(so, "/usr/local/") == 1) { next }; gsub("^/(usr/)?", "", so); printf "*%s\n", so }' \
      | sort -u \
      | xargs -rt dpkg-query --search \
      | awk 'sub(":$", "", $1) { print $1 }' \
      | sort -u \
      | xargs -r apt-mark manual; \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false \
      $PHPIZE_DEPS git unzip libfreetype6-dev libicu-dev libjpeg62-turbo-dev \
      libpng-dev libpq-dev libzip-dev; \
    rm -rf /var/lib/apt/lists/*; \
    if command -v cc >/dev/null || dpkg-query -s linux-libc-dev >/dev/null 2>&1; then \
      echo 'Build-only compiler/kernel headers remain in the runtime image.' >&2; exit 1; \
    fi; \
    php -r 'foreach (["bcmath", "curl", "dom", "fileinfo", "gd", "intl", "mbstring", "openssl", "pcntl", "pdo_pgsql", "zip"] as $extension) { if (!extension_loaded($extension)) { throw new RuntimeException("Missing required extension: ".$extension); } }'

FROM php-runtime

WORKDIR /var/www/html

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/99-empulse-opcache.ini
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

RUN mkdir -p storage/app/public storage/app/private storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && ln -s ../storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl --fail --silent --header "Host: $(php -r 'echo parse_url(getenv("APP_URL"), PHP_URL_HOST);')" \
        http://127.0.0.1/api/healthz > /dev/null || exit 1

CMD ["sh", "-lc", "php scripts/check-php-runtime.php && php artisan app:production-check && exec apache2-foreground"]
