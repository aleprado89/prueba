#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SCHEMA="$ROOT/shema_sistemasescolares.sql"
SEED="$ROOT/tests/fixtures/seed_minimal.sql"

DB_HOST="${SESYSTEM_DB_HOST:-127.0.0.1}"
DB_USER="${SESYSTEM_DB_USER:-root}"
DB_NAME="${SESYSTEM_DB_NAME:-sesystem_test}"

export MYSQL_PWD="${SESYSTEM_DB_PASSWORD:-}"

mysql -h"$DB_HOST" -u"$DB_USER" -e "DROP DATABASE IF EXISTS \`$DB_NAME\`; CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sed 's/sesystem_prueba/'"$DB_NAME"'/g' "$SCHEMA" | mysql -h"$DB_HOST" -u"$DB_USER"
mysql -h"$DB_HOST" -u"$DB_USER" "$DB_NAME" < "$SEED"
echo "Listo: $DB_NAME"
