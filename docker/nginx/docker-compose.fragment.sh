#!/usr/bin/sh

cat <<EOF
    nginx:
        image: nginx:alpine
EOF

# If the user wants a PHP docker container
if echo "${DOCKER}" | grep -Fq '"php"'; then
    cat <<EOF
        depends_on:
            - php
EOF
fi

cat <<EOF
        restart: always
        tty: false
        ports:
            - "${NGINX_HTTP_PORT}:80"
            - "${NGINX_HTTPS_PORT}:443"
        volumes:
            # Nginx
            - ./docker/nginx/nginx.conf:/var/nginx/social.conf
            - ./docker/nginx/domain.sh:/var/nginx/domain.sh
            # Certbot
            - ./docker/certbot/www:/var/www/certbot
            - ./docker/certbot/.files:/etc/letsencrypt
            # social
            - ./public:/var/www/social/public
        env_file:
            - ./docker/bootstrap/bootstrap.env
            - ./docker/db/db.env
EOF

# If the user wants a Certbot docker container
if echo "${DOCKER}" | grep -Fq '"certbot"'; then
    cat <<EOF
        command: /bin/sh -c '/var/nginx/domain.sh;
                             while :; do
                                 sleep 6h & wait \$\${!};
                                 nginx -s reload;
                             done &
                             nginx -g "daemon off;"'

EOF
else
    cat <<EOF
        command: 'nginx -g \"daemon off;\"'

EOF
fi
