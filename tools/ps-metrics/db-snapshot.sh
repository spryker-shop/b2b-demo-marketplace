#!/usr/bin/env bash
#
# Database snapshots as copies of the logical database inside the same MariaDB server.
#
# Quicker than dump and restore: rows go table to table inside the server, several tables at a time,
# with no SQL text in between. A restore never drops the current state: its tables are first moved into
# a trash__ database with RENAME TABLE, which is instant, and only then is the snapshot copied back.
# Snapshots and trash databases cost disk space in the database volume. Nothing here drops a database
# except `drop`, which needs --yes.
#
# Usage:
#   tools/ps-metrics/db-snapshot.sh create <name>        # copy the working database into snap__<name>
#   tools/ps-metrics/db-snapshot.sh restore <name>       # working tables -> trash__<ts>, then snap__<name> -> working
#   tools/ps-metrics/db-snapshot.sh verify <name>        # row counts of snap__<name> vs the working database
#   tools/ps-metrics/db-snapshot.sh list                 # snapshots and trash databases with their sizes
#   tools/ps-metrics/db-snapshot.sh drop <db> --yes      # drop one snap__ or trash__ database
#
# The key-value store, search and the broker are not part of a snapshot. After a restore their content
# belongs to another database state: empty them, or rebuild them with publish and sync.

set -euo pipefail

DB_CONTAINER="${DB_CONTAINER:-spryker_database_1}"
DB_ROOT_USER="${DB_ROOT_USER:-root}"
DB_ROOT_PASS="${DB_ROOT_PASS:-secret}"
DB_NAME="${DB_NAME:-eu-docker}"
PARALLEL="${PARALLEL:-4}"

fail() { printf 'db-snapshot: %s\n' "$1" >&2; exit 1; }
sql()  { docker exec -i "${DB_CONTAINER}" mariadb -u"${DB_ROOT_USER}" -p"${DB_ROOT_PASS}" -N -B "$@"; }
db_exists() { [ "$(sql -e "SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name='$1'")" = "1" ]; }
tables_of() { sql -e "SELECT table_name FROM information_schema.tables WHERE table_schema='$1' AND table_type='BASE TABLE' ORDER BY table_name"; }

# Exact row counts of every table, one "table<TAB>count" line each. information_schema.table_rows is
# an estimate for InnoDB, so it cannot verify a copy.
counts_of() {
    local db="$1" q="" t
    for t in $(tables_of "${db}"); do
        [ "${t}" = "_snapshot_meta" ] && continue
        q="${q:+${q} UNION ALL }SELECT '${t}', COUNT(*) FROM \`${db}\`.\`${t}\`"
    done
    [ -n "${q}" ] && sql -e "${q}" | sort
}

copy_tables() {
    local src="$1" dst="$2"
    local views
    views=$(sql -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${src}' AND table_type='VIEW'")
    [ "${views}" = "0" ] || fail "${src} has ${views} view(s); copying views is not implemented"
    # The table name goes in as an argument: BSD xargs -I caps the substituted command at 255 bytes.
    tables_of "${src}" | grep -v '^_snapshot_meta$' | xargs -P "${PARALLEL}" -n 1 bash -c '
        docker exec -i "$0" mariadb -u"$1" -p"$2" -e "SET SESSION foreign_key_checks=0, unique_checks=0;
            CREATE TABLE \`$4\`.\`$5\` LIKE \`$3\`.\`$5\`; INSERT INTO \`$4\`.\`$5\` SELECT * FROM \`$3\`.\`$5\`;"' \
        "${DB_CONTAINER}" "${DB_ROOT_USER}" "${DB_ROOT_PASS}" "${src}" "${dst}" \
        || fail "copying ${src} into ${dst} failed"
}

# A copy counts only when every table exists on both sides with the same exact row count.
verify_copy() {
    local src="$1" dst="$2" a b
    a=$(counts_of "${src}"); b=$(counts_of "${dst}")
    if [ "${a}" != "${b}" ]; then
        diff <(printf '%s\n' "${a}") <(printf '%s\n' "${b}") | head -20 >&2
        fail "${dst} differs from ${src}"
    fi
    printf '%s\n' "${a}" | awk -F'\t' '{t++; r+=$2} END {printf "  verified: %d tables, %d rows\n", t, r}'
}

name_ok() { [[ "$1" =~ ^[a-z0-9_]+$ ]] || fail "name must match [a-z0-9_]+, got '$1'"; }

case "${1:-}" in
    create)
        name="${2:?snapshot name required}"; name_ok "${name}"
        snap="snap__${name}"
        db_exists "${snap}" && fail "${snap} exists; a snapshot is never overwritten"
        started=$(date +%s)
        sql -e "CREATE DATABASE \`${snap}\`"
        copy_tables "${DB_NAME}" "${snap}"
        verify_copy "${DB_NAME}" "${snap}"
        # Written last: restore refuses a snapshot without it, so an interrupted copy is never restored.
        sql -e "CREATE TABLE \`${snap}\`._snapshot_meta (source VARCHAR(64), created_at DATETIME, seconds INT);
                INSERT INTO \`${snap}\`._snapshot_meta VALUES ('${DB_NAME}', NOW(), $(( $(date +%s) - started )))"
        printf 'create: %s in %ss\n' "${snap}" "$(( $(date +%s) - started ))"
        ;;
    restore)
        name="${2:?snapshot name required}"; name_ok "${name}"
        snap="snap__${name}"
        db_exists "${snap}" || fail "${snap} does not exist"
        [ -n "$(sql -e "SELECT 1 FROM \`${snap}\`._snapshot_meta LIMIT 1" 2>/dev/null)" ] \
            || fail "${snap} has no _snapshot_meta row; it was not completed and is not restored"
        started=$(date +%s)
        trash="trash__$(date +%Y%m%d_%H%M%S)"
        sql -e "CREATE DATABASE \`${trash}\`"
        renames=$(tables_of "${DB_NAME}" | awk -v s="${DB_NAME}" -v d="${trash}" '{printf "%s`%s`.`%s` TO `%s`.`%s`", (NR>1?", ":""), s, $1, d, $1}')
        [ -n "${renames}" ] && sql -e "RENAME TABLE ${renames}"
        [ -z "$(tables_of "${DB_NAME}")" ] || fail "tables left in ${DB_NAME} after moving them to ${trash}"
        printf '  current state moved to %s\n' "${trash}"
        copy_tables "${snap}" "${DB_NAME}"
        verify_copy "${snap}" "${DB_NAME}"
        printf 'restore: %s -> %s in %ss (previous state in %s)\n' "${snap}" "${DB_NAME}" "$(( $(date +%s) - started ))" "${trash}"
        ;;
    verify)
        name="${2:?snapshot name required}"; name_ok "${name}"
        verify_copy "${DB_NAME}" "snap__${name}"
        ;;
    list)
        sql -e "SELECT table_schema, ROUND(SUM(data_length + index_length) / 1024 / 1024), COUNT(*)
                FROM information_schema.tables
                WHERE table_schema = '${DB_NAME}' OR table_schema LIKE 'snap\\_\\_%' OR table_schema LIKE 'trash\\_\\_%'
                GROUP BY table_schema ORDER BY table_schema" \
            | awk -F'\t' 'BEGIN {printf "%-40s %10s %7s\n", "database", "MB", "tables"} {printf "%-40s %10s %7s\n", $1, $2, $3}'
        ;;
    drop)
        db="${2:?database name required}"
        [[ "${db}" =~ ^(snap|trash)__[a-z0-9_]+$ ]] || fail "only snap__ and trash__ databases can be dropped, got '${db}'"
        [ "${3:-}" = "--yes" ] || fail "drop is permanent; pass --yes"
        db_exists "${db}" || fail "${db} does not exist"
        sql -e "DROP DATABASE \`${db}\`"
        printf 'drop: %s\n' "${db}"
        ;;
    *)
        fail "usage: db-snapshot.sh create <name> | restore <name> | verify <name> | list | drop <db> --yes"
        ;;
esac
