#!/bin/bash

DIR="app-store-suite/"

if [ -d "$DIR" ]; then
  cd ${DIR}
  export APPLICATION_STORE=GLOBAL
  export SPRYKER_BE_HOST=apps.spryker.local
  "${@}"
  exit $?
fi

exit 0
