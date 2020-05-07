#!/bin/sh

sed -ri "s/%hostname%/$domain/" /etc/nginx/conf.d/challenge.conf

nginx

rsa_key_size=4096
certbot_path="/var/www/certbot"
lets_path="/etc/letsencrypt"

echo "Starting bootstrap"

if [ ! -e "${lets_path}/live//options-ssl-nginx.conf" ] \
    || [ ! -e "${lets_path}/live/ssl-dhparams.pem" ]; then

    echo "### Downloading recommended TLS parameters ..."
    mkdir -p "${lets_path}/live"

    curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot-nginx/certbot_nginx/_internal/tls_configs/options-ssl-nginx.conf > \
         "${lets_path}/options-ssl-nginx.conf"
    curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot/certbot/ssl-dhparams.pem > \
         "${lets_path}/ssl-dhparams.pem"

    echo "### Creating dummy certificate for ${root_domain} ..."
    openssl req -x509 -nodes -newkey rsa:1024 -days 1\
            -keyout "${lets_path}/live/privkey.pem" \
            -out "${lets_path}/live/fullchain.pem" -subj '/CN=localhost'

    nginx -s reload

    rm -Rf "${lets_path}/live/${root_domain}"
    rm -Rf "${lets_path}/archive/${root_domain}"
    rm -Rf "${lets_path}/renewal/${root_domain}.conf"

    echo "### Requesting Let's Encrypt certificate for $root_domain ..."
    # Format domain_args with the cartesian product of `root_domain` and `subdomains`

    email_arg="--email ${email}"
    domain_arg=$([ "${domain_root}" = "${domain}" ] && printf "-d ${domain_root}" || printf "-d ${domain_root} -d ${domain}")

    # Ask Let's Encrypt to create certificates, if challenge passed
    certbot certonly --webroot -w /var/www/certbot \
            ${email_arg} \
            ${domain_arg} \
            --non-interactive \
            --rsa-key-size ${rsa_key_size} \
            --agree-tos \
            --force-renewal

else
    echo "Certificate related files exists, exiting"
fi
