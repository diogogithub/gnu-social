#!/bin/sh

if /var/www/social/bin/phpunit --coverage-html .test_coverage_report; then
    exit 64
else
    exit 65
fi
