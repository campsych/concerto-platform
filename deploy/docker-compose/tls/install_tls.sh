#!/usr/bin/env bash

if [[ "$#" -lt 2 ]]; then
    printf "Usage: $0 domain proxy_url [email]\n\n"
    exit 1
fi

DOMAIN=$1
PROXY_TARGET=$2
EMAIL=$3

NGINX_DATA_PATH="./data/nginx"
CERTBOT_DATA_PATH="./data/certbot"
CERTBOT_RSA_KEY_SIZE=4096

get_tsl_params() {
    if [[ ! -e "${CERTBOT_DATA_PATH}/conf/options-ssl-nginx.conf" ]] || [[ ! -e "${CERTBOT_DATA_PATH}/conf/ssl-dhparams.pem" ]]; then
      printf "### Downloading TLS parameters\n"
      mkdir -p "${CERTBOT_DATA_PATH}/conf"
      curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot-nginx/certbot_nginx/tls_configs/options-ssl-nginx.conf > "${CERTBOT_DATA_PATH}/conf/options-ssl-nginx.conf"
      curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot/ssl-dhparams.pem > "${CERTBOT_DATA_PATH}/conf/ssl-dhparams.pem"
    fi
}

configure_nginx_without_tls() {
    if [[ ! -e "${NGINX_DATA_PATH}/app.conf" ]]; then
        printf "### Writing initial Nginx configuration\n"
        mkdir -p ${NGINX_DATA_PATH}
        cat > ${NGINX_DATA_PATH}/app.conf << EOF
server {
    listen [::]:80 default_server;
    listen 80 default_server;

    client_max_body_size 50M;

    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }

    location / {
        proxy_pass ${PROXY_TARGET};
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_set_header Host \$http_host;
    }
}
EOF
    fi
}

start_nginx() {
    printf "### Starting Nginx\n"
    docker-compose up -d nginx
}

request_cert() {
    printf "### Requesting certificate\n"

    case "$EMAIL" in
      "") email="--register-unsafely-without-email" ;;
      *) email="--email $EMAIL" ;;
    esac

    docker-compose run --rm --entrypoint "\
      certbot certonly --non-interactive --webroot -w /var/www/certbot \
        $email \
        -d ${DOMAIN} \
        --rsa-key-size ${CERTBOT_RSA_KEY_SIZE} \
        --agree-tos \
        --force-renewal" certbot

    if [[ $? -ne 0 ]]; then
        printf "### Error while requesting certificate, will sleep for 5m and retry...\n"
        sleep 300
        request_cert
    fi
}

configure_nginx_with_tls() {
    printf "### Writing TLS-enabled Nginx configuration\n"
    cat > ${NGINX_DATA_PATH}/app.conf << EOF
server {
    listen [::]:80 default_server;
    listen 80 default_server;

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
    server_name ${DOMAIN};

    ssl_certificate /etc/letsencrypt/live/${DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${DOMAIN}/privkey.pem;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    include /etc/letsencrypt/options-ssl-nginx.conf;

    client_max_body_size 50M;

    location / {
        proxy_pass ${PROXY_TARGET};
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_set_header Host \$http_host;
    }
}
EOF
}

reload_nginx() {
    printf "### Reloading Nginx\n"
    docker-compose exec -T nginx nginx -s reload
}

start_certbot() {
    printf "### Starting Certbot\n"
    docker-compose up -d certbot
}

get_tsl_params
configure_nginx_without_tls
start_nginx
request_cert
configure_nginx_with_tls
reload_nginx
start_certbot
