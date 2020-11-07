#!/bin/sh
# Config postfix
postconf -e myhostname="$MAILNAME"
postconf -e mydomain="$DOMAINNAME"
postconf -e smtpd_tls_cert_file="$SSL_CERT"
postconf -e smtpd_tls_key_file="$SSL_KEY"

# Config dovecot
sed -i -e "s#^\s*ssl_cert\s*=.*#ssl_cert = <$SSL_CERT#" /etc/mail/dovecot/dovecot.conf
sed -i -e "s#^\s*ssl_key\s*=.*#ssl_key = <$SSL_KEY#" /etc/mail/dovecot/dovecot.conf
sed -i -e "s#^\s*hostname\s*=.*#hostname = $MAILNAME#" /etc/mail/dovecot/dovecot.conf
sed -i -e "s#^\s*postmaster_address\s*=.*#postmaster_address = $POSTMASTER#" /etc/mail/dovecot/dovecot.conf

# Config dkim
sed -i -e "s/#HOSTNAME/$MAILNAME/" /etc/mail/opendkim/TrustedHosts

# Run openssl
if [ ! -e "$SSL_CERT" ]
then
	mkdir -p "$(dirname $SSL_CERT)" "$(dirname $SSL_KEY)"
	openssl req -x509 -nodes -newkey rsa:2018 -days 365 -keyout "$SSL_CERT" -out "$SSL_KEY"
fi

# Run opendkim
if [ ! -e "/var/opendkim/keys/default.private" ]
then
	mkdir -p /var/opendkim/keys
	opendkim-genkey -d "$DOMAINNAME" -D "/var/opendkim/keys"
fi

if [ ! -d "/var/mail/$DOMAINNAME" ]
then
	touch /etc/mail/aliases /etc/mail/domains /etc/mail/mailboxes /etc/mail/passwd
	postmap /etc/mail/aliases && postmap /etc/mail/domains && postmap /etc/mail/mailboxes
	/usr/bin/new-domain.sh "$DOMAINNAME"
fi


# Start services
rsyslogd 				-f /etc/mail/rsyslogd/rsyslog.conf
/usr/sbin/opendkim 		-x /etc/mail/opendkim/opendkim.conf
/usr/sbin/dovecot 		-c /etc/mail/dovecot/dovecot.conf
/usr/sbin/postfix start	-c /etc/mail/postfix
supervisord 			-c /etc/mail/supervisord/supervisord.conf
