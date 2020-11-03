FROM ubuntu:18.04
MAINTAINER Przemyslaw Lis <przemek@concertoplatform.com>

ARG CRAN_MIRROR=https://cloud.r-project.org

ENV CONCERTO_PLATFORM_URL=/
ENV CONCERTO_PASSWORD=admin
ENV CONCERTO_API_ENABLED=true
ENV CONCERTO_API_ENABLED_OVERRIDABLE=true
ENV CONCERTO_DATA_API_ENABLED=true
ENV CONCERTO_SESSION_LIMIT=0
ENV CONCERTO_SESSION_LIMIT_OVERRIDABLE=true
ENV CONCERTO_CONTENT_URL=.
ENV CONCERTO_CONTENT_URL_OVERRIDABLE=true
ENV CONCERTO_CONTENT_TRANSFER_OPTIONS='[]'
ENV CONCERTO_CONTENT_TRANSFER_OPTIONS_OVERRIDABLE=true
ENV CONCERTO_SESSION_RUNNER_SERVICE=SerializedSessionRunnerService
ENV CONCERTO_SESSION_RUNNER_SERVICE_OVERRIDABLE=true
ENV CONCERTO_GIT_ENABLED=0
ENV CONCERTO_GIT_ENABLED_OVERRIDABLE=true
ENV CONCERTO_GIT_URL=''
ENV CONCERTO_GIT_URL_OVERRIDABLE=true
ENV CONCERTO_GIT_BRANCH=master
ENV CONCERTO_GIT_BRANCH_OVERRIDABLE=true
ENV CONCERTO_GIT_LOGIN=''
ENV CONCERTO_GIT_LOGIN_OVERRIDABLE=true
ENV CONCERTO_GIT_PASSWORD=''
ENV CONCERTO_GIT_PASSWORD_OVERRIDABLE=true
ENV CONCERTO_GIT_REPOSITORY_PATH=''
ENV CONCERTO_BEHIND_PROXY=false
ENV CONCERTO_CONTENT_IMPORT_AT_START=true
ENV CONCERTO_FAILED_AUTH_LOCK_TIME=300
ENV CONCERTO_FAILED_AUTH_LOCK_STREAK=3
ENV CONCERTO_SESSION_FILES_EXPIRATION=7
ENV CONCERTO_SESSION_LOG_LEVEL=1
ENV CONCERTO_KEY_PASS=changeme
ENV CONCERTO_SESSION_STORAGE=filesystem
ENV CONCERTO_COOKIES_SAME_SITE=strict
ENV CONCERTO_COOKIES_SECURE=false
ENV CONCERTO_KEEP_ALIVE_INTERVAL_TIME=900
ENV CONCERTO_KEEP_ALIVE_TOLERANCE_TIME=0
ENV CONCERTO_SESSION_TOKEN_EXPIRY_TIME=7200
ENV REDIS_HOST=redis
ENV REDIS_PORT=6379
ENV REDIS_PASS=''
ENV DB_HOST=localhost
ENV DB_PORT=3306
ENV DB_NAME=concerto
ENV DB_USER=concerto
ENV DB_PASSWORD=changeme
ENV NGINX_PORT=80
ENV NGINX_SERVER_CONF="add_header X-Frame-Options sameorigin always;\nadd_header X-Content-Type-Options nosniff always;"
ENV PHP_FPM_PM=dynamic
ENV PHP_FPM_PM_MAX_CHILDREN=30
ENV PHP_FPM_PM_START_SERVERS=10
ENV PHP_FPM_PM_MIN_SPARE_SERVERS=5
ENV PHP_FPM_PM_MAX_SPARE_SERVERS=15
ENV PHP_FPM_PM_PROCESS_IDLE_TIMEOUT=10s
ENV PHP_FPM_PM_MAX_REQUESTS=300
ENV TZ=Europe/London

COPY . /app/concerto/
ADD https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh /

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone \
 && apt-get update -y \
 && apt-get -y install \
    ca-certificates \
    gnupg \
 && echo "deb $CRAN_MIRROR/bin/linux/ubuntu bionic-cran35/" | tee -a /etc/apt/sources.list \
 && apt-key adv --no-tty --keyserver keyserver.ubuntu.com --recv-keys E298A3A825C0D65DFD57CBB651716619E084DAB9 \
 && apt-get update -y \
 && apt-get -y install \
    cron \
    curl \
    gettext \
    git \
    libcurl4-openssl-dev \
    libhiredis-dev \
    libmariadbclient-dev \
    libxml2-dev \
    libssl-dev \
    locales \
    nginx \
    php7.2-curl \
    php7.2-mbstring \
    php7.2-mysql \
    php7.2-xml \
    php7.2-zip \
    php-fpm \
    procps \
    r-base \
    r-base-dev \
 && rm -rf /var/lib/apt/lists/* \
 && sed -i 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' /etc/locale.gen \
 && locale-gen "en_US.UTF-8" \
 && Rscript -e "install.packages(c('catR','digest','httr','jsonlite','redux','rjson','RMySQL','session','xml2'), repos='$CRAN_MIRROR')" \
 && R CMD INSTALL /app/concerto/src/Concerto/TestBundle/Resources/R/concerto5 \
 && chmod +x /wait-for-it.sh \
 && php /app/concerto/bin/console concerto:r:cache \
 && crontab -l | { cat; echo "* * * * * . /app/concerto/cron/concerto.schedule.tick.sh >> /var/log/cron.log 2>&1"; } | crontab - \
 && crontab -l | { cat; echo "0 0 * * * . /app/concerto/cron/concerto.session.clear.sh >> /var/log/cron.log 2>&1"; } | crontab - \
 && crontab -l | { cat; echo "*/5 * * * * . /app/concerto/cron/concerto.session.log.sh >> /var/log/cron.log 2>&1"; } | crontab - \
 && rm -f /etc/nginx/sites-available/default \
 && rm -f /etc/nginx/sites-enabled/default \
 && ln -fs /etc/nginx/sites-available/concerto.conf /etc/nginx/sites-enabled/concerto.conf

COPY build/docker/php/php.ini /etc/php/7.2/fpm/php.ini
COPY build/docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY build/docker/nginx/concerto.conf.tpl /etc/nginx/sites-available/concerto.conf.tpl
COPY build/docker/php-fpm/php-fpm.conf /etc/php/7.2/fpm/php-fpm.conf
COPY build/docker/php-fpm/www.conf /etc/php/7.2/fpm/pool.d/www.conf

RUN rm -rf /app/concerto/src/Concerto/PanelBundle/Resources/public/files \
 && rm -rf /app/concerto/src/Concerto/TestBundle/Resources/sessions

EXPOSE 80 9000
WORKDIR /app/concerto
HEALTHCHECK --interval=1m --start-period=1m CMD curl -f http://localhost/api/check/health || exit 1

CMD if [ "$CONCERTO_COOKIES_SECURE" = "true" ]; \
    then export CONCERTO_COOKIES_SECURE_PHP=1; \
    else export CONCERTO_COOKIES_SECURE_PHP=0; \
    fi \
 && printenv | sed 's/^\([a-zA-Z0-9_]*\)=\(.*\)$/export \1="\2"/g' > /root/env.sh \
 && mkdir -p /data/files \
 && mkdir -p /data/sessions \
 && mkdir -p /data/git \
 && ln -sf /data/files /app/concerto/src/Concerto/PanelBundle/Resources/public \
 && ln -sf /data/sessions /app/concerto/src/Concerto/TestBundle/Resources \
 && ln -sf /app/concerto/src/Concerto/PanelBundle/Resources/public/files /app/concerto/web \
 && chown www-data:www-data /data/sessions \
 && chown -R www-data:www-data /data/files \
 && /wait-for-it.sh $DB_HOST:$DB_PORT -t 300 \
 && php bin/console concerto:setup --env=prod --admin-pass=$CONCERTO_PASSWORD \
 && chown www-data:www-data /app/concerto/app/config/jwt/private.pem /app/concerto/app/config/jwt/public.pem \
 && if [ "$CONCERTO_CONTENT_IMPORT_AT_START" = "true" ]; \
    then php bin/console concerto:content:import --env=prod --sc; \
    fi \
 && rm -rf var/cache/* \
 && php bin/console cache:warmup --env=prod \
 && chown www-data:www-data src/Concerto/TestBundle/Resources/R/fifo \
 && chown -R www-data:www-data var/cache \
 && chown -R www-data:www-data var/logs \
 && chown -R www-data:www-data var/sessions \
 && chown -R www-data:www-data var/git \
 && chown -R www-data:www-data src/Concerto/PanelBundle/Resources/import \
 && chown -R www-data:www-data src/Concerto/PanelBundle/Resources/export \
 && chown -R www-data:www-data /data/git \
 && cron \
 && cat /etc/nginx/sites-available/concerto.conf.tpl | sed "s/{{nginx_port}}/$NGINX_PORT/g" | sed "s/{{nginx_server_conf}}/$NGINX_SERVER_CONF/g" > /etc/nginx/sites-available/concerto.conf \
 && service nginx start \
 && php bin/console concerto:forker:start --env=prod  \
 && /etc/init.d/php7.2-fpm start \
 && tail -F var/logs/prod.log -n 0