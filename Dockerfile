ARG php_version=8.3

FROM dunglas/frankenphp:1.1-php${php_version} AS base

WORKDIR /laravel
SHELL ["/bin/bash", "-eou", "pipefail", "-c"]

ENV SERVER_NAME=:80
ARG user=laravel

COPY ./ /laravel
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY --chmod=755 /entrypoint.sh entrypoint.sh
COPY --chmod=755 /common common
COPY --chown=${user}:${user} /artisan artisan
COPY .env.example .env
COPY /php.ini "${PHP_INI_DIR}/php.ini"


RUN apt-get update \
  && apt-get install -y lsb-release apt-transport-https wget gnupg \
  && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - \
  && echo "deb https://packages.sury.org/php/ $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list \
  && apt-get update \
  && apt-get install -y php8.1 php8.1-mbstring php8.1-dev \
  && apt-get satisfy -y --no-install-recommends \
    "curl (>=7.88)" \
    "supervisor (>=4.2)" \
    "unzip (>=6.0)" \
    "vim-tiny (>=2)" \
  && apt-get install -y nodejs npm \
  && npm install -g npm@7  \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*

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

RUN install-php-extensions \
    bcmath \
    bz2 \
    curl \
    exif \
    gd \
    intl \
    pcntl \
    pdo_pgsql \
    opcache \
    redis \
    sockets \
    calendar\
    zip

RUN composer install
RUN npm install

USER ${user}

RUN chmod -R a+rw storage
    
ENTRYPOINT ["/laravel/entrypoint.sh"]