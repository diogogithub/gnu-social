
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

database-force-nuke:
	docker stop $(call translate-container-name,$(strip $(DIR))_worker_1) \
	&& docker exec -it $(call translate-container-name,$(strip $(DIR))_php_1) sh -c "cd /var/www/social; bin/console doctrine:database:drop --force && bin/console doctrine:database:create && bin/console doctrine:schema:update --dump-sql --force && bin/console app:populate_initial_values" \
	&& docker-compose up -d

database-force-schema-update:
	docker exec -it $(call translate-container-name,$(strip $(DIR))_php_1) sh -c "/var/www/social/bin/console doctrine:schema:update --dump-sql --force"

tooling-docker: .PHONY
	@cd docker/tooling && docker-compose up -d > /dev/null 2>&1

stop-tooling: .PHONY
	cd docker/tooling && docker-compose down

tooling-php-shell: tooling-docker
	docker exec -it $(call translate-container-name,tooling_php_1) sh

acceptance-and-accessibility: tooling-docker
	docker exec -it $(call translate-container-name,tooling_php_1) /var/tooling/acceptance_and_accessibility.sh

test: tooling-docker
	docker exec $(call translate-container-name,tooling_php_1) /var/tooling/coverage.sh $(call args,'')

cs-fixer: tooling-docker
	@bin/php-cs-fixer $${CS_FIXER_FILE}

doc-check: tooling-docker
	bin/php-doc-check

phpstan: tooling-docker
	bin/phpstan

remove-var:
	rm -rf var/*

remove-file:
	rm -rf file/*

flush-redis-cache:
	docker exec -it $(call translate-container-name,$(strip $(DIR))_redis_1) sh -c 'redis-cli flushall'

force-nuke-everything: down up flush-redis-cache database-force-nuke remove-var remove-file
