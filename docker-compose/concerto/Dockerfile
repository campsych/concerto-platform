FROM campsych/concerto-platform:build-improvements
MAINTAINER Przemyslaw Lis <przemek@concertoplatform.com>

RUN rm -rf /usr/src/concerto/src/Concerto/PanelBundle/Resources/public/files \
&& ls -s /usr/src/concerto/volume/files /usr/src/concerto/src/Concerto/PanelBundle/Resources/public \
&& rm -rf /usr/src/concerto/src/Concerto/TestBundle/Resources/sessions \
&& ls -s /usr/src/concerto/volume/sessions /usr/src/concerto/src/Concerto/TestBundle/Resources

CMD /wait-for-it.sh $DB_HOST:$DB_PORT -t 300 \
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