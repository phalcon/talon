# syntax=docker/dockerfile:1
ARG PHP_VERSION=8.5

FROM php:${PHP_VERSION}-cli AS base

ARG PHP_VERSION=8.5
ARG PHALCON_VARIANT=v5
ARG PHALCON_V5_CONSTRAINT="^5.0"
ARG UID=1000
ARG GID=1000
ARG USER=talon
ARG GROUP=talon

# System packages + PHP extensions + an unprivileged user matching the host UID
RUN <<EOT
set -eux
groupadd -g "${GID}" "${GROUP}"
useradd -l -m -u "${UID}" -g "${GID}" -s /bin/bash "${USER}"
apt-get update -y
apt-get install -yq --no-install-recommends \
    curl git unzip zip \
    default-mysql-client postgresql-client \
    libicu-dev libzip-dev libpq-dev libsqlite3-dev libmemcached-dev zlib1g-dev
docker-php-ext-install intl zip pdo_mysql pdo_pgsql pdo_sqlite
apt-get clean -y
rm -rf /var/lib/apt/lists/*
EOT

# Composer
COPY --from=composer/composer:2 /usr/bin/composer /usr/bin/composer

RUN <<EOT
set -eux
apt-get update -y
apt-get install -yq --no-install-recommends ${PHPIZE_DEPS} libtool
curl -fsSL https://github.com/php/pie/releases/latest/download/pie.phar -o /usr/local/bin/pie
chmod +x /usr/local/bin/pie
# redis/memcached may not have a build yet for the newest PHP (e.g. 8.5); keep the image
# build resilient - the Services tests skip when the extension/backend is unavailable.
pie install --no-interaction pecl/redis || echo "skipping redis extension (no build for this PHP version)"
pie install --no-interaction pecl/memcached || echo "skipping memcached extension (no build for this PHP version)"
pie install --no-interaction pecl/pcov
# Phalcon v5 C extension (v5 variant only; v6 uses the phalcon/phalcon composer package)
if [ "${PHALCON_VARIANT}" = "v5" ]; then
    pie install --no-interaction phalcon/cphalcon:${PHALCON_V5_CONSTRAINT}
    php -m | grep -i phalcon
fi
apt-get clean -y
rm -rf /var/lib/apt/lists/*
EOT

COPY resources/docker/extra.ini /usr/local/etc/php/conf.d/zz-talon.ini
COPY --chown=${UID}:${GID} resources/docker/.bashrc /home/${USER}/.bashrc

ENV HOME=/home/${USER} \
    COMPOSER_HOME=/home/${USER}/.composer

RUN install -d -m 0775 -o "${USER}" -g "${GROUP}" /srv
WORKDIR /srv
COPY --chown=${UID}:${GID} . /srv
USER ${USER}

# v5: extension provides Phalcon (phalcon/phalcon stays dormant if present).
# v6: install phalcon/phalcon explicitly so Phalcon is available without the ext.
RUN <<EOT
set -eux
composer install --prefer-dist --no-interaction --no-progress
if [ "${PHALCON_VARIANT}" = "v6" ]; then
    composer require --dev --no-interaction --no-progress "phalcon/phalcon:^6@dev"
fi
EOT

# Keepalive so `docker compose up -d` holds the container open for `exec`;
# `docker compose run --rm app <cmd>` still overrides this for one-off commands.
CMD ["sleep", "infinity"]
