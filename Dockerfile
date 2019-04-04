FROM ubuntu:18.04
MAINTAINER Przemyslaw Lis <przemek@concertoplatform.com>

ARG CRAN_MIRROR=https://cloud.r-project.org
ENV TZ=Europe/London

COPY . /usr/src/concerto/
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
    libcurl4-openssl-dev \
    libmariadbclient-dev \
    libxml2-dev \
    libssl-dev \
    locales \
    nginx \
    php7.2-curl \
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
 && Rscript -e "install.packages(c('session','RMySQL','jsonlite','catR','digest','rjson','httr'), repos='$CRAN_MIRROR')" \
 && R CMD INSTALL /usr/src/concerto/src/Concerto/TestBundle/Resources/R/concerto5 \
 && chmod +x /wait-for-it.sh \
 && php /usr/src/concerto/bin/console concerto:r:cache \
 && crontab -l | { cat; echo "* * * * * /usr/bin/php /usr/src/concerto/bin/console concerto:schedule:tick --env=dev >> /var/log/cron.log 2>&1"; } | crontab - \
 && crontab -l | { cat; echo "0 0 * * * /usr/bin/php /usr/src/concerto/bin/console concerto:sessions:clear --env=dev >> /var/log/cron.log 2>&1"; } | crontab - \
 && crontab -l | { cat; echo "*/5 * * * * /usr/bin/php /usr/src/concerto/bin/console concerto:sessions:log --env=dev >> /var/log/cron.log 2>&1"; } | crontab - \
 && rm -f /etc/nginx/sites-available/default \
 && rm -f /etc/nginx/sites-enabled/default \
 && ln -fs /etc/nginx/sites-available/concerto.conf /etc/nginx/sites-enabled/concerto.conf

COPY build/php/php.ini /etc/php/7.2/fpm/php.ini
COPY build/nginx/nginx.conf /etc/nginx/nginx.conf
COPY build/nginx/concerto.conf /etc/nginx/sites-available/concerto.conf
COPY build/php-fpm/php-fpm.conf /etc/php/7.2/fpm/php-fpm.conf
COPY build/php-fpm/www.conf /etc/php/7.2/fpm/pool.d/www.conf

EXPOSE 80 9000
WORKDIR /usr/src/concerto

CMD /wait-for-it.sh $DB_TEST_HOST:$DB_TEST_PORT -t 300 \
 && php bin/console concerto:setup --env=test \
 && service nginx start \
 && php bin/console concerto:forker:start \
 && /etc/init.d/php7.2-fpm start \
 && vendor/bin/simple-phpunit