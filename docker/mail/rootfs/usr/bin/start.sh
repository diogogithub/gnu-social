#!/bin/sh

# Config postfix
postconf -e myhostname="$MAILNAME"
postconf -e mydomain="$DOMAINNAME"
postconf -e smtpd_tls_cert_file="$SSL_CERT"
postconf -e smtpd_tls_key_file="$SSL_KEY"

# Config dovecot
sed -i -e "s#^\s*ssl_cert\s*=.*#ssl_cert = $SSL_CERT#" /etc/dovecot/dovecot.conf
sed -i -e "s#^\s*ssl_key\s*=.*#ssl_key = $SSL_KEY#" /etc/dovecot/dovecot.conf
sed -i -e "s#^\s*hostname\s*=.*#hostname = $MAILNAME#" /etc/dovecot/dovecot.conf
sed -i -e "s#^\s*postmaster_address\s*=.*#postmaster_address = $POSTMASTER#" /etc/dovecot/dovecot.conf

# Config dkim
sed -i -e "s/#HOSTNAME/$MAILNAME/" /etc/opendkim/TrustedHosts

# Run openssl
if [ ! -e /etc/ssl/.ssl-generated ]
then
	openssl genrsa -des3 -passout pass:asdf -out /etc/ssl/mail.pass.key 2048 && \
	openssl rsa -passin pass:asdf -in /etc/ssl/mail.pass.key -out "$SSL_KEY"
	rm /etc/ssl/mail.pass.key
	openssl req -new -key "$SSL_KEY" -out /etc/ssl/mail.csr \
	  -subj "/C=UK/ST=England/L=London/O=OrgName/OU=IT Department/CN=$MAILNAME"
	openssl x509 -req -days 365 -in /etc/ssl/mail.csr -signkey "$SSL_KEY" -out "$SSL_CERT"
	echo "Do not remove this file." >> /etc/ssl/.ssl-generated
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
