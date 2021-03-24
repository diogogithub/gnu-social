#!/bin/sh

set -x

touch /etc/passwd
adduser nobody
adduser postfix
adduser dovecot
adduser opendkim

addgroup dovecot postfix
addgroup opendkim postfix
chown postfix:postfix "/var/mail/${MAIL_DOMAIN}"
mkdir -p "/var/opendkim/keys/"
chown opendkim:opendkim "/var/opendkim/keys/"
chmod +x "/etc/service/postfix/run"
chmod +x "/etc/service/dovecot/run"
chmod +x "/etc/service/opendkim/run"
chmod +x "/etc/service/rsyslog/run"
chmod +x "/usr/bin/entrypoint.sh"
mkdir -p "/var/mail/${MAIL_DOMAIN}/${MAIL_USER}"

# Config postfix
sed -ri \
    -e "s,%hostname%,${MAIL_DOMAIN}," \
    -e "s,%domain_root%,${MAIL_DOMAIN_ROOT}," \
    -e "s,%cert_file%,${SSL_CERT}," \
    -e "s,%key_file%,${SSL_KEY}," \
    -e "s,%postmaster_address%,${MAIL_ADDRESS}," \
    /etc/postfix/main.cf /etc/dovecot/dovecot.conf /etc/mail/opendkim/TrustedHosts

# Prepare mail user
touch /etc/mail/aliases /etc/mail/domains /etc/mail/mailboxes /etc/mail/passwd
echo "${MAIL_DOMAIN} #OK" > /etc/mail/domains
if ! grep -Fq 'root:' /etc/mail/aliases; then echo "root: ${MAIL_USER}" >> /etc/mail/aliases; fi
echo "${MAIL_USER} ${MAIL_DOMAIN}/${MAIL_USER}/" > /etc/mail/mailboxes
echo "${MAIL_USER}:${HASHED_PASSWORD}" > /etc/mail/passwd

# Run opendkim
if [ ! -e "/var/opendkim/keys/default.private" ]
then
	opendkim-genkey -d "${MAIL_DOMAIN}" -D "/var/opendkim/keys/"
fi

newaliases
postmap /etc/mail/aliases /etc/mail/domains /etc/mail/mailboxes /etc/mail/passwd
postfix reload
dovecot

# # Run services
# s6-svscan /etc/service
