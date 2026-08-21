#!/usr/bin/env sh

set -eu

project="${COMPOSE_PROJECT_NAME:-zandu-sales-manager}"
container="${project}-postgres-1"
source_db="${POSTGRES_DB:-zandu}"
user="${POSTGRES_USER:-zandu}"
restore_db="zandu_restore_test"
backup_file="/tmp/zandu-lot0-backup.dump"

docker exec "$container" pg_dump -U "$user" -d "$source_db" -Fc -f "$backup_file"
docker exec "$container" dropdb -U "$user" --if-exists "$restore_db"
docker exec "$container" createdb -U "$user" "$restore_db"
docker exec "$container" pg_restore -U "$user" -d "$restore_db" "$backup_file"
docker exec "$container" psql -U "$user" -d "$restore_db" -v ON_ERROR_STOP=1 -c \
    "SELECT COUNT(*) FROM doctrine_migration_versions;"
docker exec "$container" rm -f "$backup_file"
