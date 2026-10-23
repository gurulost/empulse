#!/bin/sh
set -eu

php scripts/check-php-runtime.php

if [ "${1:-web}" != web ]; then
    exec "$@"
fi

# No environment switch bypasses production validation for the default server.
php artisan app:production-check
php-fpm -t
nginx -t

php-fpm -F &
php_pid=$!
nginx -g 'daemon off;' &
nginx_pid=$!

stop_web() {
    trap '' INT TERM
    # Graceful shutdown: finish active requests, then reap both children.
    kill -QUIT "$php_pid" "$nginx_pid" 2>/dev/null || true
    wait "$php_pid" 2>/dev/null || true
    wait "$nginx_pid" 2>/dev/null || true
}
trap 'stop_web; exit 0' INT TERM

# BusyBox ash supports wait -n. Either child exiting makes the web container
# unhealthy as a whole, even if the child exited with status zero.
set +e
wait -n "$php_pid" "$nginx_pid"
child_status=$?
stop_web
if [ "$child_status" -eq 0 ]; then
    child_status=1
fi
exit "$child_status"
