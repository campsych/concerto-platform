FROM php:7.1-fpm
MAINTAINER Przemyslaw Lis <przemek@concertoplatform.com>

ARG CRAN_MIRROR=http://www.stats.bris.ac.uk/R/

RUN echo "deb http://cran.rstudio.com/bin/linux/debian jessie-cran3/" | tee -a /etc/apt/sources.list \
 && apt-key adv --keyserver keys.gnupg.net --recv-key 6212B7B7931C4BB16280BA1306F90DE5381BA480 \
 && apt-get update -y \
 && apt-get -y install \
    cron \
    curl \
    git \
    libcurl4-openssl-dev \
    libxml2-dev \
    libmysqlclient-dev \
    locales \
    mysql-client \
    nginx \
    npm \
    r-base \
    r-base-dev \
    unzip \
    zip \
 && sed -i 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' /etc/locale.gen \
 && locale-gen "en_US.UTF-8" \
 && docker-php-ext-install \
    curl \
    json \
    pdo \
    pdo_mysql \
    posix \
    sockets \
    xml

COPY . /usr/src/concerto/
COPY build/php.ini /usr/local/etc/php/php.ini
COPY build/nginx/nginx.conf /etc/nginx/nginx.conf
COPY build/nginx/concerto.conf /etc/nginx/sites-available/concerto.conf
ADD https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh /

RUN Rscript -e "install.packages(c('session','RMySQL','jsonlite','catR','digest','ggplot2','base64enc','rjson'), repos='$CRAN_MIRROR')" \
 && R CMD INSTALL /usr/src/concerto/src/Concerto/TestBundle/Resources/R/concerto5 \
 && chmod +x /wait-for-it.sh \
 && php /usr/src/concerto/app/console concerto:r:cache \
 && crontab -l | { cat; echo "* * * * * /usr/local/bin/php /usr/src/concerto/app/console concerto:schedule:tick --env=dev >> /var/log/cron.log 2>&1"; } | crontab - \
 && rm -f /etc/nginx/sites-available/default \
 && rm -f /etc/nginx/sites-enabled/default \
 && ln -fs /etc/nginx/sites-available/concerto.conf /etc/nginx/sites-enabled/concerto.conf
 
EXPOSE 80 9000
WORKDIR /usr/src/concerto
 
CMD php app/console concerto:setup \
 && php app/console concerto:r:cache \
 && rm -rf app/cache/prod \
 && php app/console cache:warmup --env=prod \
 && chown -R www-data:www-data app/cache \
 && chown -R www-data:www-data app/logs \
 && chown -R www-data:www-data src/Concerto/PanelBundle/Resources/public/files \
 && chown -R www-data:www-data src/Concerto/PanelBundle/Resources/import \
 && chown -R www-data:www-data src/Concerto/TestBundle/Resources/sessions \
 && cron \
 && service nginx start \
 && php-fpm