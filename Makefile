.PHONY: install start stop restart test lint architecture shell logs ps database-create database-migrate database-rollback database-status database-sql

install:
	docker compose build
	docker compose run --rm backend composer install

start:
	docker compose up -d

stop:
	docker compose down

restart:
	docker compose restart

test:
	docker compose exec backend php bin/phpunit

lint:
	docker compose exec backend composer validate --no-check-publish
	docker compose exec backend php bin/console lint:container

shell:
	docker compose exec backend bash

logs:
	docker compose logs -f

ps:
	docker compose ps

architecture:
	docker compose exec backend vendor/bin/deptrac analyse \
		--config-file=deptrac.layers.php \
		--no-cache
	docker compose exec backend vendor/bin/deptrac analyse \
		--config-file=deptrac.modules.php \
		--no-cache

database-create:
	docker compose exec backend php bin/console doctrine:database:create --if-not-exists

database-migrate:
	docker compose exec backend php bin/console doctrine:migrations:migrate --no-interaction

database-rollback:
	docker compose exec backend php bin/console doctrine:migrations:migrate prev --no-interaction

database-status:
	docker compose exec backend php bin/console doctrine:migrations:status

database-sql:
	docker compose exec backend php bin/console dbal:run-sql "$(SQL)"
