release: sh -lc 'php scripts/check-php-runtime.php && php artisan app:production-check && php artisan migrate --force'
web: sh -lc 'php scripts/check-php-runtime.php && exec heroku-php-apache2 public/'
worker: sh -lc 'php scripts/check-php-runtime.php && php artisan app:production-check && php artisan queue:work --tries=3 --backoff=10 --sleep=1 --timeout=120 --max-time=3600'
scheduler: sh -lc 'php scripts/check-php-runtime.php && php artisan app:production-check && php artisan schedule:work'
