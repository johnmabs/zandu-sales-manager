.PHONY: install start stop restart test lint quality security architecture auth-keys staging-test staging-image-test backup-restore-test shell logs ps database-create database-migrate database-rollback database-status database-sql frontend-openapi frontend-openapi-check

install:
	docker compose build
	docker compose run --rm backend composer install
	docker compose run --rm backend php bin/console lexik:jwt:generate-keypair --skip-if-exists

auth-keys:
	docker compose run --rm backend php bin/console lexik:jwt:generate-keypair --skip-if-exists

frontend-openapi:
	docker compose exec -T backend php bin/console api:openapi:export --output=/app/var/openapi.json
	cd frontend && pnpm --filter @zandu/api-client generate:openapi ../../../backend/var/openapi.json --output src/generated/schema.ts
	cd frontend && pnpm exec prettier --write packages/api-client/src/generated/schema.ts

frontend-openapi-check: frontend-openapi
	git diff --exit-code -- frontend/packages/api-client/src/generated/schema.ts

staging-test:
	docker compose build backend
	$(MAKE) staging-image-test

staging-image-test:
	sh scripts/test-staging.sh

backup-restore-test:
	sh scripts/test-backup-restore.sh

start:
	docker compose up -d

stop:
	docker compose down

restart:
	docker compose restart

test:
	docker compose exec backend php bin/console doctrine:database:create --if-not-exists --env=test
	docker compose exec backend php bin/console doctrine:migrations:migrate --no-interaction --env=test
	docker compose exec -e APP_ENV=test backend php bin/phpunit

lint:
	docker compose exec backend composer validate --no-check-publish
	docker compose exec backend php bin/console lint:container

quality:
	docker compose exec backend vendor/bin/php-cs-fixer check --sequential --show-progress=none
	docker compose exec backend vendor/bin/phpstan analyse --configuration=phpstan.dist.neon --no-progress --debug --memory-limit=512M

security:
	docker compose exec backend composer audit --locked

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
