#!/bin/sh

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

# Prepare postfix
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
