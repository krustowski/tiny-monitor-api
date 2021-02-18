#!/bin/bash

# entrypoint.sh
# krustowski <k@n0p.cz>

# TODO start those services:
SERVICES=(php8-fpm redis)

for s in ${SERVICES[@]}; do
    #rc-update add $s default;
    #rc-service $s start
    sv $s start
done

#nginx -g "daemon off;"

# to debug
sleep 3600   