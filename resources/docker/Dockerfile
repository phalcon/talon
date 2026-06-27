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
pecl install redis memcached pcov
docker-php-ext-enable redis memcached pcov
apt-get clean -y
rm -rf /var/lib/apt/lists/*
EOT

# Composer
COPY --from=composer/composer:2 /usr/bin/composer /usr/bin/composer

# Phalcon provider: v5 = C extension via PIE (builds from Packagist source, auto-enables);
# v6 = the phalcon/phalcon composer package (no extension installed).
RUN <<EOT
set -eux
if [ "${PHALCON_VARIANT}" = "v5" ]; then
    apt-get update -y
    apt-get install -yq --no-install-recommends ${PHPIZE_DEPS}
    curl -fsSL https://github.com/php/pie/releases/latest/download/pie.phar -o /usr/local/bin/pie
    chmod +x /usr/local/bin/pie
    pie install --no-interaction phalcon/cphalcon:${PHALCON_V5_CONSTRAINT}
    php -m | grep -i phalcon
    apt-get clean -y
    rm -rf /var/lib/apt/lists/*
fi
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

ENTRYPOINT ["bash", "bin/docker-entrypoint.sh"]
CMD ["bash"]
