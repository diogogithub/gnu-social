
DIR=$(strip $(notdir $(CURDIR))) # Seems a bit hack-ish, but `basename` works differently

translate-container-name = $$(if docker container inspect $(1) > /dev/null 2>&1; then echo $(1); else echo $(1) | sed 'y/_/-/' ; fi)
args = `arg="$(filter-out $@,$(MAKECMDGOALS))" && echo $${arg:-${1}}`

%:
	@:

.PHONY:
	@if ! docker info > /dev/null; then echo "Docker does not seem to be running"; exit 1; fi

up: .PHONY
	docker-compose up -d

down: .PHONY
	docker-compose down

redis-shell:
	docker exec -it $(call translate-container-name,$(strip $(DIR))_redis_1) sh -c 'redis-cli'

php-repl: .PHONY
	docker exec -it $(call translate-container-name,$(strip $(DIR))_php_1) sh -c '/var/www/social/bin/console psysh'

php-shell: .PHONY
	docker exec -it $(call translate-container-name,$(strip $(DIR))_php_1) sh -c 'cd /var/www/social; sh'

psql-shell: .PHONY
	docker exec -it $(call translate-container-name,$(strip $(DIR))_db_1) sh -c "psql -U postgres social"

database-force-schema-update:
	docker exec -it $(call translate-container-name,$(strip $(DIR))_php_1) sh -c "/var/www/social/bin/console doctrine:schema:update --dump-sql --force"

tooling-docker: .PHONY
	@cd docker/tooling && docker-compose up -d > /dev/null 2>&1

test: tooling-docker
	docker exec $(call translate-container-name,tooling_php_1) /var/tooling/coverage.sh $(call args,'.*')

doc-check:
	bin/php-doc-check src components plugins

phpstan: tooling-docker
	bin/phpstan

stop-tooling: .PHONY
	cd docker/tooling && docker-compose down
