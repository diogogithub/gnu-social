#!/bin/sh

cd /var/www/social || exit 1

printf "Cleaning Redis cache: " && echo "FLUSHALL" | nc redis 6379
yes yes | php bin/console doctrine:fixtures:load || exit 1

echo yooo

if [ "$#" -eq 0 ]; then
    runuser -u www-data -- vendor/bin/simple-phpunit -vvv --coverage-html .test_coverage_report
else
    echo "Running with filter"
    runuser -u www-data -- vendor/bin/simple-phpunit -vvv --coverage-html .test_coverage_report --filter "$*"
fi
