#!/usr/bin/env bash

if [[ "$#" -lt 2 ]]; then
    printf "Usage: $0 domain proxy_url [certbot_email]\n\n"
    exit 1
fi

NGINX_SERVER_NAME=$1
NGINX_PROXY_TARGET=$2
CERTBOT_EMAIL=$3

NGINX_DATA_PATH="./data/nginx"
CERTBOT_RSA_KEY_SIZE=4096
CERTBOT_DATA_PATH="./data/certbot"

get_tsl_params() {
    if [[ ! -e "${CERTBOT_DATA_PATH}/conf/options-ssl-nginx.conf" ]] || [[ ! -e "${CERTBOT_DATA_PATH}/conf/ssl-dhparams.pem" ]]; then
      printf "### Downloading TLS parameters\n"
      mkdir -p "${CERTBOT_DATA_PATH}/conf"
      curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot-nginx/certbot_nginx/options-ssl-nginx.conf > "${CERTBOT_DATA_PATH}/conf/options-ssl-nginx.conf"
      curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot/ssl-dhparams.pem > "${CERTBOT_DATA_PATH}/conf/ssl-dhparams.pem"
    fi
}

create_self_signed_cert() {
    printf "### Creating self-signed certificates\n"

    mkdir -p "$CERTBOT_DATA_PATH/conf/live/$NGINX_SERVER_NAME"

    docker-compose run --rm --entrypoint "\
      openssl req -x509 -nodes -newkey rsa:1024 -days 1\
        -keyout '/etc/letsencrypt/live/$NGINX_SERVER_NAME/privkey.pem' \
        -out '/etc/letsencrypt/live/$NGINX_SERVER_NAME/fullchain.pem' \
        -subj '/CN=localhost'" certbot
}

configure_nginx() {
    printf "### Writing Nginx configuration\n"
    mkdir -p ${NGINX_DATA_PATH}
    cat > ${NGINX_DATA_PATH}/app.conf << EOF
server {
    listen [::]:80;
    listen 80;
    server_name ${NGINX_SERVER_NAME};
    server_name www.${NGINX_SERVER_NAME};

    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }

    location / {
        return 301 https://\$host\$request_uri;
    }
}

server {
    listen [::]:443 ssl http2;
    listen 443 ssl http2;
    server_name www.${NGINX_SERVER_NAME};

    ssl_certificate /etc/letsencrypt/live/${NGINX_SERVER_NAME}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${NGINX_SERVER_NAME}/privkey.pem;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    include /etc/letsencrypt/options-ssl-nginx.conf;

    location / {
      return 301 https://${NGINX_SERVER_NAME}\$request_uri;
    }
}

server {
    listen [::]:443 ssl http2;
    listen 443 ssl http2;
    server_name ${NGINX_SERVER_NAME};

    ssl_certificate /etc/letsencrypt/live/${NGINX_SERVER_NAME}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${NGINX_SERVER_NAME}/privkey.pem;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    include /etc/letsencrypt/options-ssl-nginx.conf;

    client_max_body_size 50M;

    location / {
        proxy_pass ${NGINX_PROXY_TARGET};
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_set_header Host \$http_host;
    }
}
EOF
}

start_nginx() {
    printf "### Starting Nginx\n"
    docker-compose up --force-recreate -d nginx
}

remove_self_signed_cert() {
    docker-compose run --rm --entrypoint "\
        rm -Rf /etc/letsencrypt/live/${NGINX_SERVER_NAME} && \
        rm -Rf /etc/letsencrypt/archive/${NGINX_SERVER_NAME} && \
        rm -Rf /etc/letsencrypt/renewal/${NGINX_SERVER_NAME}" certbot
}

request_cert() {
    printf "### Requesting certificate\n"

    case "$CERTBOT_EMAIL" in
      "") email="--register-unsafely-without-email" ;;
      *) email="--email $email" ;;
    esac

    docker-compose run --rm --entrypoint "\
      certbot certonly --webroot -w /var/www/certbot \
        $email \
        --dry-run \
        -d ${NGINX_SERVER_NAME} \
        -d www.${NGINX_SERVER_NAME} \
        --rsa-key-size ${CERTBOT_RSA_KEY_SIZE} \
        --agree-tos \
        --force-renewal" certbot

    if [[ $? -ne 0 ]]; then
        printf "### Error while requesting certificate, will sleep for 5m and retry...\n"
        sleep 300
        request_cert
    fi
}

reload_nginx() {
    printf "### Reloading nginx\n"
    docker-compose exec nginx nginx -s reload
}

get_tsl_params
create_self_signed_cert
configure_nginx
start_nginx
remove_self_signed_cert
request_cert
reload_nginx
