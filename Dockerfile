ARG php_version=8.3
FROM dunglas/frankenphp:1.2-php${php_version} AS base

WORKDIR /laravel
SHELL ["/bin/bash", "-eou", "pipefail", "-c"]

ENV SERVER_NAME=:80
ARG user=laravel

# הגדרת משתני סביבה למניעת עדכון FrankenPHP
ENV OCTANE_SKIP_FRANKENPHP_UPGRADE=true
ENV FRANKENPHP_VERSION=1.2.0

COPY ./ /laravel
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY --chmod=755 /entrypoint.sh entrypoint.sh
COPY --chmod=755 /common common
COPY --chown=${user}:${user} /artisan artisan
COPY .env.example .env
COPY /php.ini "${PHP_INI_DIR}/php.ini"

# התקן dependencies
RUN apt-get update \
    && apt-get satisfy -y --no-install-recommends \
        "curl (>=7.88)" \
        "supervisor (>=4.2)" \
        "unzip (>=6.0)" \
        "vim-tiny (>=2)" \
    && apt-get install -y nodejs npm \
    && npm install -g npm@7 \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# התקן PHP extensions
RUN install-php-extensions \
    bcmath \
    bz2 \
    curl \
    exif \
    gd \
    intl \
    pcntl \
    pdo_pgsql \
    mbstring \
    opcache \
    redis \
    sockets \
    calendar \
    zip

# הורד ידנית את FrankenPHP בגרסה תואמת
RUN curl -L "https://github.com/dunglas/frankenphp/releases/download/v1.2.0/frankenphp-linux-x86_64" -o /usr/local/bin/frankenphp-new \
    && chmod +x /usr/local/bin/frankenphp-new \
    && mv /usr/local/bin/frankenphp /usr/local/bin/frankenphp-old \
    && mv /usr/local/bin/frankenphp-new /usr/local/bin/frankenphp

# צור משתמש
RUN useradd \
    --uid 1000 \
    --shell /bin/bash \
    "${user}" \
    && setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp \
    && chown -R "${user}:${user}" \
        /laravel \
        /data/caddy \
        /config/caddy \
        /var/{log,run} \
    && chmod -R a+rw \
        /var/{log,run}

# התקן composer dependencies
RUN composer install --no-dev --optimize-autoloader

# התקן npm dependencies
RUN npm install

# החלף למשתמש רגיל
USER ${user}

# הרשאות storage
RUN chmod -R a+rw storage

ENTRYPOINT ["/laravel/entrypoint.sh"]