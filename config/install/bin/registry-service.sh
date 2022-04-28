#!/bin/bash

DIR="registry-service/"

if [ -d "$DIR" ]; then
  cd ${DIR}
  export APPLICATION_STORE=REGISTRY
  export SPRYKER_BE_HOST=backoffice.registry.spryker.local
  export SPRYKER_FE_HOST=yves.registry.spryker.local
  export SPRYKER_API_HOST=glue.registry.spryker.local
  "${@}"
  exit $?
fi

exit 0
