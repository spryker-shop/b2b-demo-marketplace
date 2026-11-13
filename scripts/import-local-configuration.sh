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
# The environment variable is the setting key namespaced with `configuration:`:
#     configuration:integrations:google_analytics:tracking:measurement_id=G-XXXXXXXXXX
# The prefix is stripped to give the setting key actually written.
# Only variables carrying that prefix are imported; everything else in the
# environment is ignored.
#
# Locally the values come from deploy.dev.yml; on cloud environments they come
# from the environment itself. Nothing is committed, so installing this
# boilerplate never carries environment-specific configuration.
#
set -euo pipefail

CSV_PATH='data/import/common/common/configuration_value.local.csv'
IMPORT_CONFIG='data/import/local/configuration_value_local.yml'
# Environment variables are namespaced with `configuration:`; the remainder is
# the setting key. The prefix makes the intent explicit and keeps an unrelated
# variable from ever being treated as a setting - which matters, because
# ConfigurationValueSettingKeyValidatorStep THROWS on a key that is not in the
# schema, and that would abort the whole install.
ENV_VAR_PREFIX='configuration:'
ENV_VAR_PATTERN="^${ENV_VAR_PREFIX}[a-z0-9_]+(:[a-z0-9_]+)+$"

tmp_rows="$(mktemp)"
trap 'rm -f "${tmp_rows}"' EXIT

while IFS= read -r line; do
    name="${line%%=*}"
    value="${line#*=}"

    [[ "${name}" =~ ${ENV_VAR_PATTERN} ]] || continue
    [ -n "${value}" ] || continue

    # Strip the namespace: configuration:<setting key> -> <setting key>
    key="${name#"${ENV_VAR_PREFIX}"}"

    printf '%s,global,,%s\n' "${key}" "${value}" >> "${tmp_rows}"
    echo "  ${name}  ->  ${key}"
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

