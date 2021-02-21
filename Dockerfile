# tiny-monitor-api Dockerfile
#
# krustowski <k@n0p.cz>
# mxdpeep <f@n0p.cz> 

FROM alpine:latest

ENV APP_ROOT="/var/www/tiny-monitor-api"
ENV REDIS_PORT=6379
ENV TZ="Europe/Prague"

# install essentials
RUN apk update && \
    apk upgrade && \
    apk add --no-cache bash runit nginx php8 php8-fpm php8-curl php8-json tzdata

# clone the repo
COPY . ${APP_ROOT}
RUN rm -rf /var/www/html && \
    chown -R nginx:nginx ${APP_ROOT}

# reconfigure services
RUN rm -f /etc/nginx/http.d/* && \
    ln -s ${APP_ROOT}/docker/tiny-monitor-api-nginx.conf /etc/nginx/http.d/ 
RUN sed -i 's|error_log /var/log/nginx/error.log warn;|error_log /tmp/nginx-error.log warn;|' /etc/nginx/nginx.conf && \
    sed -i 's|access_log /var/log/nginx/access.log main;|access_log /tmp/nginx-access.log main;|' /etc/nginx/nginx.conf && \
    mkdir /run/nginx && \
    chown -R nginx:nginx /run/nginx && \
    chown -R nginx:nginx /var/log/nginx/ && \
    nginx -t
RUN chown -R :nginx /var/log/php8/ && chmod -R g+rw /var/log/php8/
RUN sed -i "s|'server' => 'redis-server:6379',|'server' => 'redis-server:${REDIS_PORT}',|" ${APP_ROOT}/src/Api.php

# final cmd batch
WORKDIR ${APP_ROOT}
USER nginx
EXPOSE 8080
ENTRYPOINT ["docker/entrypoint.sh"]
