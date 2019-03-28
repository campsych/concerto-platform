FROM campsych/concerto-platform:5.0.beta.9
MAINTAINER Przemyslaw Lis <przemek@concertoplatform.com>

RUN mkdir -p /var/log/php-fpm \
 && rm -rf src/Concerto/PanelBundle/Resources/public/files \
 && rm -rf src/Concerto/TestBundle/Resources/sessions \
 && touch var/logs/prod.log

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
 && cron \
 && service nginx start \
 && php bin/console concerto:forker:start \
 && php-fpm -D >> /var/log/php-fpm/out.log 2>&1 \
 && tail -F var/logs/prod.log -n 0