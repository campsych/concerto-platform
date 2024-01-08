FROM campsych/concerto-platform:test
MAINTAINER Przemyslaw Lis <przemek@concertoplatform.com>

RUN apt-get update -y \
 && apt-get install composer -y

CMD if [ "$CONCERTO_COOKIES_SECURE" = "true" ]; \
    then export CONCERTO_COOKIES_SECURE_PHP=1; \
    else export CONCERTO_COOKIES_SECURE_PHP=0; \
    fi \
 && /wait-for-it.sh $DB_TEST_HOST:$DB_TEST_PORT -t 300 \
 && php bin/console concerto:setup --env=test \
 && cat /etc/nginx/sites-available/concerto.conf.tpl | sed "s/{{nginx_port}}/$NGINX_PORT/g" | sed "s/{{nginx_server_conf}}/$NGINX_SERVER_CONF/g" > /etc/nginx/sites-available/concerto.conf \
 && service nginx start \
 && php bin/console concerto:forker:start --env=test \
 && /etc/init.d/php7.4-fpm start \
 && vendor/bin/simple-phpunit