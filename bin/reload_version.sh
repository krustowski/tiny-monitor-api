#!/bin/bash

#
# tiny-monitor-api / reload version in PHP files
#

[[ -z ${RUN_FROM_MAKEFILE} ]] && \
    echo "This script has to be run by 'make version'!" &&
    exit 1

# https://stackoverflow.com/questions/4210042/how-to-exclude-a-directory-in-find-command
PHP_PROJECT_FILES=$(find . -f -name "*.php" -path "./vendor" -prune -false -o)

for PHP_FILE in ${PHP_PROJECT_FILES}; do
    sed -i "s|version=\"${APP_VERSION_PREV}\",|version=\"${APP_VERSION}\",|" ${PHP_FILE};
done
