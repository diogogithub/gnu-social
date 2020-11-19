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

cat > mail.env <<EOF
#!/bin/sh
DOMAINNAME=${domain_root}
MAILNAME=${domain}
SSL_CERT=/etc/letsencrypt/live/${domain_root}/fullchain.pem
SSL_KEY=/etc/letsencrypt/live/${domain_root}/privkey.pem
EOF


# Config postfix
sed -i -e "s#^\s*myhostname\s*=.*#myhostname = $MAILNAME#" rootfs/etc/mail/postfix/main.cf
sed -i -e "s#^\s*mydomain\s*=.*#mydomain = $DOMAINNAME#" rootfs/etc/mail/postfix/main.cf
sed -i -e "s#^\s*smtpd_tls_cert_file\s*=.*#smtpd_tls_cert_file = $SSL_CERT#" rootfs/etc/mail/postfix/main.cf
sed -i -e "s#^\s*smtpd_tls_key_file\s*=.*#smtpd_tls_key_file = $SSL_KEY#" rootfs/etc/mail/postfix/main.cf

# Config dovecot
sed -i -e "s#^\s*ssl_cert\s*=.*#ssl_cert = <$SSL_CERT#" rootfs/etc/mail/dovecot/dovecot.conf
sed -i -e "s#^\s*ssl_key\s*=.*#ssl_key = <$SSL_KEY#" rootfs/etc/mail/dovecot/dovecot.conf
sed -i -e "s#^\s*hostname\s*=.*#hostname = $MAILNAME#" rootfs/etc/mail/dovecot/dovecot.conf
sed -i -e "s#^\s*postmaster_address\s*=.*#postmaster_address = $POSTMASTER#" rootfs/etc/mail/dovecot/dovecot.conf

# Config dkim
sed -i -e "s/#HOSTNAME/$MAILNAME/" rootfs/etc/mail/opendkim/TrustedHosts
