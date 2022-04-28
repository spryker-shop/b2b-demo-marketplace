#!/bin/bash

# This script is used to run installation scripts for external regions: app-store-suite and registry-service.
# It also skips the execution for each internal region: EU, US.

if [[ "${SPRYKER_REGION}" = 'app-store-suite' ]]; then
    echo "This command executed for ${SPRYKER_REGION}."
    bash "$(dirname "$0")"/app-store-suite.sh vendor/bin/install "${@}"
    exit $?
fi

if [[ "${SPRYKER_REGION}" = 'registry-service' ]]; then
    echo "This command executed for ${SPRYKER_REGION}."
    bash "$(dirname "$0")"/registry-service.sh vendor/bin/install "${@}"
    exit $?
fi

echo "This script cannot be executed for ${SPRYKER_REGION}."
exit 0
