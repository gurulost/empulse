release: sh -lc 'php artisan migrate --force && if [ "${SEED_DEMO_ON_RELEASE:-false}" = "true" ]; then php artisan demo:seed --import-instrument --employees=${DEMO_SEED_EMPLOYEES:-120} --months=${DEMO_SEED_MONTHS:-6} --force --if-empty; fi'
web: php artisan serve --host=0.0.0.0 --port=${PORT:-5000}
