# tiny-monitor-api Dockerfile
#
# krustowski <k@n0p.cz>
# mxdpeep <f@n0p.cz> 

FROM alpine:latest

ENV APP_ROOT="/var/www/tiny-monitor-api"
ENV TZ="Europe/Prague"
ENV DATABASE_FILE="${APP_ROOT}/tiny_monitor_core.db"

# install essentials
RUN apk update && \
    apk upgrade && \
    apk add --no-cache bash runit nginx php8 php8-fpm php8-curl php8-json php8-sqlite3 sqlite tzdata

# clone the repo
COPY . ${APP_ROOT}
RUN cd /var/www && rm -rf html localhost && \
    #touch ${DATABASE_FILE} && \
    chmod a+w ${APP_ROOT} && \
    chown -R nginx:nginx ${APP_ROOT}

# reconfigure services
RUN rm -f /etc/nginx/http.d/* && \
    ln -s ${APP_ROOT}/docker/tiny-monitor-api-nginx.conf /etc/nginx/http.d/ 
RUN mkdir /run/nginx && \
    chown nginx:nginx /run/nginx && \
    nginx -t && \
    php-fpm8 -t

# final cmd batch
WORKDIR ${APP_ROOT}
#USER nginx
EXPOSE 80
ENTRYPOINT ["docker/entrypoint.sh"]
