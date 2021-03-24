#!/usr/bin/sh

cat <<EOF
    mail:
        build: docker/mail
        env_file:
          - ./docker/mail/mail.env
        ports:
          - 25:25
          - 110:110
          - 143:143
          - 587:587
          - 993:993
        volumes:
          - ./docker/mail/etc:/etc
          - ./docker/mail/entrypoint.sh:/usr/bin/entrypoint.sh
          - ./docker/mail/mail:/var/mail
          - ./docker/mail/config:/etc/mail
          - ./docker/mail/config/postfix:/etc/postfix
          - ./docker/mail/config/dovecot:/etc/dovecot
          # Certbot
          - ./docker/certbot/www:/var/www/certbot
          - ./docker/certbot/.files:/etc/letsencrypt

EOF
