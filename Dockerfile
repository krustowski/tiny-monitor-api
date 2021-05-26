# tiny-monitor-api Dockerfile
#
# krustowski <k@n0p.cz>

FROM alpine:latest

ARG APP_ROOT
ARG TZ
ARG DATABASE_FILE

ENV APP_ROOT ${APP_ROOT}
ENV TZ ${TZ}
ENV DATABASE_FILE ${DATABASE_FILE}

# install essentials
RUN apk update && \
    apk upgrade && \
    apk add --no-cache bash runit nginx php8 php8-fpm php8-curl php8-json php8-sqlite3 sqlite tzdata

# copy repo
COPY . ${APP_ROOT}
RUN cd /var/www && rm -rf html localhost && \
    chmod a+w ${APP_ROOT} && \
    chown -R nginx:nginx ${APP_ROOT}

# reconfigure services
RUN rm -f /etc/nginx/http.d/* && \
    ln -s ${APP_ROOT}/docker/tiny-monitor-api-nginx.conf /etc/nginx/http.d/ && \
    mkdir /run/nginx && \
    chown nginx:nginx /run/nginx && \
    nginx -t && \
    php-fpm8 -t

RUN ln -s /dev/stdout /var/log/nginx/error.log

WORKDIR ${APP_ROOT}
#USER nginx
EXPOSE 80
ENTRYPOINT ["docker/entrypoint.sh"]
