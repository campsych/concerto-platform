FROM php:7.1-fpm
MAINTAINER Przemyslaw Lis <przemek@concertoplatform.com>

ARG CRAN_MIRROR=http://cran.uni-muenster.de/

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
    npm \
    r-base \
    r-base-dev \
    unzip \
    zip \
 && sed -i 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' /etc/locale.gen \
 && locale-gen "en_US.UTF-8"
 
RUN docker-php-ext-install \
    curl \
    json \
    pdo \
    pdo_mysql \
    posix \
    sockets \
    xml

COPY php.ini /usr/local/etc/php/

COPY . /usr/src/concerto/

RUN Rscript -e "install.packages(c('session','RMySQL','jsonlite','catR','digest','ggplot2','base64enc','rjson'), repos='$CRAN_MIRROR')" \
 && cd /usr/src/concerto/src/Concerto/TestBundle/Resources/R \
 && R CMD INSTALL concerto5 
 
ADD https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh /

RUN chmod +x /wait-for-it.sh \
 && cd /usr/src/concerto \
 && php app/console concerto:r:cache

RUN crontab -l | { cat; echo "* * * * * * * * * * /usr/local/bin/php /usr/src/concerto/app/console concerto:schedule:tick --env=prod >> /var/log/cron.log 2>&1"; } | crontab -
 
EXPOSE 9000
 
CMD cd / \
 && ./wait-for-it.sh database:3306 -t 60 \
 && cd /usr/src/concerto \
 && php app/console concerto:setup \
 && chown -R www-data:www-data /usr/src/concerto \
 && cron \
 && php-fpm