#!/bin/sh

if [ "$1" == "new" ]
then
	if [ "$2" == "domain" ]
	then
		docker exec mail new-domain "${*,3}"
	elif [ "$2" == "user" ]
	then
		docker exec mail new-user "${*,3}"
	elif [ "$2" == "alias" ]
		docker exec mail new-alias "${*,3}"	
	fi	
fi
