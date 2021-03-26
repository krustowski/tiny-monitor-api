#!/bin/bash

#
# tiny-monitor-api / execute single API call
#

[[ -z ${RUN_FROM_MAKEFILE} ]] && \
    echo "This script has to be run by 'make call'!" &&
    exit 1

[[ ! -f ${APIKEY_FILE} ]] \
    && echo "API key file '${APIKEY_FILE}' not found! Run 'make deploy' and try again!"

export SUPERVISOR_APIKEY=$(cat ${APIKEY_FILE}) \

echo $SUPERVISOR_APIKEY

# load JSON file to PAYLOAD variable
[[ -f ${JSON_FILE} ]] \
    && PAYLOAD="$(cat ${JSON_FILE})" \
    || PAYLOAD='{}'

# define API endpoint for cURL
ENDPOINT="http://localhost:${TM_API_PORT}/api/v2/${FUNCTION}?apikey=${SUPERVISOR_APIKEY}"

# execute
curl -sL -d "${PAYLOAD}" ${ENDPOINT}
