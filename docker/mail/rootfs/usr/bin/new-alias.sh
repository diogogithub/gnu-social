#!/bin/sh
USAGE="Usage: $0 ALIAS TARGET";

if [ -z "$2" ]
then
  echo "$USAGE";
  exit 1;
fi

DOMAINPART=$(echo $1 | sed -e "s/^.*\@//")

if ! grep "^$DOMAINPART\s" /etc/mail/domains &> /dev/null; then
	echo "This server is not responsible for the domain of this alias."
	exit 1
fi

echo -e "$1\t\t$2" >> /etc/mail/aliases
postmap /etc/mail/aliases
postfix reload

echo "Alias added."
