#!/bin/bash

#
# tiny-monitor-api / execute single API call
#

[[ -z ${RUN_FROM_MAKEFILE} ]] && \
    echo "This script has to be run by 'make call'!" &&
    exit 1

[[ ! -f ${SUPER_APIKEY_FILE} ]] \
    && echo "API key file '${SUPER_APIKEY_FILE}' not found! Run 'make deploy' and try again!"

export SUPERVISOR_APIKEY=$(cat ${SUPER_APIKEY_FILE})

# load JSON file to PAYLOAD variable
[[ -f ${JSON_FILE} ]] \
    && PAYLOAD="$(cat ${JSON_FILE})" \
    || PAYLOAD='{}'

# define API endpoint for cURL
ENDPOINT="http://localhost:${APP_EXPOSE_PORT}/api/v2/${FUNCTION}"

# execute
curl -sL -d "${PAYLOAD}" -H "X-Api-Key: ${SUPERVISOR_APIKEY}" ${ENDPOINT}
