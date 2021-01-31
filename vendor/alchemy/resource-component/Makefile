.PHONY: test clean

test:
	php -dzend_extension=xdebug.so ./vendor/bin/phpunit --coverage-html=build --coverage-text --coverage-clover=build/coverage.clover

clean:
	rm -rf build/
	rm -rf ocular.php

scrutinizer:
	wget https://scrutinizer-ci.com/ocular.phar
	php ocular.phar code-coverage:upload --format=php-clover build/coverage.clover
