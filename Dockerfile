# ARG php_version=8.3

# FROM dunglas/frankenphp:1.1-php${php_version} AS base

# WORKDIR /laravel
# SHELL ["/bin/bash", "-eou", "pipefail", "-c"]

# ENV SERVER_NAME=:80
# ARG user=laravel

# COPY ./ /laravel
# COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
# COPY --chmod=755 /entrypoint.sh entrypoint.sh
# COPY --chmod=755 /common common
# COPY --chown=${user}:${user} /artisan artisan
# COPY .env.example .env
# COPY /php.ini "${PHP_INI_DIR}/php.ini"


# RUN apt-get update \
#   && apt-get install -y lsb-release apt-transport-https wget gnupg \
#   && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - \
#   && echo "deb https://packages.sury.org/php/ $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list \
#   && apt-get update \
#   && apt-get install -y php8.1 php8.1-mbstring php8.1-dev \
#   && apt-get satisfy -y --no-install-recommends \
#     "curl (>=7.88)" \
#     "supervisor (>=4.2)" \
#     "unzip (>=6.0)" \
#     "vim-tiny (>=2)" \
#   && apt-get install -y nodejs npm \
#   && npm install -g npm@7  \
#   && apt-get clean \
#   && rm -rf /var/lib/apt/lists/*

# RUN useradd \
#     --uid 1000 \
#     --shell /bin/bash \
#     "${user}" \
#   && setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp \
#   && chown -R "${user}:${user}" \
#     /laravel \
#     /data/caddy \
#     /config/caddy \
#     /var/{log,run} \
#   && chmod -R a+rw \
#     /var/{log,run}

# RUN install-php-extensions \
#     bcmath \
#     bz2 \
#     curl \
#     exif \
#     gd \
#     intl \
#     pcntl \
#     pdo_pgsql \
#     opcache \
#     redis \
#     sockets \
#     calendar\
#     zip

# RUN composer install
# RUN npm install

# USER ${user}

# RUN chmod -R a+rw storage
    
# ENTRYPOINT ["/laravel/entrypoint.sh"]

השגיאה שאתה נתקל בה נובעת מכך שהפקודה add-apt-repository לא מצליחה לגשת למידע הנדרש כדי להוסיף את ה-PPA. זה יכול לקרות כאשר יש בעיה עם חבילות או שה-PPA לא תואם לגרסה של מערכת ההפעלה שלך.

לחלופין, תוכל לנסות להוסיף את ה-PPA ישירות על ידי עריכת קובץ המקורות של apt במקום להשתמש ב-add-apt-repository. הנה דוגמה כיצד לעשות זאת:

RUN apt-get update \
  && apt-get install -y lsb-release apt-transport-https wget gnupg \
  && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - \
  && echo "deb https://packages.sury.org/php/ $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list \
  && echo "deb http://ppa.launchpad.net/ondrej/php/ubuntu $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/ondrej-php.list \
  && apt-get update \
  && apt-get install -y php8.2 php8.2-mbstring php8.2-dev \
  && apt-get install -y --no-install-recommends \
    curl \
    supervisor \
    unzip \
    vim-tiny \
  && apt-get install -y nodejs npm \
  && npm install -g npm@7  \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*
בשיטה זו, אנו מוסיפים את ה-PPA ישירות לקובץ המקורות, מה שמאפשר לעקוף את הבעיה עם add-apt-repository.

נסה להריץ את הקוד המתוקן הזה וראה אם הבעיה נפתרת.

#16 5.059 E: The repository 'http://ppa.launchpad.net/ondrej/php/ubuntu bookworm Release' does not have a Release file.
#16 ERROR: process "/bin/bash -eou pipefail -c apt-get update && apt-get install -y lsb-release apt-transport-https wget gnupg && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - && echo "deb https://packages.sury.org/php/ (lsb_release -cs) main\" | tee /etc/apt/sources.list.d/php.list && echo \"deb http://ppa.launchpad.net/ondrej/php/ubuntu (lsb_release -cs) main" | tee /etc/apt/sources.list.d/ondrej-php.list && apt-get update && apt-get install -y php8.2 php8.2-mbstring php8.2-dev && apt-get satisfy -y --no-install-recommends "curl (>=7.88)" "supervisor (>=4.2)" "unzip (>=6.0)" "vim-tiny (>=2)" && apt-get install -y nodejs npm && npm install -g npm@7 && apt-get clean && rm -rf /var/lib/apt/lists/*" did not complete successfully: exit code: 100
[base 10/15] RUN apt-get update && apt-get install -y lsb-release apt-transport-https wget gnupg && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - && echo "deb https://packages.sury.org/php/ (lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list && echo "deb http://ppa.launchpad.net/ondrej/php/ubuntu (lsb_release -cs) main" | tee /etc/apt/sources.list.d/ondrej-php.list && apt-get update && apt-get install -y php8.2 php8.2-mbstring php8.2-dev && apt-get satisfy -y --no-install-recommends "curl (>=7.88)" "supervisor (>=4.2)" "unzip (>=6.0)" "vim-tiny (>=2)" && apt-get install -y nodejs npm && npm install -g npm@7 && apt-get clean && rm -rf /var/lib/apt/lists/*:
4.214 deb https://packages.sury.org/php/ bookworm main
4.222 deb http://ppa.launchpad.net/ondrej/php/ubuntu bookworm main
4.264 Hit:1 http://deb.debian.org/debian bookworm InRelease
4.264 Hit:2 http://deb.debian.org/debian bookworm-updates InRelease
4.264 Hit:3 http://deb.debian.org/debian-security bookworm-security InRelease
4.269 Get:4 https://packages.sury.org/php bookworm InRelease [7551 B]
4.528 Ign:5 http://ppa.launchpad.net/ondrej/php/ubuntu bookworm InRelease
5.059 W: https://packages.sury.org/php/dists/bookworm/InRelease: Key is stored in legacy trusted.gpg keyring (/etc/apt/trusted.gpg), see the DEPRECATION section in apt-key(8) for details.
5.059 E: The repository 'http://ppa.launchpad.net/ondrej/php/ubuntu bookworm Release' does not have a Release file.

Dockerfile:20
19 |
20 | >>> RUN apt-get update
21 | >>> && apt-get install -y lsb-release apt-transport-https wget gnupg
22 | >>> && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add -
23 | >>> && echo "deb https://packages.sury.org/php/ (lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list \ 24 | >>> && echo "deb http://ppa.launchpad.net/ondrej/php/ubuntu (lsb_release -cs) main" | tee /etc/apt/sources.list.d/ondrej-php.list
25 | >>> && apt-get update
26 | >>> && apt-get install -y php8.2 php8.2-mbstring php8.2-dev
27 | >>> && apt-get satisfy -y --no-install-recommends
28 | >>> "curl (>=7.88)"
29 | >>> "supervisor (>=4.2)"
30 | >>> "unzip (>=6.0)"
31 | >>> "vim-tiny (>=2)"
32 | >>> && apt-get install -y nodejs npm
33 | >>> && npm install -g npm@7
34 | >>> && apt-get clean
35 | >>> && rm -rf /var/lib/apt/lists/*
36 |
ERROR: failed to solve: process "/bin/bash -eou pipefail -c apt-get update && apt-get install -y lsb-release apt-transport-https wget gnupg && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - && echo "deb https://packages.sury.org/php/ (lsb_release -cs) main\" | tee /etc/apt/sources.list.d/php.list && echo \"deb http://ppa.launchpad.net/ondrej/php/ubuntu (lsb_release -cs) main" | tee /etc/apt/sources.list.d/ondrej-php.list && apt-get update && apt-get install -y php8.2 php8.2-mbstring php8.2-dev && apt-get satisfy -y --no-install-recommends "curl (>=7.88)" "supervisor (>=4.2)" "unzip (>=6.0)" "vim-tiny (>=2)" && apt-get install -y nodejs npm && npm install -g npm@7 && apt-get clean && rm -rf /var/lib/apt/lists/" did not complete successfully: exit code: 100
Error: buildx failed with: ERROR: failed to solve: process "/bin/bash -eou pipefail -c apt-get update && apt-get install -y lsb-release apt-transport-https wget gnupg && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - && echo "deb https://packages.sury.org/php/ (lsb_release -cs) main\" | tee /etc/apt/sources.list.d/php.list && echo \"deb http://ppa.launchpad.net/ondrej/php/ubuntu (lsb_release -cs) main" | tee /etc/apt/sources.list.d/ondrej-php.list && apt-get update && apt-get install -y php8.2 php8.2-mbstring php8.2-dev && apt-get satisfy -y --no-install-recommends "curl (>=7.88)" "supervisor (>=4.2)" "unzip (>=6.0)" "vim-tiny (>=2)" && apt-get install -y nodejs npm && npm install -g npm@7 && apt-get clean && rm -rf /var/lib/apt/lists/" did not complete successfully: exit code: 100

השגיאה "The repository 'http://ppa.launchpad.net/ondrej/php/ubuntu bookworm Release' does not have a Release file" מצביעה על כך שה-PPA לא תומך בגרסה של מערכת ההפעלה שלך, במקרה זה "bookworm".

כדי להתקין PHP 8.2 על Debian Bookworm, אתה יכול להשתמש במאגר של deb.sury.org בלבד מבלי לנסות להוסיף את ה-PPA. הנה דוגמה מעודכנת של ה-Dockerfile שלך:

RUN apt-get update \
  && apt-get install -y lsb-release apt-transport-https wget gnupg \
  && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - \
  && echo "deb https://packages.sury.org/php/ $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list \
  && apt-get update \
  && apt-get install -y php8.2 php8.2-mbstring php8.2-dev \
  && apt-get install -y --no-install-recommends \
    curl \
    supervisor \
    unzip \
    vim-tiny \
  && apt-get install -y nodejs npm \
  && npm install -g npm@7  \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*
בשיטה זו, אנו משתמשים רק במאגר של deb.sury.org ולא מנסים להוסיף את ה-PPA של ondrej.

נסה להריץ את הקוד הזה וראה אם הבעיה נפתרת.

#16 5.554 E: Package 'php8.2' has no installation candidate
#16 5.554 E: Package 'php8.2-mbstring' has no installation candidate
#16 5.554 E: Package 'php8.2-dev' has no installation candidate
#16 ERROR: process "/bin/bash -eou pipefail -c apt-get update && apt-get install -y lsb-release apt-transport-https wget gnupg && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - && echo "deb https://packages.sury.org/php/ $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list && apt-get update && apt-get install -y php8.2 php8.2-mbstring php8.2-dev && apt-get satisfy -y --no-install-recommends "curl (>=7.88)" "supervisor (>=4.2)" "unzip (>=6.0)" "vim-tiny (>=2)" && apt-get install -y nodejs npm && npm install -g npm@7 && apt-get clean && rm -rf /var/lib/apt/lists/*" did not complete successfully: exit code: 100
[base 10/15] RUN apt-get update && apt-get install -y lsb-release apt-transport-https wget gnupg && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - && echo "deb https://packages.sury.org/php/ $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list && apt-get update && apt-get install -y php8.2 php8.2-mbstring php8.2-dev && apt-get satisfy -y --no-install-recommends "curl (>=7.88)" "supervisor (>=4.2)" "unzip (>=6.0)" "vim-tiny (>=2)" && apt-get install -y nodejs npm && npm install -g npm@7 && apt-get clean && rm -rf /var/lib/apt/lists/*:
5.552 This may mean that the package is missing, has been obsoleted, or
5.552 is only available from another source
5.552
5.552 Package php8.2-mbstring is not available, but is referred to by another package.
5.552 This may mean that the package is missing, has been obsoleted, or
5.552 is only available from another source
5.552
5.554 E: Package 'php8.2' has no installation candidate
5.554 E: Package 'php8.2-mbstring' has no installation candidate
5.554 E: Package 'php8.2-dev' has no installation candidate

Dockerfile:20
19 |
20 | >>> RUN apt-get update
21 | >>> && apt-get install -y lsb-release apt-transport-https wget gnupg
22 | >>> && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add -
23 | >>> && echo "deb https://packages.sury.org/php/ $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list
24 | >>> && apt-get update
25 | >>> && apt-get install -y php8.2 php8.2-mbstring php8.2-dev
26 | >>> && apt-get satisfy -y --no-install-recommends
27 | >>> "curl (>=7.88)"
28 | >>> "supervisor (>=4.2)"
29 | >>> "unzip (>=6.0)"
30 | >>> "vim-tiny (>=2)"
31 | >>> && apt-get install -y nodejs npm
32 | >>> && npm install -g npm@7
33 | >>> && apt-get clean
34 | >>> && rm -rf /var/lib/apt/lists/*
35 |
ERROR: failed to solve: process "/bin/bash -eou pipefail -c apt-get update && apt-get install -y lsb-release apt-transport-https wget gnupg && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - && echo "deb https://packages.sury.org/php/ (lsb_release -cs) main\" | tee /etc/apt/sources.list.d/php.list && apt-get update && apt-get install -y php8.2 php8.2-mbstring php8.2-dev && apt-get satisfy -y --no-install-recommends \"curl (>=7.88)\" \"supervisor (>=4.2)\" \"unzip (>=6.0)\" \"vim-tiny (>=2)\" && apt-get install -y nodejs npm && npm install -g npm@7 && apt-get clean && rm -rf /var/lib/apt/lists/*" did not complete successfully: exit code: 100 Error: buildx failed with: ERROR: failed to solve: process "/bin/bash -eou pipefail -c apt-get update && apt-get install -y lsb-release apt-transport-https wget gnupg && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - && echo \"deb https://packages.sury.org/php/ (lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list && apt-get update && apt-get install -y php8.2 php8.2-mbstring php8.2-dev && apt-get satisfy -y --no-install-recommends "curl (>=7.88)" "supervisor (>=4.2)" "unzip (>=6.0)" "vim-tiny (>=2)" && apt-get install -y nodejs npm && npm install -g npm@7 && apt-get clean && rm -rf /var/lib/apt/lists/*" did not complete successfully: exit code: 100

השגיאה "Package 'php8.2' has no installation candidate" מצביעה על כך שהחבילות PHP 8.2 אינן זמינות במאגר שהגדרת. זה יכול לקרות אם הגרסה של Debian שלך (Bookworm) אינה נתמכת על ידי המאגר של deb.sury.org.

כדי להתקין PHP 8.2 על Debian Bookworm, תוכל לנסות את השיטות הבאות:

שימוש במאגר של Debian: ודא שאתה משתמש במאגר המתאים לגרסה שלך. תוכל לבדוק אם יש גרסאות אחרות של PHP זמינות במאגר הרשמי של Debian.

התקנת PHP מ-PPA אחר: אם אתה זקוק לגרסה ספציפית של PHP, תוכל לשקול להשתמש ב-PPA אחר או במאגר אחר שמתאים ל-Debian Bookworm.

התקנת PHP מ-Source: אם כל השיטות האחרות נכשלות, תוכל להוריד את הקוד המקורי של PHP 8.2 ולהתקין אותו ידנית.

להלן דוגמה כיצד להתקין PHP 8.2 מ-PPA אחר (אם קיים):

RUN apt-get update \
  && apt-get install -y lsb-release apt-transport-https wget gnupg \
  && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - \
  && echo "deb https://packages.sury.org/php/ $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list \
  && apt-get update \
  && apt-get install -y php8.1 php8.1-mbstring php8.1-dev \  # שים לב שהגרסה שונה כאן
  && apt-get install -y --no-install-recommends \
    curl \
    supervisor \
    unzip \
    vim-tiny \
  && apt-get install -y nodejs npm \
  && npm install -g npm@7  \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*
במקרה זה, אנחנו מתקינים את PHP 8.1 במקום 8.2. תוודא שהגרסה שאתה צריך זמינה במאגר.

אם אתה מתעקש על PHP 8.2, ייתכן שתצטרך לבדוק את המצב של המאגר או לחפש פתרונות חלופיים.

#15 [base 9/15] COPY /php.ini /usr/local/etc/php/php.ini
#15 DONE 0.0s
#16 [base 10/15] RUN apt-get update RUN apt-get update && apt-get install -y lsb-release apt-transport-https wget gnupg && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - && echo "deb https://packages.sury.org/php/ (lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list && apt-get update && apt-get install -y php8.1 php8.1-mbstring php8.1-dev && apt-get satisfy -y --no-install-recommends "curl (>=7.88)" "supervisor (>=4.2)" "unzip (>=6.0)" "vim-tiny (>=2)" && apt-get install -y nodejs npm && npm install -g npm@7 && apt-get clean && rm -rf /var/lib/apt/lists/* #16 0.110 E: The update command takes no arguments #16 ERROR: process "/bin/bash -eou pipefail -c apt-get update RUN apt-get update && apt-get install -y lsb-release apt-transport-https wget gnupg && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - && echo \"deb https://packages.sury.org/php/ (lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list && apt-get update && apt-get install -y php8.1 php8.1-mbstring php8.1-dev && apt-get satisfy -y --no-install-recommends "curl (>=7.88)" "supervisor (>=4.2)" "unzip (>=6.0)" "vim-tiny (>=2)" && apt-get install -y nodejs npm && npm install -g npm@7 && apt-get clean && rm -rf /var/lib/apt/lists/*" did not complete successfully: exit code: 100
[base 10/15] RUN apt-get update RUN apt-get update && apt-get install -y lsb-release apt-transport-https wget gnupg && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - && echo "deb https://packages.sury.org/php/ $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list && apt-get update && apt-get install -y php8.1 php8.1-mbstring php8.1-dev && apt-get satisfy -y --no-install-recommends "curl (>=7.88)" "supervisor (>=4.2)" "unzip (>=6.0)" "vim-tiny (>=2)" && apt-get install -y nodejs npm && npm install -g npm@7 && apt-get clean && rm -rf /var/lib/apt/lists/*:
0.110 E: The update command takes no arguments

Dockerfile:20
19 |
20 | >>> RUN apt-get update
21 | >>> RUN apt-get update
22 | >>> && apt-get install -y lsb-release apt-transport-https wget gnupg
23 | >>> && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add -
24 | >>> && echo "deb https://packages.sury.org/php/ $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list
25 | >>> && apt-get update
26 | >>> && apt-get install -y php8.1 php8.1-mbstring php8.1-dev \
27 | >>> && apt-get satisfy -y --no-install-recommends
28 | >>> "curl (>=7.88)"
29 | >>> "supervisor (>=4.2)"
30 | >>> "unzip (>=6.0)"
31 | >>> "vim-tiny (>=2)"
32 | >>> && apt-get install -y nodejs npm
33 | >>> && npm install -g npm@7
34 | >>> && apt-get clean
35 | >>> && rm -rf /var/lib/apt/lists/*
36 |
ERROR: failed to solve: process "/bin/bash -eou pipefail -c apt-get update RUN apt-get update && apt-get install -y lsb-release apt-transport-https wget gnupg && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - && echo "deb https://packages.sury.org/php/ (lsb_release -cs) main\" | tee /etc/apt/sources.list.d/php.list && apt-get update && apt-get install -y php8.1 php8.1-mbstring php8.1-dev && apt-get satisfy -y --no-install-recommends \"curl (>=7.88)\" \"supervisor (>=4.2)\" \"unzip (>=6.0)\" \"vim-tiny (>=2)\" && apt-get install -y nodejs npm && npm install -g npm@7 && apt-get clean && rm -rf /var/lib/apt/lists/*" did not complete successfully: exit code: 100 Error: buildx failed with: ERROR: failed to solve: process "/bin/bash -eou pipefail -c apt-get update RUN apt-get update && apt-get install -y lsb-release apt-transport-https wget gnupg && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - && echo \"deb https://packages.sury.org/php/ (lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list && apt-get update && apt-get install -y php8.1 php8.1-mbstring php8.1-dev && apt-get satisfy -y --no-install-recommends "curl (>=7.88)" "supervisor (>=4.2)" "unzip (>=6.0)" "vim-tiny (>=2)" && apt-get install -y nodejs npm && npm install -g npm@7 && apt-get clean && rm -rf /var/lib/apt/lists/*" did not complete successfully: exit code: 100

השגיאה "The update command takes no arguments" נובעת מכך שאתה מנסה להריץ את הפקודה apt-get update פעמיים באותה שורה, מה שגורם לבעיה.

כדי לתקן את ה-Dockerfile שלך, הסר את השורה השנייה של apt-get update כך שהפקודות יהיו ברצף נכון. הנה הגרסה המתוקנת:

RUN apt-get update \
  && apt-get install -y lsb-release apt-transport-https wget gnupg \
  && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - \
  && echo "deb https://packages.sury.org/php/ $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list \
  && apt-get update \
  && apt-get install -y php8.1 php8.1-mbstring php8.1-dev \
  && apt-get install -y --no-install-recommends \
    curl \
    supervisor \
    unzip \
    vim-tiny \
  && apt-get install -y nodejs npm \
  && npm install -g npm@7  \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*
שנה את השורה של RUN כך שתכיל רק פקודה אחת של apt-get update והמשך עם שאר הפקודות. נסה להריץ את הקוד הזה וראה אם הבעיה נפתרת.

#16 5.541 E: Package 'php8.1' has no installation candidate
#16 5.541 E: Package 'php8.1-mbstring' has no installation candidate
#16 5.541 E: Package 'php8.1-dev' has no installation candidate
#16 ERROR: process "/bin/bash -eou pipefail -c apt-get update && apt-get install -y lsb-release apt-transport-https wget gnupg && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - && echo "deb https://packages.sury.org/php/ $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list && apt-get update && apt-get install -y php8.1 php8.1-mbstring php8.1-dev && apt-get satisfy -y --no-install-recommends "curl (>=7.88)" "supervisor (>=4.2)" "unzip (>=6.0)" "vim-tiny (>=2)" && apt-get install -y nodejs npm && npm install -g npm@7 && apt-get clean && rm -rf /var/lib/apt/lists/*" did not complete successfully: exit code: 100
[base 10/15] RUN apt-get update && apt-get install -y lsb-release apt-transport-https wget gnupg && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - && echo "deb https://packages.sury.org/php/ $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list && apt-get update && apt-get install -y php8.1 php8.1-mbstring php8.1-dev && apt-get satisfy -y --no-install-recommends "curl (>=7.88)" "supervisor (>=4.2)" "unzip (>=6.0)" "vim-tiny (>=2)" && apt-get install -y nodejs npm && npm install -g npm@7 && apt-get clean && rm -rf /var/lib/apt/lists/*:
5.540 This may mean that the package is missing, has been obsoleted, or
5.540 is only available from another source
5.540
5.540 Package php8.1 is not available, but is referred to by another package.
5.540 This may mean that the package is missing, has been obsoleted, or
5.540 is only available from another source
5.540
5.541 E: Package 'php8.1' has no installation candidate
5.541 E: Package 'php8.1-mbstring' has no installation candidate
5.541 E: Package 'php8.1-dev' has no installation candidate

Dockerfile:20
19 |
20 | >>> RUN apt-get update
21 | >>> && apt-get install -y lsb-release apt-transport-https wget gnupg
22 | >>> && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add -
23 | >>> && echo "deb https://packages.sury.org/php/ $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list
24 | >>> && apt-get update
25 | >>> && apt-get install -y php8.1 php8.1-mbstring php8.1-dev
26 | >>> && apt-get satisfy -y --no-install-recommends
27 | >>> "curl (>=7.88)"
28 | >>> "supervisor (>=4.2)"
29 | >>> "unzip (>=6.0)"
30 | >>> "vim-tiny (>=2)"
31 | >>> && apt-get install -y nodejs npm
32 | >>> && npm install -g npm@7
33 | >>> && apt-get clean
34 | >>> && rm -rf /var/lib/apt/lists/*
35 |
ERROR: failed to solve: process "/bin/bash -eou pipefail -c apt-get update && apt-get install -y lsb-release apt-transport-https wget gnupg && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - && echo "deb https://packages.sury.org/php/ (lsb_release -cs) main\" | tee /etc/apt/sources.list.d/php.list && apt-get update && apt-get install -y php8.1 php8.1-mbstring php8.1-dev && apt-get satisfy -y --no-install-recommends \"curl (>=7.88)\" \"supervisor (>=4.2)\" \"unzip (>=6.0)\" \"vim-tiny (>=2)\" && apt-get install -y nodejs npm && npm install -g npm@7 && apt-get clean && rm -rf /var/lib/apt/lists/*" did not complete successfully: exit code: 100 Error: buildx failed with: ERROR: failed to solve: process "/bin/bash -eou pipefail -c apt-get update && apt-get install -y lsb-release apt-transport-https wget gnupg && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - && echo \"deb https://packages.sury.org/php/ (lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list && apt-get update && apt-get install -y php8.1 php8.1-mbstring php8.1-dev && apt-get satisfy -y --no-install-recommends "curl (>=7.88)" "supervisor (>=4.2)" "unzip (>=6.0)" "vim-tiny (>=2)" && apt-get install -y nodejs npm && npm install -g npm@7 && apt-get clean && rm -rf /var/lib/apt/lists/*" did not complete successfully: exit code: 100

השגיאה "Package 'php8.1' has no installation candidate" מצביעה על כך שהחבילות PHP 8.1 אינן זמינות במאגר שהגדרת. זה יכול להתרחש אם הגרסה של מערכת ההפעלה שלך אינה נתמכת על ידי המאגר של deb.sury.org.

כדי לפתור את הבעיה, יש מספר צעדים שאפשר לנקוט:

ודא שהמאגר נכון: ודא שאתה משתמש במאגר המתאים לגרסה שלך של Debian. ייתכן שהגרסה שלך לא תומכת ב-PHP 8.1.

שימוש במאגר אחר: אם PHP 8.1 לא זמין, תוכל לשקול להתקין גרסה אחרת של PHP (כמו 8.0 או 7.4) או לחפש מאגר חלופי.

התקנה ידנית: אם אתה זקוק לגרסה ספציפית של PHP, תוכל לשקול להוריד את הקוד המקורי ולהתקין אותו ידנית.

כדי לבדוק אילו גרסאות של PHP זמינות, תוכל להריץ את הפקודה הבאה:

apt-cache search php | grep php
אם אתה רוצה להתקין גרסה אחרת של PHP, עדכן את השורות הרלוונטיות ב-Dockerfile שלך. לדוגמה, אם תבחר ב-PHP 8.0:

RUN apt-get update \
  && apt-get install -y lsb-release apt-transport-https wget gnupg \
  && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - \
  && echo "deb https://packages.sury.org/php/ $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list \
  && apt-get update \
  && apt-get install -y php8.0 php8.0-mbstring php8.0-dev \
  && apt-get install -y --no-install-recommends \
    curl \
    supervisor \
    unzip \
    vim-tiny \
  && apt-get install -y nodejs npm \
  && npm install -g npm@7  \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*
ודא שהגרסה שאתה בוחר זמינה במאגר.

אני מריצה את הדוקפיל הזה ב github action על ubuntu-latest

אם אתה מריץ את ה-Dockerfile ב-GitHub Actions על ubuntu-latest, יש לוודא שהמאגר של PHP תואם לגרסה של Ubuntu.

באופן כללי, ubuntu-latest מתעדכן לגרסה העדכנית ביותר של Ubuntu, ולכן ייתכן ש-PHP 8.1 לא יהיה זמין במאגר שהגדרת.

כדי להתקין PHP 8.1 על Ubuntu, תוכל להשתמש במאגר ondrej/php. הנה דוגמה ל-Dockerfile מעודכן:

FROM ubuntu:latest

RUN apt-get update \
  && apt-get install -y lsb-release apt-transport-https wget gnupg \
  && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - \
  && echo "deb https://packages.sury.org/php/ $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list \
  && apt-get update \
  && apt-get install -y php8.1 php8.1-mbstring php8.1-dev \
  && apt-get install -y --no-install-recommends \
    curl \
    supervisor \
    unzip \
    vim-tiny \
  && apt-get install -y nodejs npm \
  && npm install -g npm@7 \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*
אם אתה נתקל בבעיות עם החבילות, תוכל לשקול להתקין גרסה אחרת של PHP או להשתמש במאגר אחר. ודא שהמאגר שאתה מוסיף תואם לגרסה של Ubuntu שבה אתה משתמש ב-GitHub Actions.

אבל זה מביא לי את השגיאה הזו :
#16 5.541 E: Package 'php8.1' has no installation candidate
#16 5.541 E: Package 'php8.1-mbstring' has no installation candidate
#16 5.541 E: Package 'php8.1-dev' has no installation candidate
#16 ERROR: process "/bin/bash -eou pipefail -c apt-get update && apt-get install -y lsb-release apt-transport-https wget gnupg && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - && echo "deb https://packages.sury.org/php/ $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list && apt-get update && apt-get install -y php8.1 php8.1-mbstring php8.1-dev && apt-get satisfy -y --no-install-recommends "curl (>=7.88)" "supervisor (>=4.2)" "unzip (>=6.0)" "vim-tiny (>=2)" && apt-get install -y nodejs npm && npm install -g npm@7 && apt-get clean && rm -rf /var/lib/apt/lists/*" did not complete successfully: exit code: 100
[base 10/15] RUN apt-get update && apt-get install -y lsb-release apt-transport-https wget gnupg && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - && echo "deb https://packages.sury.org/php/ $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list && apt-get update && apt-get install -y php8.1 php8.1-mbstring php8.1-dev && apt-get satisfy -y --no-install-recommends "curl (>=7.88)" "supervisor (>=4.2)" "unzip (>=6.0)" "vim-tiny (>=2)" && apt-get install -y nodejs npm && npm install -g npm@7 && apt-get clean && rm -rf /var/lib/apt/lists/*:
5.540 This may mean that the package is missing, has been obsoleted, or
5.540 is only available from another source
5.540
5.540 Package php8.1 is not available, but is referred to by another package.
5.540 This may mean that the package is missing, has been obsoleted, or
5.540 is only available from another source
5.540
5.541 E: Package 'php8.1' has no installation candidate
5.541 E: Package 'php8.1-mbstring' has no installation candidate
5.541 E: Package 'php8.1-dev' has no installation candidate

Dockerfile:20
19 |
20 | >>> RUN apt-get update
21 | >>> && apt-get install -y lsb-release apt-transport-https wget gnupg
22 | >>> && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add -
23 | >>> && echo "deb https://packages.sury.org/php/ $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list
24 | >>> && apt-get update
25 | >>> && apt-get install -y php8.1 php8.1-mbstring php8.1-dev
26 | >>> && apt-get satisfy -y --no-install-recommends
27 | >>> "curl (>=7.88)"
28 | >>> "supervisor (>=4.2)"
29 | >>> "unzip (>=6.0)"
30 | >>> "vim-tiny (>=2)"
31 | >>> && apt-get install -y nodejs npm
32 | >>> && npm install -g npm@7
33 | >>> && apt-get clean
34 | >>> && rm -rf /var/lib/apt/lists/*
35 |
ERROR: failed to solve: process "/bin/bash -eou pipefail -c apt-get update && apt-get install -y lsb-release apt-transport-https wget gnupg && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - && echo "deb https://packages.sury.org/php/ (lsb_release -cs) main\" | tee /etc/apt/sources.list.d/php.list && apt-get update && apt-get install -y php8.1 php8.1-mbstring php8.1-dev && apt-get satisfy -y --no-install-recommends \"curl (>=7.88)\" \"supervisor (>=4.2)\" \"unzip (>=6.0)\" \"vim-tiny (>=2)\" && apt-get install -y nodejs npm && npm install -g npm@7 && apt-get clean && rm -rf /var/lib/apt/lists/*" did not complete successfully: exit code: 100 Error: buildx failed with: ERROR: failed to solve: process "/bin/bash -eou pipefail -c apt-get update && apt-get install -y lsb-release apt-transport-https wget gnupg && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - && echo \"deb https://packages.sury.org/php/ (lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list && apt-get update && apt-get install -y php8.1 php8.1-mbstring php8.1-dev && apt-get satisfy -y --no-install-recommends "curl (>=7.88)" "supervisor (>=4.2)" "unzip (>=6.0)" "vim-tiny (>=2)" && apt-get install -y nodejs npm && npm install -g npm@7 && apt-get clean && rm -rf /var/lib/apt/lists/*" did not complete successfully: exit code: 100

כדי להתקין PHP 8.1 ב-GitHub Actions על ubuntu-latest, תוכל לנסות את השינויים הבאים ב-Dockerfile שלך:

השתמש במאגר הנכון: ודא שאתה משתמש במאגר של ondrej/php, שהוא המאגר הנפוץ ביותר עבור PHP ב-Ubuntu.

התקן את PHP 8.1: ודא שהפקודות שלך מעודכנות.

הנה דוגמה ל-Dockerfile מעודכן:

FROM ubuntu:latest

RUN apt-get update \
  && apt-get install -y lsb-release apt-transport-https wget gnupg \
  && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - \
  && echo "deb https://packages.sury.org/php/ $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list \
  && apt-get update \
  && apt-get install -y php8.1 php8.1-mbstring php8.1-dev \
  && apt-get install -y --no-install-recommends \
    curl \
    supervisor \
    unzip \
    vim-tiny \
  && apt-get install -y nodejs npm \
  && npm install -g npm@7 \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*
אם אתה עדיין נתקל בבעיות, ייתכן שהגרסה של Ubuntu הנוכחית לא תומכת ב-PHP 8.1 דרך המאגר הזה. במקרה כזה, תוכל לנסות להחליף את $(lsb_release -cs) בגרסה ספציפית של Ubuntu, כמו focal (Ubuntu 20.04) או jammy (Ubuntu 22.04), לדוגמה:

echo "deb https://packages.sury.org/php/ focal main" | tee /etc/apt/sources.list.d/php.list
ודא שאתה בודק את התאימות של הגרסאות כדי להבטיח שהן תואמות לגרסה של Ubuntu שבה אתה משתמש.

זה הדוקר פיל שלי :
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
COPY --chown=
u
s
e
r
:
user:{user} /artisan artisan
COPY .env.example .env
COPY /php.ini "${PHP_INI_DIR}/php.ini"

RUN apt-get update
&& apt-get install -y lsb-release apt-transport-https wget gnupg
&& wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add -
&& echo "deb https://packages.sury.org/php/ $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list
&& apt-get update
&& apt-get install -y php8.1 php8.1-mbstring php8.1-dev
&& apt-get satisfy -y --no-install-recommends
"curl (>=7.88)"
"supervisor (>=4.2)"
"unzip (>=6.0)"
"vim-tiny (>=2)"
&& apt-get install -y nodejs npm
&& npm install -g npm@7
&& apt-get clean
&& rm -rf /var/lib/apt/lists/*

RUN useradd
--uid 1000
--shell /bin/bash
"{user}" \ && setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp \ && chown -R "{user}:${user}"
/laravel
/data/caddy
/config/caddy
/var/{log,run}
&& chmod -R a+rw
/var/{log,run}

RUN install-php-extensions
bcmath
bz2
curl
exif
gd
intl
pcntl
pdo_pgsql
opcache
redis
sockets
calendar
zip

RUN composer install
RUN npm install

USER ${user}

RUN chmod -R a+rw storage

ENTRYPOINT ["/laravel/entrypoint.sh"]
ואני מקבל את השגיאה הזו :
#16 5.541 E: Package 'php8.1' has no installation candidate
#16 5.541 E: Package 'php8.1-mbstring' has no installation candidate
#16 5.541 E: Package 'php8.1-dev' has no installation candidate
#16 ERROR: process "/bin/bash -eou pipefail -c apt-get update && apt-get install -y lsb-release apt-transport-https wget gnupg && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - && echo "deb https://packages.sury.org/php/ $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list && apt-get update && apt-get install -y php8.1 php8.1-mbstring php8.1-dev && apt-get satisfy -y --no-install-recommends "curl (>=7.88)" "supervisor (>=4.2)" "unzip (>=6.0)" "vim-tiny (>=2)" && apt-get install -y nodejs npm && npm install -g npm@7 && apt-get clean && rm -rf /var/lib/apt/lists/*" did not complete successfully: exit code: 100
[base 10/15] RUN apt-get update && apt-get install -y lsb-release apt-transport-https wget gnupg && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - && echo "deb https://packages.sury.org/php/ $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list && apt-get update && apt-get install -y php8.1 php8.1-mbstring php8.1-dev && apt-get satisfy -y --no-install-recommends "curl (>=7.88)" "supervisor (>=4.2)" "unzip (>=6.0)" "vim-tiny (>=2)" && apt-get install -y nodejs npm && npm install -g npm@7 && apt-get clean && rm -rf /var/lib/apt/lists/*:
5.540 This may mean that the package is missing, has been obsoleted, or
5.540 is only available from another source
5.540
5.540 Package php8.1 is not available, but is referred to by another package.
5.540 This may mean that the package is missing, has been obsoleted, or
5.540 is only available from another source
5.540
5.541 E: Package 'php8.1' has no installation candidate
5.541 E: Package 'php8.1-mbstring' has no installation candidate
5.541 E: Package 'php8.1-dev' has no installation candidate

Dockerfile:20
19 |
20 | >>> RUN apt-get update
21 | >>> && apt-get install -y lsb-release apt-transport-https wget gnupg
22 | >>> && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add -
23 | >>> && echo "deb https://packages.sury.org/php/ $(lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list
24 | >>> && apt-get update
25 | >>> && apt-get install -y php8.1 php8.1-mbstring php8.1-dev
26 | >>> && apt-get satisfy -y --no-install-recommends
27 | >>> "curl (>=7.88)"
28 | >>> "supervisor (>=4.2)"
29 | >>> "unzip (>=6.0)"
30 | >>> "vim-tiny (>=2)"
31 | >>> && apt-get install -y nodejs npm
32 | >>> && npm install -g npm@7
33 | >>> && apt-get clean
34 | >>> && rm -rf /var/lib/apt/lists/*
35 |
ERROR: failed to solve: process "/bin/bash -eou pipefail -c apt-get update && apt-get install -y lsb-release apt-transport-https wget gnupg && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - && echo "deb https://packages.sury.org/php/ (lsb_release -cs) main\" | tee /etc/apt/sources.list.d/php.list && apt-get update && apt-get install -y php8.1 php8.1-mbstring php8.1-dev && apt-get satisfy -y --no-install-recommends \"curl (>=7.88)\" \"supervisor (>=4.2)\" \"unzip (>=6.0)\" \"vim-tiny (>=2)\" && apt-get install -y nodejs npm && npm install -g npm@7 && apt-get clean && rm -rf /var/lib/apt/lists/*" did not complete successfully: exit code: 100 Error: buildx failed with: ERROR: failed to solve: process "/bin/bash -eou pipefail -c apt-get update && apt-get install -y lsb-release apt-transport-https wget gnupg && wget -qO - https://packages.sury.org/php/apt.gpg | apt-key add - && echo \"deb https://packages.sury.org/php/ (lsb_release -cs) main" | tee /etc/apt/sources.list.d/php.list && apt-get update && apt-get install -y php8.1 php8.1-mbstring php8.1-dev && apt-get satisfy -y --no-install-recommends "curl (>=7.88)" "supervisor (>=4.2)" "unzip (>=6.0)" "vim-tiny (>=2)" && apt-get install -y nodejs npm && npm install -g npm@7 && apt-get clean && rm -rf /var/lib/apt/lists/*" did not complete successfully: exit code: 100

נראה שהבעיה נובעת מכך שהגרסה של PHP 8.1 אינה זמינה במאגר שאתה מנסה להשתמש בו. מאחר שאתה משתמש ב-Dockerfile שמבוסס על dunglas/frankenphp, ייתכן שהמאגר של ondrej/php לא תואם.

אם אתה רוצה להשתמש ב-PHP 8.3, תוכל לשנות את הפקודות להתקנה כך שיתאימו לגרסה זו. הנה דוגמה מעודכנת:

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
  && apt-get install -y php${php_version} php${php_version}-mbstring php${php_version}-dev \
  && apt-get install -y --no-install-recommends \
    curl \
    supervisor \
    unzip \
    vim-tiny \
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
    calendar \
    zip

RUN composer install
RUN npm install

USER ${user}

RUN chmod -R a+rw storage

ENTRYPOINT ["/laravel/entrypoint.sh"]
