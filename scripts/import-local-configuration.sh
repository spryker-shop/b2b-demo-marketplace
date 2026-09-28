#!/usr/bin/env bash
#
# Bridges environment-provided configuration into Spryker's Configuration module.
#
# WHY THIS EXISTS
# The Configuration module never reads environment variables. Values reach the
# storefront only through:
#     spy_configuration_value -> publish -> spy_configuration_storage -> Redis
# So declaring a setting in deploy.yml's `environment:` block has no effect on
# its own - this script is what turns it into a configuration value.
#
# CONVENTION
# The environment variable NAME is the setting key, verbatim:
#     integrations:google_analytics:tracking:measurement_id=G-XXXXXXXXXX
# Any variable whose name looks like a setting key (lowercase segments joined
# by colons) is imported. Everything else is ignored, so ordinary SCREAMING_CASE
# variables are never picked up.
#
# Locally the values come from deploy.dev.yml; on cloud environments they come
# from the environment itself. Nothing is committed, so installing this
# boilerplate never carries environment-specific configuration.

#
set -euo pipefail

CSV_PATH='data/import/common/common/configuration_value.local.csv'
IMPORT_CONFIG='data/import/local/configuration_value_local.yml'
SETTING_KEY_PATTERN='^[a-z0-9_]+(:[a-z0-9_]+)+$'

tmp_rows="$(mktemp)"
trap 'rm -f "${tmp_rows}"' EXIT

while IFS= read -r line; do
    key="${line%%=*}"
    value="${line#*=}"

    [[ "${key}" =~ ${SETTING_KEY_PATTERN} ]] || continue
    [ -n "${value}" ] || continue

    printf '%s,global,,%s\n' "${key}" "${value}" >> "${tmp_rows}"
    echo "  ${key}"
done < <(env)

if [ ! -s "${tmp_rows}" ]; then
    echo 'No configuration settings found in the environment - nothing to import.'
    exit 0
fi

echo 'Importing environment-provided configuration (global scope):'
{
    echo 'setting_key,scope,scope_identifier,value'
    cat "${tmp_rows}"
} > "${CSV_PATH}"

vendor/bin/console data:import --config="${IMPORT_CONFIG}"

