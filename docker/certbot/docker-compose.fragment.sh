#!/usr/bin/sh

cat <<EOF
    certbot:
        image: certbot/certbot
EOF

# If the user wants a nginx docker container
if echo "${DOCKER}" | grep -Fvq '"nginx"'; then
    cat <<EOF
        depends_on:
            - nginx
EOF
fi
cat <<EOF
        # Check for certificate renewal every 12h as
        # recommended by Let's Encrypt
        entrypoint: /bin/sh -c 'trap exit TERM;
                                while :; do
                                    certbot renew > /dev/null;
                                    sleep 12h & wait \$\${!};
                                done'
        volumes:
          - ./docker/certbot/www:/var/www/certbot
          - ./docker/certbot/.files:/etc/letsencrypt

EOF
