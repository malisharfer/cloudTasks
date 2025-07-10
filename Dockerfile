# הגדרת התמונה הבסיסית
FROM php:8.1-fpm

# עדכון כלים בסיסיים
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    curl \
    git \
    && rm -rf /var/lib/apt/lists/*

# התקנה של הרחבות PHP דרושות
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip

# התקנת Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# התקנת Node.js ו-NPM
RUN curl -sL https://deb.nodesource.com/setup_16.x | bash - \
    && apt-get install -y nodejs

# התקנה של frankenphp מתוך GitHub, ישירות ללא gzip
RUN curl -sSL https://github.com/laravel/octane/releases/download/v2.6/frankenphp-linux-x86_64 -o /usr/local/bin/frankenphp && \
    chmod +x /usr/local/bin/frankenphp

# התקנת Laravel Octane
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer global require laravel/octane

# התקנה של NPM dependencies
WORKDIR /var/www/html
RUN npm install

# תיקון הרשאות
RUN chmod -R a+rw storage

# פתיחה של פורט 9000
EXPOSE 9000

# הגדרת הפקודה הראשונית
CMD ["php-fpm"]