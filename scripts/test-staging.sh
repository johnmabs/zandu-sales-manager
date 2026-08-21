#!/usr/bin/env sh

set -eu

container="zandu-staging-smoke"
network="${COMPOSE_PROJECT_NAME:-zandu-sales-manager}_default"

cleanup() {
    docker rm -f "$container" >/dev/null 2>&1 || true
}

trap cleanup EXIT INT TERM
cleanup

docker run --rm -d \
    --name "$container" \
    --network "$network" \
    -p 18080:8080 \
    -e APP_SECRET="ci-staging-app-secret-00000000000000000000000000000000" \
    -e JWT_PASSPHRASE="ci-staging-jwt-passphrase-000000000000000000000000000000" \
    -e DATABASE_URL="postgresql://zandu:zandu@postgres:5432/zandu?serverVersion=18&charset=utf8" \
    zandu-sales-manager-backend:latest \
    php -S 0.0.0.0:8080 -t public >/dev/null

attempt=0
until response="$(curl --fail --silent http://127.0.0.1:18080/health/ready)"; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge 20 ]; then
        docker logs "$container"
        exit 1
    fi
    sleep 1
done

test "$response" = '{"status":"ready","database":"up"}'
