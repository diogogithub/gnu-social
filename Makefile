DIR=$(strip $(notdir $(CURDIR))) # Seems a bit hack-ish, but `basename` works differently

.PHONY:
	if ! docker info > /dev/null; then echo "Docker does not seem to be running"; exit 1; fi

up: .PHONY
	docker-compose up -d

down: .PHONY
	docker-compose down

redis-shell:
	docker exec -it $(strip $(DIR))_redis_1 sh -c 'redis-cli'

php-repl: .PHONY
	docker exec -it $(strip $(DIR))_php_1 sh -c '/var/www/social/bin/console psysh'

php-shell: .PHONY
	docker exec -it $(strip $(DIR))_php_1 sh -c 'cd /var/www/social; sh'

psql-shell: .PHONY
	docker exec -it $(strip $(DIR))_db_1 sh -c "psql -U postgres social"

database-force-schema-update:
	docker exec -it $(strip $(DIR))_php_1 sh -c "/var/www/social/bin/console doctrine:schema:update --dump-sql --force"

test: .PHONY
	cd docker/testing && docker-compose run php; docker-compose down

stop-test: .PHONY
	cd docker/testing && docker-compose down
