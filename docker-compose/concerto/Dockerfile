FROM campsych/concerto-platform:latest
MAINTAINER Przemyslaw Lis <przemek@concertoplatform.com>

ADD https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh /wait-for-it.sh
RUN chmod o+x /wait-for-it.sh

CMD /wait-for-it.sh database:3306 -t 300 \
 && rm -rf var/cache/* \
 && php bin/console concerto:setup \
 && php bin/console concerto:r:cache \
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
 && php-fpm