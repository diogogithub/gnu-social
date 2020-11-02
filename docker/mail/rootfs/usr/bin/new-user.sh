#!/bin/sh
USAGE="Usage: $0 EMAIL PASSWORD";

if [ -z "$2" ]
then
  echo "$USAGE";
  exit 1;
fi

DOMAINPART=$(echo "$1" | sed -e "s/^.*\@//")
USERPART=$(echo "$1" | sed -e "s/\@.*$//")

if ! grep -q "^$DOMAINPART" /etc/mail/domains 
then
	echo "This server is not responsible for the domain of this user."
	exit 1
fi

PASSHASH=$(doveadm pw -s SHA512-CRYPT -p "$2")

/usr/bin/new-alias.sh "$1" "$1"
echo "$1  $DOMAINPART/$USERPART/" >> /etc/mail/mailboxes
postmap /etc/mail/mailboxes
echo "$1:$PASSHASH" >> /etc/mail/passwd
mkdir "/var/mail/$DOMAINPART/$USERPART"
chown vmail:vmail "/var/mail/$DOMAINPART/$USERPART"
postfix reload
dovecot reload

echo "User added"
