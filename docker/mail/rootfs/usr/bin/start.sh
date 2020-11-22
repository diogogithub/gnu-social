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

postmap /etc/mail/aliases /etc/mail/domains /etc/mail/mailboxes /etc/mail/passwd
postfix reload
dovecot reload

# Run services
s6-svscan /etc/service
