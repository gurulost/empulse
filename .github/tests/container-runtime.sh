#!/usr/bin/env bash
set -euo pipefail

[[ "${GITHUB_ACTIONS:-}" == true ]] || { echo 'This disposable fixture is CI-only.' >&2; exit 1; }
fixture_dir="$(mktemp -d "${RUNNER_TEMP:?}/empulse-runtime.XXXXXX")"
fixture_id="${GITHUB_RUN_ID:?}-${GITHUB_RUN_ATTEMPT:-1}"
network="empulse-smoke-${fixture_id}"
database="empulse-smoke-db-${fixture_id}"
web="empulse-smoke-web-${fixture_id}"

cleanup() {
    local fixture_status=$?
    if [[ "$fixture_status" != 0 ]]; then
        docker logs "$web" > "$RUNNER_TEMP/empulse-runtime-web.log" 2>&1 || true
        docker logs "$database" > "$RUNNER_TEMP/empulse-runtime-postgres.log" 2>&1 || true
    fi
    # Only the named containers/anonymous volumes created by this fixture.
    docker rm -fv "$web" "$database" >/dev/null 2>&1 || true
    docker network rm "$network" >/dev/null 2>&1 || true
    return "$fixture_status"
}
trap cleanup EXIT

docker network create --internal "$network" >/dev/null
[[ "$(docker image inspect --format '{{.Config.StopSignal}}' empulse:audit)" == SIGTERM ]]
docker run -d --name "$database" --network "$network" \
    -e POSTGRES_DB=empulse_smoke -e POSTGRES_USER=empulse \
    -e POSTGRES_PASSWORD=empulse_ci_fixture empulse-postgres:audit >/dev/null
for attempt in {1..40}; do
    docker exec -e PGPASSWORD=empulse_ci_fixture "$database" \
        psql -h 127.0.0.1 -U empulse -d empulse_smoke -Atc 'select 1' >/dev/null 2>&1 && break
    sleep 1
done
docker exec -e PGPASSWORD=empulse_ci_fixture "$database" \
    psql -h 127.0.0.1 -U empulse -d empulse_smoke -Atc 'select 1'
[[ "$(docker exec "$database" id -u)" != 0 ]]
docker exec "$database" sh -c 'test ! -e /usr/local/bin/gosu'
echo 'Non-root PostgreSQL fixture accepts authenticated TCP queries.'

# Never-issued fixtures only, no inherited provider credentials. The internal
# network also prevents SMTP/Brevo/Stripe connections outside this test estate.
env_file="${fixture_dir}/runtime.env"
umask 077
cat > "$env_file" <<EOF
APP_ENV=production
APP_DEBUG=false
APP_NAME=Empulse
APP_URL=https://empulse-ci.test
APP_KEY=base64:$(openssl rand -base64 32)
AUDIT_HASH_KEY=$(openssl rand -hex 32)
APP_RELEASE_SHA=${GITHUB_SHA:?}
DEPLOYMENT_ENVIRONMENT_ID=ci-runtime-${fixture_id}
DB_CONNECTION=pgsql
DB_HOST=${database}
DB_PORT=5432
DB_DATABASE=empulse_smoke
DB_USERNAME=empulse
DB_PASSWORD=empulse_ci_fixture
CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
AVATAR_DISK=public
REQUIRE_PROCESS_HEARTBEATS=true
HEARTBEAT_MAX_AGE_SECONDS=180
LOG_CHANNEL=stderr
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1
MAIL_FROM_ADDRESS=ci@example.invalid
BREVO_KEY=ci
BREVO_WEBHOOK_TOKEN=$(openssl rand -hex 32)
STRIPE_KEY=ci
STRIPE_SECRET=ci
STRIPE_WEBHOOK_SECRET=ci
STRIPE_PRICE_STARTER=price_ci_starter
STRIPE_PRICE_PULSE=price_ci_pulse
BILLING_PRICE_STARTER_CENTS=9900
BILLING_PRICE_PULSE_CENTS=19900
EOF

docker run --rm --network "$network" --env-file "$env_file" \
    empulse:audit php artisan migrate --force >/dev/null

# The default web command must reject missing production configuration.
if docker run --rm --network "$network" empulse:audit web > "${fixture_dir}/rejected-start.log" 2>&1; then
    echo 'Unconfigured default web command unexpectedly succeeded.' >&2
    exit 1
fi
echo 'Default production web command rejects missing configuration.'

http() {
    docker exec "$web" curl --max-time 3 -H 'Host: empulse-ci.test' "$@"
}

start_web() {
    docker run -d --name "$web" --network "$network" --env-file "$env_file" \
        empulse:audit >/dev/null
    for attempt in {1..40}; do
        http --fail --silent -i http://127.0.0.1:8080/api/healthz \
            > "${fixture_dir}/health.http" 2>/dev/null && break
        sleep 1
    done
    http --fail --silent --show-error -i http://127.0.0.1:8080/api/healthz > "${fixture_dir}/health.http"
    grep -qi "X-Empulse-Release: ${GITHUB_SHA}" "${fixture_dir}/health.http"
    [[ "$(docker exec "$web" id -u)" != 0 ]]
    docker exec "$web" php scripts/check-php-runtime.php
    docker exec "$web" sh -c '! command -v cc && ! apk info -e linux-headers'
    echo 'Guarded non-root web startup and HTTP release identity passed.'
}

start_web
[[ "$(http --silent -o /dev/null -w '%{http_code}' http://127.0.0.1:8080/.env)" == 403 ]]
[[ "$(http --silent -o /dev/null -w '%{http_code}' http://127.0.0.1:8080/storage/untrusted.php)" == 404 ]]
canary="ci-sensitive-route-${fixture_id}"
http --silent -o /dev/null "http://127.0.0.1:8080/survey/${canary}?token=${canary}"
docker logs "$web" > "${fixture_dir}/web-output.log" 2>&1
if grep -Fq "$canary" "${fixture_dir}/web-output.log"; then
    echo 'Request token canary appeared in server logs.' >&2
    exit 1
fi
echo 'PHP/dotfile routing and request-log privacy checks passed.'

# A child exiting must terminate the whole container, not leave half a server.
docker exec "$web" nginx -e stderr -s quit
exit_status="$(timeout 20 docker wait "$web")"
[[ "$exit_status" != 0 ]]
echo 'nginx child failure terminates the whole web container.'
docker rm -v "$web" >/dev/null

start_web
docker exec "$web" sh -c 'read php_pid < /tmp/php-fpm.pid; kill -TERM "$php_pid"'
exit_status="$(timeout 20 docker wait "$web")"
[[ "$exit_status" != 0 ]]
echo 'FPM child failure terminates the whole web container.'
docker rm -v "$web" >/dev/null

start_web
docker stop --time 15 "$web" >/dev/null
[[ "$(docker inspect --format '{{.State.ExitCode}}' "$web")" == 0 ]]
echo 'Rootless production entrypoint, PostgreSQL, routing/privacy, child-failure and graceful-stop checks passed.'
