#!/bin/bash

# entrypoint.sh
# krustowski <tmv2@n0p.cz>

php-fpm8 -D && \
    nginx -g "daemon off;" 
