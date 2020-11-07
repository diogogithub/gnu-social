#!/bin/sh
USAGE = "Usage: $0 new domain <domain_name>\n     : $0 new user <user_name> <user_password>\n     : $0 new alias <user_alias> <user_name>"

if [ -z "$2" ]
then
  echo "$USAGE";
  exit 1;
fi

if [ "$1" == "new" ]
then
	if [ "$2" == "domain" ]
	then
		docker exec mail new-domain "${*,3}"
	elif [ "$2" == "user" ]
	then
		docker exec mail new-user "${*,3}"
	elif [ "$2" == "alias" ]
	then
		docker exec mail new-alias "${*,3}"	
	fi	
fi
