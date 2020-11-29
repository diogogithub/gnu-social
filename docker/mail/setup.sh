#!/bin/sh

ROOT="$(git rev-parse --show-toplevel)"
. $ROOT/docker/mail/mail.env

cd "${0%/*}"

if [ -z "${MAIL_SUBDOMAIN}" ]
then
  domain="${MAIL_DOMAIN_ROOT}"
else
  domain="${MAIL_SUBDOMAIN}.${MAIL_DOMAIN_ROOT}"
fi

PASSHASH=$(mkpasswd -m sha-512 -S "" -R 5000 ${MAIL_PASSWORD})

cat > mail.env <<EOF
#!/bin/sh
DOMAINNAME=${MAIL_DOMAIN_ROOT}
MAILNAME=${domain}
SSL_CERT=/etc/letsencrypt/live/${MAIL_DOMAIN_ROOT}/fullchain.pem
SSL_KEY=/etc/letsencrypt/live/${MAIL_DOMAIN_ROOT}/privkey.pem
MAIL_USER="${MAIL_USER}"
USER="${MAIL_USER}@${MAIL_DOMAIN_ROOT}"
EOF

. $ROOT/docker/mail/mail.env

# Config postfix
sed -i -e "s#^\s*myhostname\s*=.*#myhostname = ${MAILNAME}#" config/postfix/main.cf
sed -i -e "s#^\s*mydomain\s*=.*#mydomain = ${DOMAINNAME}#" config/postfix/main.cf
sed -i -e "s#^\s*smtpd_tls_cert_file\s*=.*#smtpd_tls_cert_file = ${SSL_CERT}#" config/postfix/main.cf
sed -i -e "s#^\s*smtpd_tls_key_file\s*=.*#smtpd_tls_key_file = ${SSL_KEY}#" config/postfix/main.cf

# Config dovecot
sed -i -e "s#^\s*ssl_cert\s*=.*#ssl_cert = <${SSL_CERT}#" config/dovecot/dovecot.conf
sed -i -e "s#^\s*ssl_key\s*=.*#ssl_key = <${SSL_KEY}#" config/dovecot/dovecot.conf
sed -i -e "s#^\s*postmaster_address\s*=.*#postmaster_address = postmaster@${DOMAINNAME}#" config/dovecot/dovecot.conf

# Config dkim
sed -i -e "s/^.*#HOSTNAME/${MAILNAME}#HOSTNAME/" config/opendkim/TrustedHosts

# Prepare mail user
touch config/aliases config/domains config/mailboxes config/passwd
echo "${DOMAINNAME}  #OK" > config/domains
echo "${USER}  ${USER}" > config/aliases
echo "${USER}  ${DOMAINNAME}/${MAIL_USER}/" > config/mailboxes
echo "${USER}:${PASSHASH}" > config/passwd
