#!/bin/sh
USAGE="Usage: $0 DOMAIN";

if [ -z "$1" ]
then
  echo "$USAGE";
  exit 1;
fi

echo "$1  #OK" >> /etc/mail/domains
postmap /etc/mail/domains
mkdir "/var/mail/$1"
chown vmail:vmail "/var/mail/$1"
postfix reload

echo "Domain added."
