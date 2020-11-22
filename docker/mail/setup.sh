#!/bin/sh

printf "Domain root: "
read -r domain_root
printf "Subdomain (can be empty): "
read -r sub_domain

if [ -z "$sub_domain" ]
then
  domain="${domain_root}"
else
  domain="${sub_domain}.${domain_root}"
fi

cat > ./docker/mail/mail.env <<EOF
#!/bin/sh
DOMAINNAME=${domain_root}
MAILNAME=${domain}
SSL_CERT=/etc/letsencrypt/live/${domain_root}/fullchain.pem
SSL_KEY=/etc/letsencrypt/live/${domain_root}/privkey.pem
EOF

DOMAINNAME="${domain_root}"
MAILNAME="${domain}"
SSL_CERT="/etc/letsencrypt/live/${domain_root}/fullchain.pem"
SSL_KEY="/etc/letsencrypt/live/${domain_root}/privkey.pem"

# Config postfix
sed -i -e "s#^\s*myhostname\s*=.*#myhostname = $MAILNAME#" ./docker/mail/config/postfix/main.cf
sed -i -e "s#^\s*mydomain\s*=.*#mydomain = $DOMAINNAME#" ./docker/mail/config/postfix/main.cf
sed -i -e "s#^\s*smtpd_tls_cert_file\s*=.*#smtpd_tls_cert_file = $SSL_CERT#" ./docker/mail/config/postfix/main.cf
sed -i -e "s#^\s*smtpd_tls_key_file\s*=.*#smtpd_tls_key_file = $SSL_KEY#" ./docker/mail/config/postfix/main.cf

# Config dovecot
sed -i -e "s#^\s*ssl_cert\s*=.*#ssl_cert = <$SSL_CERT#" ./docker/mail/config/dovecot/dovecot.conf
sed -i -e "s#^\s*ssl_key\s*=.*#ssl_key = <$SSL_KEY#" ./docker/mail/config/dovecot/dovecot.conf
sed -i -e "s#^\s*postmaster_address\s*=.*#postmaster_address = postmaster@$DOMAINNAME#" ./docker/mail/config/dovecot/dovecot.conf

# Config dkim
sed -i -e "s/^.*#HOSTNAME/$MAILNAME#HOSTNAME/" ./docker/mail/config/opendkim/TrustedHosts

# Prepare mail user
touch /etc/mail/aliases /etc/mail/domains /etc/mail/mailboxes /etc/mail/passwd
