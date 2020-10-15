#!/bin/bash

if ! command -v swaks &> /dev/null
then
  echo "SWAKS not found."
  exit
fi

read -p "TO:         " to_input
read -p "FROM:       " from_input
read -s -p "PASS:       " pass_input
printf "\n"
read -p "SERVER:     " host_input
read -p "HEADER:     " header_input
read -p "BODY:       " body_input

swaks -t "$to_input" -f "$from_input" -s "$host_input" -au "$from_input" -ap "$pass_input" --header "$header_input" --body "$body_input" -tlso
