# הגדרת גרסת PHP
ARG php_version=8.3

# שלב הבסיס עם גרסת FrankenPHP
FROM dunglas/frankenphp:1.1-php${php_version} AS base

WORKDIR /laravel
SHELL ["/bin/bash", "-eou", "pipefail", "-c"]

ENV SERVER_NAME=:80
ARG user=laravel

# העתקת קבצים חשובים לאפליקציה
COPY ./ /laravel
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY --chmod=755 /entrypoint.sh entrypoint.sh
COPY --chmod=755 /common common
COPY --chown=${user}:${user} /artisan artisan
COPY .env.example .env
COPY /php.ini "${PHP_INI_DIR}/php.ini"
RUN php --ini \
 && php -r "echo 'max_execution_time: ' . ini_get('max_execution_time') . PHP_EOL;"

# התקנה של תלותים נוספות
RUN apt-get update \
  && apt-get satisfy -y --no-install-recommends \
    "curl (>=7.88)" \
    "supervisor (>=4.2)" \
    "unzip (>=6.0)" \
    "vim-tiny (>=2)" \
  && apt-get install -y nodejs npm \
  && npm install -g npm@7  \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*

# יצירת משתמש חדש עם UID 1000
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

# התקנה של הרחבות PHP הדרושות
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

# התקנה של Composer ו-NPM
RUN composer install
RUN npm install

# שינוי הרשאות על תיקיית storage
RUN chmod -R a+rw storage

# התקנה אוטומטית של FrankenPHP מתוך GitHub
RUN curl -sSL https://github.com/laravel/octane/releases/download/v2.6/frankenphp-linux-x86_64.tar.gz -o /tmp/frankenphp.tar.gz && \
    mkdir -p /usr/local/bin && \
    tar -xzvf /tmp/frankenphp.tar.gz -C /usr/local/bin && \
    chmod +x /usr/local/bin/frankenphp && \
    rm /tmp/frankenphp.tar.gz

# שינוי למשתמש הלאראבל
USER ${user}

# שינוי הרשאות על תיקיית storage
RUN chmod -R a+rw storage

# הגדרת EntryPoint
ENTRYPOINT ["/laravel/entrypoint.sh"]