# tiny-monitor-api Dockerfile
#
# krustowski <k@n0p.cz>
# mxdpeep <f@n0p.cz> 

FROM alpine:latest

ENV APP_ROOT="/var/www/tiny-monitor-api"
ENV HOSTNAME="tiny-monitor-api"

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
    ln -s ${APP_ROOT}/docker/tiny-monitor-api-nginx.conf /etc/nginx/http.d/ 
RUN mkdir /run/nginx && \
    chown nginx:nginx /run/nginx
RUN echo -e "daemonize yes\nunixsocket /tmp/redis.sock" >> /etc/redis.conf

#RUN rm -f /var/log/php8/error.log && \
#    ln -s /dev/stdout /var/log/redis/redis.log && \
#    ln -s /dev/stdout /var/log/php8/error.log
RUN chown -R :nginx /var/log/php8/ && chmod -R g+rw /var/log/php8/ && \
    chown -R :nginx /var/log/redis/ && chmod -R g+w /var/log/redis/
#RUN echo "@reboot sleep 5 && /usr/sbin/php-fpm8 -t && /usr/sbin/php-fpm8 -D && /usr/bin/redis-server /etc/redis.conf" >> /var/spool/cron/crontabs/root
#RUN echo "@reboot nginx -g \"daemon off;\"" >> /var/spool/cron/crontabs/nginx
#RUN cat /etc/php8/php-fpm.d/www.conf && sed -i 's|listen = /run/php/php8.0-fpm.sock|listen = 9000|' /etc/php8/php-fpm.d/www.conf

# final cmd batch
WORKDIR ${APP_ROOT}
USER nginx
EXPOSE 80
ENTRYPOINT ["docker/entrypoint.sh"]