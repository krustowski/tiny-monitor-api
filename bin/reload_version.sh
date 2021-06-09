#!/bin/bash

#
# tiny-monitor-api / reload version in PHP files
#

[[ -z ${RUN_FROM_MAKEFILE} ]] && \
    echo "This script has to be run by 'make version'!" &&
    exit 1

# rewrite versions
PHP_FILES=$(find src mods public -type f -name "*.php")

# broken! do not run
for FILE in ${PHP_FILES}; do
    sed -i "s|\(.*version\)=\(.*\)|\1=\"${APP_VERSION}\",|" ${FILE};
done
