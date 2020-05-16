#!/bin/sh

. bootstrap.env

sed -ri "s/%hostname%/${domain}/" /etc/nginx/conf.d/challenge.conf

nginx

rsa_key_size=4096
certbot_path="/var/www/certbot"
lets_path="/etc/letsencrypt"

echo "Starting bootstrap"

if [ ! -e "$lets_path/live//options-ssl-nginx.conf" ] ||  [ ! -e "$lets_path/live/ssl-dhparams.pem" ]
then

  echo "### Downloading recommended TLS parameters ..."
  mkdir -p "${lets_path}/live/${domain_root}"

  curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot-nginx/certbot_nginx/_internal/tls_configs/options-ssl-nginx.conf >"$lets_path/options-ssl-nginx.conf"
  curl -s https://raw.githubusercontent.com/certbot/certbot/master/certbot/certbot/ssl-dhparams.pem >"$lets_path/ssl-dhparams.pem"

  if [ ${signed} -eq 0 ]
  then
    echo "### Creating self signed certificate for ${domain_root} ..."
    openssl req -x509 -nodes -newkey rsa:$rsa_key_size -days 365 \
      -keyout "${lets_path}/live/${domain_root}/privkey.pem" \
      -out "${lets_path}/live/${domain_root}/fullchain.pem" -subj "/CN=${domain_root}"

  else
    echo "### Creating dummy certificate for ${domain_root} ..."
    openssl req -x509 -nodes -newkey rsa:1024 -days 1 \
      -keyout "${lets_path}/live/${domain_root}/privkey.pem" \
      -out "${lets_path}/live/${domain_root}/fullchain.pem" -subj '/CN=localhost'

    nginx -s reload

    rm -Rf "${lets_path}/live/${domain_root}"
    rm -Rf "${lets_path}/archive/${domain_root}"
    rm -Rf "${lets_path}/renewal/${domain_root}.conf"

    echo "### Requesting Let's Encrypt certificate for ${domain_root} ..."
    # Format domain_args with the cartesian product of `domain_root` and `subdomains`

    if [ "${domain_root}" = "${domain}" ]; then domain_arg="-d ${domain_root}"; else domain_arg="-d ${domain_root} -d ${domain}"; fi

    # Ask Let's Encrypt to create certificates, if challenge passed
    certbot certonly --webroot -w "${certbot_path}" \
            --email "${email}" \
            ${domain_arg} \
            --non-interactive \
            --rsa-key-size "${rsa_key_size}" \
            --agree-tos \
            --force-renewal
  fi

else
  echo "Certificate related files exists, exiting"
fi
