#!/bin/sh

cd /var/www/social || exit 1

printf "Cleaning Redis cache: " && echo "FLUSHALL" | nc redis 6379
yes yes | php bin/console doctrine:fixtures:load || exit 1
php bin/console app:populate_initial_values # since loading fixtures purges the DB

if [ "$#" -eq 0 ] || [ -z "$*" ]; then
    vendor/bin/simple-phpunit -vvv --coverage-html .test_coverage_report
else
    echo "Running with filter"
    vendor/bin/simple-phpunit -vvv --coverage-html .test_coverage_report --filter "$*"
fi
