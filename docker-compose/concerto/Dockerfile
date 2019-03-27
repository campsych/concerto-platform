FROM campsych/concerto-platform:5.0.beta.9
MAINTAINER Przemyslaw Lis <przemek@concertoplatform.com>

RUN mkdir -p /var/log/php-fpm \
 && rm -rf /usr/src/concerto/src/Concerto/PanelBundle/Resources/public/files \
 && rm -rf /usr/src/concerto/src/Concerto/TestBundle/Resources/sessions

CMD ln -sf /usr/src/concerto/volume/files /usr/src/concerto/src/Concerto/PanelBundle/Resources/public \
 && ln -sf /usr/src/concerto/volume/sessions /usr/src/concerto/src/Concerto/TestBundle/Resources \
 && /wait-for-it.sh $DB_HOST:$DB_PORT -t 300 \
 && php bin/console concerto:setup \
 && php bin/console concerto:content:import --convert \
 && rm -rf var/cache/* \
 && php bin/console cache:warmup --env=prod \
 && chown -R www-data:www-data var/cache \
 && chown -R www-data:www-data var/logs \
 && chown -R www-data:www-data var/sessions \
 && chown -R www-data:www-data src/Concerto/PanelBundle/Resources/public/files \
 && chown -R www-data:www-data src/Concerto/PanelBundle/Resources/import \
 && chown -R www-data:www-data src/Concerto/TestBundle/Resources/sessions \
 && chown -R www-data:www-data src/Concerto/TestBundle/Resources/R/fifo \
 && chown -R www-data:www-data src/Concerto/TestBundle/Resources/R/init_checkpoint \
 && rm -rf src/Concerto/TestBundle/Resources/R/init_checkpoint/* \
 && cron \
 && service nginx start \
 && php bin/console concerto:forker:start \
 && php-fpm 2>&1 | tee -a /var/log/php-fpm/out.log