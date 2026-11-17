#!/usr/bin/env bash
#
# SOL-629 insert path: empties the local database so the next import inserts instead of updates.
#
# DESTRUCTIVE: drops every table of the local database and recreates the schema. Run only on a local
# docker/sdk environment, and only after a dump exists (see `dump` below). The steps are the database
# part of config/install/docker.yml: tables:drop, diff, migrate, migration:delete, setup:init-db,
# queue:setup.
#
# Usage:
#   tools/ps-metrics/reset-db.sh dump <file.sql.gz>   # dump the current database (read-only)
#   tools/ps-metrics/reset-db.sh reset                # drop and recreate the schema
#   tools/ps-metrics/reset-db.sh restore <file.sql.gz> # load a dump back

set -euo pipefail

DB_HOST="${DB_HOST:-database}"
DB_USER="${DB_USER:-spryker}"
DB_PASS="${DB_PASS:-secret}"
DB_NAME="${DB_NAME:-eu-docker}"
CONTAINER="${CLI_CONTAINER:-spryker_cli_1}"

fail() { printf 'reset-db: %s\n' "$1" >&2; exit 1; }

# `docker/sdk cli` exits 0 even when the command inside failed, so each step prints a marker that is
# checked instead.
console_step() {
    local out
    out=$(docker/sdk cli "vendor/bin/console $1 && echo STEP-OK" 2>&1) || true
    printf '%s\n' "${out}" | grep -q 'STEP-OK' || { printf '%s\n' "${out}" | tail -20 >&2; fail "step failed: $1"; }
    printf '  ok: %s\n' "$1"
}

case "${1:-}" in
    dump)
        out="${2:?dump file path required}"
        [ -e "${out}" ] && fail "${out} exists; a dump is never overwritten"
        docker exec "${CONTAINER}" bash -lc "mariadb-dump -h ${DB_HOST} -u ${DB_USER} -p${DB_PASS} --single-transaction --routines --triggers '${DB_NAME}'" \
            | gzip > "${out}"
        # A dump that ends without the completion marker was cut short.
        gzip -dc "${out}" | tail -1 | grep -q 'Dump completed' || fail "dump ${out} is incomplete"
        printf 'dump: %s (%s bytes)\n' "${out}" "$(wc -c < "${out}" | tr -d ' ')"
        ;;
    reset)
        console_step "propel:tables:drop"
        console_step "propel:diff"
        console_step "propel:migrate"
        console_step "propel:migration:delete"
        console_step "setup:init-db"
        console_step "queue:setup"
        tables=$(docker exec "${CONTAINER}" bash -lc "mariadb -h ${DB_HOST} -u ${DB_USER} -p${DB_PASS} -N -e 'SELECT COUNT(*) FROM spy_product_abstract' '${DB_NAME}'")
        [ "${tables}" = "0" ] || fail "spy_product_abstract holds ${tables} rows after the reset"
        printf 'reset: schema recreated, spy_product_abstract empty\n'
        ;;
    restore)
        in="${2:?dump file path required}"
        [ -f "${in}" ] || fail "${in} not found"
        gzip -dc "${in}" | docker exec -i "${CONTAINER}" bash -lc "mariadb -h ${DB_HOST} -u ${DB_USER} -p${DB_PASS} '${DB_NAME}'"
        printf 'restore: %s loaded\n' "${in}"
        ;;
    *)
        fail "usage: reset-db.sh dump <file> | reset | restore <file>"
        ;;
esac
