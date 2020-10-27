#!/bin/sh

postconf -e myhostname="$MAILNAME"
postconf -e mydomain="$DOMAINNAME"
postconf -e smtpd_tls_cert_file="$SSL_CERT"
postconf -e smtpd_tls_key_file="$SSL_KEY"

touch /etc/mail/aliases /etc/mail/domains /etc/mail/mailbox /etc/mail/passwd
if [ ! -d "/var/mail/$DOMAINNAME" ]
then
	echo "$DOMAINNAME  #OK" >> /etc/mail/domains
	mkdir "/var/mail/$DOMAINNAME"
	chown vmail:vmail "/var/mail/$DOMAINNAME"
fi
postmap /etc/mail/aliases && postmap /etc/mail/domains && postmap /etc/mail/mailbox

sed -i -e "s#^\s*ssl_cert\s*=.*#ssl_cert = $SSL_CERT#" /etc/dovecot/dovecot.conf
sed -i -e "s#^\s*ssl_key\s*=.*#ssl_key = $SSL_KEY#" /etc/dovecot/dovecot.conf
sed -i -e "s#^\s*hostname\s*=.*#hostname = $MAILNAME#" /etc/dovecot/dovecot.conf
sed -i -e "s#^\s*postmaster_address\s*=.*#postmaster_address = $POSTMASTER#" /etc/dovecot/dovecot.conf

sed -i -e "s/#HOSTNAME/$MAILNAME/" /etc/opendkim/TrustedHosts

if [ ! -e "/etc/opendkim/keys/default.private" ]
then
	opendkim-genkey -d "$DOMAINNAME" -D "/etc/opendkim/keys"
fi

# Start services

rsyslogd  	-f /etc/rsyslogd/rsyslogd.conf
/usr/sbin/opendkim 	#-x /etc/opendkim/opendkim.conf
dovecot 	-c /etc/dovecot/dovecot.conf
postfix start -c /etc/postfix
supervisord -c /etc/supervisord/supervisord.conf
