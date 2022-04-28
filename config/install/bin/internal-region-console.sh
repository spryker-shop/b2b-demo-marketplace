#!/bin/bash

# This script is used to execute console commands for internal regions: EU, US.
# It also skips the execution of external region (app-store-suite and registry-service) console commands.
# Also, this file skips execution of the 'pbc:app:registration' command that is needed only in the app-store-suite and is not needed in the store and registry-service contexts.

externalRegions=('app-store-suite' 'registry-service')

if [[ ${externalRegions[*]} =~ ${SPRYKER_REGION} ]]; then
    echo "'${SPRYKER_REGION}' region installation commands must be executed through sections using the [config/install/bin/install.sh] script"
    exit 0
fi

if [[ "${@}" = *pbc:app:registration ]]; then
    echo "You are in the store region."
    exit 0
fi

vendor/bin/console "${@}"
exit $?
