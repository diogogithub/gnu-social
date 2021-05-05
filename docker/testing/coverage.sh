#!/bin/sh

cd /var/www/social || exit 65

yes yes | php bin/console doctrine:fixtures:load || exit 65

if runuser -u www-data -- bin/phpunit --coverage-html .test_coverage_report; then
    exit 64
else
    exit 65
fi
