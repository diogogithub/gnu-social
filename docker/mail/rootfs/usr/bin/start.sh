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
	/usr/bin/new-domain.sh "$DOMAINNAME"
fi

# Run services
s6-svscan /etc/service
