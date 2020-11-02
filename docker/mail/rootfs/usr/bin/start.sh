#!/bin/sh

CERTBOT="/etc/letsencrypt/live/$domain/fullchain.pem"
KEYBOT="/etc/letsencrypt/live/$domain/privkey.pem"

# Config postfix
postconf -e myhostname="$MAILNAME"
postconf -e mydomain="$DOMAINNAME"
postconf -e smtpd_tls_cert_file="$CERTBOT"
postconf -e smtpd_tls_key_file="$KEYBOT"

# Config dovecot
sed -i -e "s#^\s*ssl_cert\s*=.*#ssl_cert = <$CERTBOT#" /etc/dovecot/dovecot.conf
sed -i -e "s#^\s*ssl_key\s*=.*#ssl_key = <$KEYBOT#" /etc/dovecot/dovecot.conf
sed -i -e "s#^\s*hostname\s*=.*#hostname = $MAILNAME#" /etc/dovecot/dovecot.conf
sed -i -e "s#^\s*postmaster_address\s*=.*#postmaster_address = $POSTMASTER#" /etc/dovecot/dovecot.conf

# Config dkim
sed -i -e "s/#HOSTNAME/$MAILNAME/" /etc/opendkim/TrustedHosts

# Run openssl
if [ $signed -eq 0 ]
then
	openssl req -newkey rsa:2048 -new -nodes -x509 -days 3650 -keyout "$SSL_KEY" -out "$SSL_CERT" \
		-subj "/C=UK/ST=England/L=London/O=OrgName/OU=IT Department/CN=$MAILNAME"
	postconf -e smtpd_tls_cert_file="$SSL_CERT"
	postconf -e smtpd_tls_key_file="$SSL_KEY"
	sed -i -e "s#^\s*ssl_cert\s*=.*#ssl_cert = <$SSL_CERT#" /etc/dovecot/dovecot.conf
	sed -i -e "s#^\s*ssl_key\s*=.*#ssl_key = <$SSL_KEY#" /etc/dovecot/dovecot.conf
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
rsyslogd 				-f /etc/rsyslogd/rsyslog.conf
/usr/sbin/opendkim 		-x /etc/opendkim/opendkim.conf
/usr/sbin/dovecot 		-c /etc/dovecot/dovecot.conf
/usr/sbin/postfix start	-c /etc/postfix
supervisord 			-c /etc/supervisord/supervisord.conf
