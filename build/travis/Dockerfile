FROM campsych/concerto-platform:test
MAINTAINER Przemyslaw Lis <przemek@concertoplatform.com>

RUN apt-get update -y \
 && apt-get install composer -y

CMD /wait-for-it.sh $DB_TEST_HOST:$DB_TEST_PORT -t 300 \
 && php bin/console concerto:setup --env=test \
 && cat /etc/nginx/sites-available/concerto.conf.tpl | sed "s/{{nginx_port}}/$NGINX_PORT/g" > /etc/nginx/sites-available/concerto.conf \
 && service nginx start \
 && php bin/console concerto:forker:start \
 && /etc/init.d/php7.2-fpm start \
 && vendor/bin/simple-phpunit