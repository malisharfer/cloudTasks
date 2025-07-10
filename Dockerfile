# הגדרת גרסת PHP
ARG php_version=8.3

# שימוש בתמונה של FrankenPHP
FROM dunglas/frankenphp:1.1-php${php_version} AS base

WORKDIR /laravel

# השתמש ב-Bash בתור shell
SHELL ["/bin/bash", "-eou", "pipefail", "-c"]

# הגדרת משתנים כלליים
ENV SERVER_NAME=:80
ARG user=laravel

# העתקת קבצים
COPY ./ /laravel
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY --chmod=755 /entrypoint.sh entrypoint.sh
COPY --chmod=755 /common common
COPY --chown=${user}:${user} /artisan artisan
COPY .env.example .env
COPY /php.ini "${PHP_INI_DIR}/php.ini"

# התקנת התלויות והגדרת PHP
RUN php --ini \
 && php -r "echo 'max_execution_time: ' . ini_get('max_execution_time') . PHP_EOL;" \
 && apt-get update \
 && apt-get install -y \
    curl \
    supervisor \
    unzip \
    vim-tiny \
    nodejs \
    npm \
    sudo \
 && npm install -g npm@7 \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

# התקנה של גרסה חדשה של frankenphp (לא לחכות להורדה בזמן ההפעלה)
RUN curl -sSL https://github.com/laravel/octane/releases/download/v2.6/frankenphp-linux-x86_64.tar.gz | tar -xz -C /usr/local/bin

# יצירת משתמש חדש עם UID 1000
RUN useradd --uid 1000 --shell /bin/bash "${user}" \
  && setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp \
  && chmod +x /usr/local/bin/frankenphp \
  && chmod -R 755 /usr/local/bin \
  && chown -R "${user}:${user}" \
    /laravel \
    /data/caddy \
    /config/caddy \
    /var/{log,run} \
  && chmod -R a+rw /var/{log,run}

# התקנת הרחבות PHP
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

# התקנת תלות Composer
RUN composer install

# התקנת תלות npm
RUN npm install

# החלפת הרשאות לתיקיית storage
USER ${user}
RUN chmod -R a+rw storage

# נקודת התחלה - ENTRYPOINT
ENTRYPOINT ["/laravel/entrypoint.sh"]