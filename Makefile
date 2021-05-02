
test:
	cd docker/testing && docker-compose run php && docker-compose down

stop-test:
	cd docker/testing && docker-compose down
