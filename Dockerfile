# tiny-monitor-api Dockerfile
#
# krustowski <k@n0p.cz>

FROM alpine:latest

ARG APP_ROOT
ARG TZ
ARG PHP_VERSION

ENV APP_ROOT ${APP_ROOT}
ENV TZ ${TZ}
ENV PHP_VERSION ${PHP_VERSION}

# install essentials
RUN apk update && \
    apk upgrade && \
    apk add --no-cache \
	runit \
	nginx \
    curl \
    jq \
    bash \
	${PHP_VERSION} \
	${PHP_VERSION}-fpm \
	${PHP_VERSION}-curl \
	${PHP_VERSION}-json \
	${PHP_VERSION}-sqlite3 \
	sqlite \
	tzdata

# copy repo, just essentials
COPY mods/ ${APP_ROOT}/mods/
COPY public/ ${APP_ROOT}/public/
COPY src/ ${APP_ROOT}/src/
COPY vendor/ ${APP_ROOT}/vendor/
COPY .env \
     composer.json \
     composer.lock \
     init_config.json \
     LICENSE \
     README.md \
     ${APP_ROOT}/

COPY docker/tiny-monitor-api-nginx.conf /etc/nginx/http.d/
COPY docker/entrypoint.sh ${APP_ROOT}/

# offload default www files
RUN cd /var/www && rm -rf html localhost && \
    chmod a+ws ${APP_ROOT} && \
    chown -R nginx:nginx ${APP_ROOT}

# reconfigure services
RUN rm -f /etc/nginx/http.d/default* && \
    mkdir /run/nginx && \
    chown nginx:nginx /run/nginx && \
    nginx -t && \
    php-fpm8 -t

RUN rm -f /var/log/nginx/* && \
    ln -s /dev/stdout /var/log/nginx/error.log && \
    ln -s /dev/stdout /var/log/nginx/access.log

WORKDIR ${APP_ROOT}
EXPOSE 80
ENTRYPOINT ["./entrypoint.sh"]
