server {
    listen {{nginx_port}} default_server;
    listen [::]:{{nginx_port}} default_server;

    root /app/concerto/web;
    client_max_body_size 50M;
    rewrite ^/(.*)/$ /$1 permanent;

    {{nginx_server_conf}}

    location ~ /(\.|web\.config) {
        deny all;
    }
    location ~ ^{{base_dir}}bundles/concertopanel/files/protected/ {
        deny all;
    }

    location ~ ^{{base_dir}}files/(protected|session)/ {
        rewrite ^{{base_dir}}(.*)$ {{base_dir}}app.php/$1 last;
    }

    location {{base_dir}}files {
        alias /app/concerto/web/files;
    }

    location {{base_dir}}bundles {
        alias /app/concerto/web/bundles;
    }

    location {{base_dir}}favicon.ico {
        alias /app/concerto/web/favicon.ico;
    }

    location {{base_dir}} {
        try_files $uri {{base_dir}}app.php$is_args$args;
    }

    location ~ ^{{base_dir}}app\.php(/|$) {
        fastcgi_pass localhost:9000;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /app/concerto/web/app.php;
        fastcgi_param DOCUMENT_ROOT /app/concerto/web;
        fastcgi_param HTTPS off;
    }

    location ~ ^{{base_dir}}app_dev\.php(/|$) {
        fastcgi_pass localhost:9000;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /app/concerto/web/app_dev.php;
        fastcgi_param DOCUMENT_ROOT /app/concerto/web;
        fastcgi_param HTTPS off;
    }
}
