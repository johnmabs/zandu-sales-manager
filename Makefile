.PHONY: install start stop restart test lint shell logs ps

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