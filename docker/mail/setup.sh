#!/bin/sh
cd "${0%/*}"

printf "Domain root: "
read -r domain_root
printf "Subdomain (can be empty): "
read -r sub_domain

printf "E-mail user (name without @domain): "
read -r user
printf "E-mail pass: "
read -r pass

if [ -z "${sub_domain}" ]
then
  domain="${domain_root}"
else
  domain="${sub_domain}.${domain_root}"
fi

cat > mail.env <<EOF
#!/bin/sh
DOMAINNAME=${domain_root}
MAILNAME=${domain}
SSL_CERT=/etc/letsencrypt/live/${domain_root}/fullchain.pem
SSL_KEY=/etc/letsencrypt/live/${domain_root}/privkey.pem
USER="${user}@${domain_root}"
EOF

DOMAINNAME="${domain_root}"
MAILNAME="${domain}"
SSL_CERT="/etc/letsencrypt/live/${domain_root}/fullchain.pem"
SSL_KEY="/etc/letsencrypt/live/${domain_root}/privkey.pem"

USER="${user}@${DOMAINNAME}"
PASSHASH=$(mkpasswd -m sha-512 -S "" -R 5000 ${pass})

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
echo "${USER}  ${DOMAINNAME}/${user}/" > config/mailboxes
echo "${USER}:${PASSHASH}" > config/passwd
