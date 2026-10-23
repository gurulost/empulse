#!/bin/sh
set -eu

php scripts/check-php-runtime.php >&2

if [ "${1:-web}" != web ]; then
    exec "$@"
fi

# No environment switch bypasses production validation for the default server.
php artisan app:production-check
php-fpm -t
nginx -e stderr -t

php-fpm -F &
php_pid=$!
nginx -e stderr -g 'daemon off;' &
nginx_pid=$!

stop_web() {
    trap '' INT TERM
    # Graceful shutdown: finish active requests, then reap both children.
    kill -QUIT "$php_pid" "$nginx_pid" 2>/dev/null || true
    wait "$php_pid" 2>/dev/null || true
    wait "$nginx_pid" 2>/dev/null || true
}
trap 'stop_web; exit 0' INT TERM

# BusyBox 1.37 applies -n only without PID operands. Supplying the two PIDs
# waits for them sequentially and masks nginx failure while FPM stays alive.
# These are the only two background jobs in this supervisor.
set +e
wait -n
child_status=$?
stop_web
if [ "$child_status" -eq 0 ]; then
    child_status=1
fi
exit "$child_status"
