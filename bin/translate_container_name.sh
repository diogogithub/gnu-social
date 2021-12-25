#!/bin/sh

translate_container_name () {
    if docker container inspect "$1" > /dev/null 2>&1; then
        echo "$1"
    else
        echo "$1" | sed 'y/_/-/'
    fi
}
