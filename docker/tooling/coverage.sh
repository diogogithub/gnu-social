#!/bin/sh

cd /var/www/social || exit 1

yes yes | php bin/console doctrine:fixtures:load || exit 1

runuser -u www-data -- vendor/bin/simple-phpunit --ansi -vvv --coverage-html .test_coverage_report
