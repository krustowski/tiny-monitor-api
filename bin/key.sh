#!/bin/bash

#
# tiny-monitor-api / supervisor's apikey generation
#

[[ -z ${RUN_FROM_MAKEFILE} ]] && \
    echo "This script has to be run by 'make key'!" &&
    exit 1

# default 'macro' for Linux (RHEL-based) kernel
SHA_TOOL="sha512sum"

# test if we are running on Darwin kernel (macOS)
uname -a | grep -iq 'Darwin' && SHA_TOOL="shasum --algorithm 512 --0"

# generate and/or overwrite APIKEY_FILE contents
ls -lR | xargs -0 printf "%s $(date +\%s) %s" | ${SHA_TOOL} | cut -d' ' -f1 | tr -d '\n' > ${SUPER_APIKEY_FILE} && \
cat ${SUPER_APIKEY_FILE} | xargs -0 printf "%s $(date +\%s) %s" | ${SHA_TOOL} | cut -d' ' -f1 | tr -d '\n' > ${PUBLIC_APIKEY_FILE} \
     && echo " ${GREEN}New SUPERVISOR_APIKEY generated!${RESET}" \
     || echo " ${RED}Something went wrong during new SUPERVISOR_APIKEY generation!${RESET}"
