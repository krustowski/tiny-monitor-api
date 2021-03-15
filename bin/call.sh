#!/bin/bash

# load JSON file to PAYLOAD variable
[[ -f ${JSON_FILE} ]] \
    && PAYLOAD="$(cat ${JSON_FILE})" \
    || PAYLOAD='{}'

# define API endpoint for cURL
ENDPOINT="http://localhost:${API_PORT}/api/v2/${FUNCTION}?apikey=${SUPERVISOR_APIKEY}"

# execute
curl -sL -d "${PAYLOAD}" ${ENDPOINT}
