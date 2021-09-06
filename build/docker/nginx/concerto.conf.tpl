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
    location ~ ^/bundles/concertopanel/files/protected/ {
        deny all;
    }

    location ~ ^/files/(protected|session)/ {
        rewrite ^/(.*)$ /app.php/$1 last;
    }

    location / {
        try_files $uri /app.php$is_args$args;
    }

    location ~ ^/app\.php(/|$) {
        fastcgi_pass localhost:9000;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_param HTTPS off;
    }

    location ~ ^/app_dev\.php(/|$) {
        fastcgi_pass localhost:9000;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_param HTTPS off;
    }
}
