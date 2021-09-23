#!/bin/sh

# This script is intended to run inside the bootstrap container. It
# should work outside, but that use case is not tested.

. bootstrap.env

# TODO: Add mail domain when implemented
rm -f /etc/nginx/conf.d/default.conf
sed -ri "s/%hostname%/${WEB_DOMAIN}/" /etc/nginx/conf.d/challenge.conf

nginx

# TODO Expose these in the configuration utility
RSA_KEY_SIZE=4096
PREFIX="/etc/letsencrypt"
SELF_SIGNED_CERTIFICATE_TTL=365

echo "Starting bootstrap"

obtain_certificates () {
    DOMAIN="$1"
    if [ ! -e "${PREFIX}/live/${DOMAIN}" ] ||  [ ! -e "${PREFIX}/live/ssl-dhparams.pem" ];then
        echo "### Downloading recommended TLS parameters ..."
        mkdir -p "${PREFIX}/live/${DOMAIN}"

        curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot-nginx/certbot_nginx/_internal/tls_configs/options-ssl-nginx.conf > "${PREFIX}/options-ssl-nginx.conf"
        curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot/certbot/ssl-dhparams.pem >"${PREFIX}/ssl-dhparams.pem"

        if [ ${SIGNED} -eq 0 ]; then
            echo "### Creating self signed certificate for ${DOMAIN} ..."
            openssl req -x509 -nodes -newkey "rsa:${RSA_KEY_SIZE}" -days "${SELF_SIGNED_CERTIFICATE_TTL}" \
                    -keyout "${PREFIX}/live/${DOMAIN}/privkey.pem" \
                    -out "${PREFIX}/live/${DOMAIN}/fullchain.pem" -subj "/CN=${DOMAIN}"
        else
            echo "### Creating dummy certificate for ${DOMAIN} ..."
            openssl req -x509 -nodes -newkey rsa:1024 -days 1 \
                    -keyout "${PREFIX}/live/${DOMAIN}/privkey.pem" \
                    -out "${PREFIX}/live/${DOMAIN}/fullchain.pem" -subj '/CN=localhost'

            nginx -s reload

            rm -Rf "${PREFIX}/live/${DOMAIN}"
            rm -Rf "${PREFIX}/archive/${DOMAIN}"
            rm -Rf "${PREFIX}/renewal/${DOMAIN}.conf"

            echo "### Requesting Let's Encrypt certificate for ${DOMAIN} ..."

            # Ask Let's Encrypt to create certificates, if challenge passes
            certbot certonly --webroot -w "/var/www/certbot" \
                    --email "${EMAIL}" \
                    -d "${DOMAIN}" \
                    --non-interactive \
                    --rsa-key-size "${RSA_KEY_SIZE}" \
                    --agree-tos \
                    --force-renewal
        fi
    else
        echo "Certificate related files exists, exiting"
    fi
}

obtain_certificates "${WEB_DOMAIN}"
#TODO: Uncomment when implemented (:
#obtain_certificates "${MAIL_DOMAIN}"
