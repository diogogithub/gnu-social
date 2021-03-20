#!/bin/sh

. bootstrap.env

sed -ri "s/%hostname%/${DOMAIN}/" /etc/nginx/conf.d/challenge.conf

nginx

rsa_key_size=4096
certbot_path="/var/www/certbot"
lets_path="/etc/letsencrypt"

echo "Starting bootstrap"

if [ ! -e "$lets_path/live//options-ssl-nginx.conf" ] ||  [ ! -e "$lets_path/live/ssl-dhparams.pem" ];then
    echo "### Downloading recommended TLS parameters ..."
    mkdir -p "${lets_path}/live/${DOMAIN}"

    curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot-nginx/certbot_nginx/_internal/tls_configs/options-ssl-nginx.conf >"$lets_path/options-ssl-nginx.conf"
    curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot/certbot/ssl-dhparams.pem >"$lets_path/ssl-dhparams.pem"

    if [ ${SIGNED} -eq 0 ]; then
        echo "### Creating self signed certificate for ${DOMAIN} ..."
        openssl req -x509 -nodes -newkey rsa:$rsa_key_size -days 365 \
                -keyout "${lets_path}/live/${DOMAIN}/privkey.pem" \
                -out "${lets_path}/live/${DOMAIN}/fullchain.pem" -subj "/CN=${DOMAIN}"
    else
        echo "### Creating dummy certificate for ${DOMAIN} ..."
        openssl req -x509 -nodes -newkey rsa:1024 -days 1 \
                -keyout "${lets_path}/live/${DOMAIN}/privkey.pem" \
                -out "${lets_path}/live/${DOMAIN}/fullchain.pem" -subj '/CN=localhost'

        nginx -s reload

        rm -Rf "${lets_path}/live/${DOMAIN}"
        rm -Rf "${lets_path}/archive/${DOMAIN}"
        rm -Rf "${lets_path}/renewal/${DOMAIN}.conf"

        echo "### Requesting Let's Encrypt certificate for ${DOMAIN} ..."
        # Format domain_args with the cartesian product of `domain_root` and `subdomains`

        # if [ "${DOMAIN_ROOT}" = "${DOMAIN}" ]; then domain_arg="-d ${DOMAIN_ROOT}"; else domain_arg="-d ${DOMAIN_ROOT} -d ${DOMAIN}"; fi
        # ${domain_arg} \

        # Ask Let's Encrypt to create certificates, if challenge passed
        certbot certonly --webroot -w "${certbot_path}" \
                --email "${EMAIL}" \
                -d "${DOMAIN}" \
                --non-interactive \
                --rsa-key-size "${rsa_key_size}" \
                --agree-tos \
                --force-renewal
    fi
else
    echo "Certificate related files exists, exiting"
fi
