#!/bin/bash

[ -z ${RUN_FROM_MAKEFILE} ] && \
    echo "This script has to be run by 'make'!" &&
    exit 1

# load JSON file to PAYLOAD variable
[[ -f ${JSON_FILE} ]] \
    && PAYLOAD="$(cat ${JSON_FILE})" \
    || PAYLOAD='{}'

# define API endpoint for cURL
ENDPOINT="http://localhost:${TM_API_PORT}/api/v2/${FUNCTION}?apikey=${SUPERVISOR_APIKEY}"

# execute
curl -sL -d "${PAYLOAD}" ${ENDPOINT}
