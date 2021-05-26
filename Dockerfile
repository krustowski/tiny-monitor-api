# tiny-monitor-api Dockerfile
#
# krustowski <k@n0p.cz>

FROM alpine:latest

ARG APP_ROOT
ARG TZ
ARG DATABASE_FILE
ARG PHP_VERSION

ENV APP_ROOT ${APP_ROOT}
ENV TZ ${TZ}
ENV DATABASE_FILE ${DATABASE_FILE}
ENV PHP_VERSION ${PHP_VERSION}

# install essentials
RUN apk update && \
    apk upgrade && \
    apk add --no-cache \
    	bash \
	runit \
	nginx \
    curl \
	${PHP_VERSION} \
	${PHP_VERSION}-fpm \
	${PHP_VERSION}-curl \
	${PHP_VERSION}-json \
	${PHP_VERSION}-sqlite3 \
	sqlite \
	tzdata

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

RUN rm -f /var/log/nginx/* && \
    ln -s /dev/stdout /var/log/nginx/error.log && \
    ln -s /dev/stdout /var/log/nginx/access.log

WORKDIR ${APP_ROOT}
#USER nginx
EXPOSE 80
ENTRYPOINT ["docker/entrypoint.sh"]
