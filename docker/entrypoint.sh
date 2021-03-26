#!/bin/bash

# entrypoint.sh
# krustowski <k@n0p.cz>

APIKEY_FILE="${APP_ROOT}/.supervisor_apikey"

[[ -f ${APIKEY_FILE} ]] \
    && export SUPERVISOR_APIKEY=$(cat ${APIKEY_FILE}) \
    || echo "Blank APIKEY! Supervisor call will probably fail!"

php-fpm8 -D && \
    crond && \
    nginx -g "daemon off;" 
