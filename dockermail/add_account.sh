#!/bin/bash

read -p "EMAIL:         " user
read -s -p "PASS:         " password
printf "\n"

bash mailserver/setup.sh email add "$user" "$password"

