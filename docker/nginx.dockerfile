FROM 1and1internet/php-build-environment:8.2 AS build
LABEL maintainer="adam@t3v.uk"

WORKDIR /app/
USER 1000
ENV HOME /tmp
COPY --chown=1000:1000 . /app/
COPY --chown=1000:1000 docker/production.env /app/.env

RUN composer install \
        --no-dev \
        --no-progress \
        --optimize-autoloader \
        --prefer-dist && \
        php artisan route:cache

FROM node:20 AS npm
WORKDIR /app/
ENV HOME /tmp
RUN mkdir -p /app
COPY --from=build /app/ /app

RUN \
        npm install && \
        npm run build

FROM ghcr.io/t3rminalv/php-docker-nginx:develop
LABEL maintainer="adam@t3v.uk"

COPY --from=npm --chown=1000:1000 /app/ /var/www
