#!/bin/bash

#
# tiny-monitor-api / app config generator/linker
#

[[ -z ${RUN_FROM_MAKEFILE} ]] && \
    echo "This script has to be run by 'make key'!" &&
    exit 1

# format JSON file output
cat > ${INIT_APP_FILE} << EOF
{
    "app_root": "${APP_ROOT}",
    "super_apikey": "$(cat ${SUPER_APIKEY_FILE})",
    "public_apikey": "$(cat ${PUBLIC_APIKEY_FILE})",
    "database_file": "${DATABASE_FILE}"
}
EOF

# parse final file through jq -- JSON syntax test
cat ${INIT_APP_FILE} | jq '.' > /dev/null \
    && echo -e "${GREEN} Init app config prepared!${RESET}" \
    || echo -e "${RED} ${RESET}"