#|/bin/bash

pushd ./mailserver || exit
docker-compose down
docker pull tvial/docker-mailserver:latest
docker-compose up -d mail
