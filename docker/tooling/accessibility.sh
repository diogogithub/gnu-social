#!/bin/bash

rm -rf /screenshots/diff
mv -fn /screenshots/new /screenshots/old
mkdir -p /screenshots/diff
mkdir -p /screenshots/new
chmod 777 -R /screenshots

/generate_pa11y-ci-config.php

su puppet -c '/usr/local/bin/pa11y-ci -c /pa11y/config.json'

cd /screenshots/new || exit 1

for f in *; do
    XC=$(compare -metric NCC "/screenshots/old/${f}" "${f}" "/screenshots/diff/${f}" 2>&1)
    if [ 1 -eq "$(echo "${XC} < 0.999" | bc)" ]; then
        printf '\e[33mCheck file for differences: \e]8;;%s\e\\%s\e]8;;\e\\\e[0m\n' "file:tests/screenshots/diff/${f}" "tests/screenshots/diff/${f}"
    fi
done
