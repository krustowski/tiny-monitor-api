# tiny-monitor-api Dockerfile
#
# krustowski <k@n0p.cz>
# mxdpeep <f@n0p.cz> 

FROM alpine:latest

ENV APP_ROOT="/var/www/tiny-monitor-api"

# install essentials
RUN apk update && \
    apk upgrade && \
    apk add --no-cache bash runit nginx php8 php8-fpm php8-curl php8-json redis

# clone the repo
COPY . ${APP_ROOT}
RUN rm -rf /var/www/html && \
    chown -R nginx:nginx ${APP_ROOT}

# reconfigure services
RUN rm -f /etc/nginx/http.d/* && \
    ln -s ${APP_ROOT}/docker/tiny-monitor-api-nginx.conf /etc/nginx/http.d/ && \
    mkdir /run/nginx && \
    chown nginx:nginx /run/nginx
#RUN cat /etc/php8/php-fpm.d/www.conf && sed -i 's|listen = /run/php/php8.0-fpm.sock|listen = 9000|' /etc/php8/php-fpm.d/www.conf

# final cmd batch
WORKDIR ${APP_ROOT}
USER nginx
EXPOSE 80
ENTRYPOINT ["docker/entrypoint.sh"]
