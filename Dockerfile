FROM php:8.5.12-fpm-alpine3.24@sha256:f09d9172dd28b8696aef729ca2c2081a132c1f8d581218c99497d552c82a92df AS php-base

RUN set -eux; \
    apk add --no-cache ca-certificates curl; \
    apk add --no-cache --virtual .build-deps \
      $PHPIZE_DEPS pax-utils freetype-dev icu-dev libjpeg-turbo-dev libpng-dev libpq-dev libzip-dev; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install bcmath gd intl pcntl pdo_pgsql zip; \
    run_deps="$(scanelf --needed --nobanner --format '%n#p' --recursive /usr/local \
      | tr ',' '\n' | sort -u \
      | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }')"; \
    apk add --no-cache $run_deps; \
    apk del --no-network .build-deps; \
    php -r 'foreach (["bcmath", "curl", "dom", "fileinfo", "gd", "intl", "mbstring", "openssl", "pcntl", "pdo_pgsql", "zip"] as $extension) { if (!extension_loaded($extension)) throw new RuntimeException("Missing extension: ".$extension); }'

FROM php-base AS vendor

RUN apk add --no-cache git unzip

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

FROM node:22.23.4-alpine3.24@sha256:fe4f6d74e20370acb04ad889077d14d0f64a9c8ea6a42bc60cddf1e844699914 AS frontend

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

FROM php-base

RUN apk add --no-cache nginx tini

WORKDIR /var/www/html

ENV APP_ENV=production
ENV APP_DEBUG=false

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/99-empulse-opcache.ini
COPY docker/php/fpm.conf /usr/local/etc/php-fpm.d/zzz-empulse.conf
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/entrypoint.sh /usr/local/bin/empulse-entrypoint
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

RUN mkdir -p storage/app/public storage/app/private storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && ln -s ../storage/app/public public/storage \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod 755 /usr/local/bin/empulse-entrypoint

USER www-data

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl --fail --silent --header "Host: $(php -r 'echo parse_url(getenv("APP_URL"), PHP_URL_HOST);')" \
        http://127.0.0.1:8080/api/healthz > /dev/null || exit 1

ENTRYPOINT ["/sbin/tini", "--", "/usr/local/bin/empulse-entrypoint"]
CMD ["web"]
