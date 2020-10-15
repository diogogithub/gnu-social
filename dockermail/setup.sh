#!/bin/bash

mkdir ./mailserver
pushd ./mailserver || exit
curl -o setup.sh https://raw.githubusercontent.com/tomav/docker-mailserver/master/setup.sh && chmod a+x ./setup.sh
curl -o docker-compose.yml https://raw.githubusercontent.com/tomav/docker-mailserver/master/docker-compose.yml.dist
curl -o env-mailserver https://raw.githubusercontent.com/tomav/docker-mailserver/master/env-mailserver.dist

if [ -f .env ]; then
	rm ./.env
fi

echo "CONTAINER_NAME=mail" >> .env
read -r -p "HOSTNAME: "
echo "HOSTNAME=$REPLY" >> .env
read -r -p "DOMAIN: "
echo "DOMAINNAME=$REPLY" >> .env

printf "\nSetup the first account.\n"
read -r -p "Enter Email: " user

bash ./setup.sh email add "$user"
bash ./setup.sh config dkim
