#!/bin/bash

#
# tiny-monitor-api / supervisor's apikey generation
#

[[ -z ${RUN_FROM_MAKEFILE} ]] && \
    echo "This script has to be run by 'make key'!" &&
    exit 1

# generate and/or overwrite APIKEY_FILE contents
ls -lR | xargs -0 printf "%s $(date +\%s) %s" | shasum --algorithm 512 --0 | cut -d' ' -f1 | tr -d '\n' > ${APIKEY_FILE} \
     && echo " ${GREEN}New SUPERVISOR_APIKEY generated!${RESET}" \
     || echo " ${RED}Something went wrong during new SUPERVISOR_APIKEY generation!${RESET}"