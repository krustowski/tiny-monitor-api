#!/bin/bash

# entrypoint.sh
# krustowski <k@n0p.cz>

php-fpm8 -t && php-fpm8 -D && \
    crond && \
    nginx -g "daemon off;" 
