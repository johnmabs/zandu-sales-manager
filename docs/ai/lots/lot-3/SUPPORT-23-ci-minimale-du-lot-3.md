# 23. CI minimale du Lot 3

Pipeline :

```text
architecture fitness tests
        ↓
Inventory domain tests
        ↓
Cash domain tests
        ↓
authorization tests
        ↓
contract tests
        ↓
PostgreSQL integration tests
        ↓
stock concurrency tests
        ↓
cash concurrency tests
        ↓
tenant isolation / RLS
        ↓
transaction / rollback tests
        ↓
API contract tests
```

Validations :

```bash
php bin/phpunit
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix --dry-run --diff
vendor/bin/deptrac analyse
composer validate --no-check-publish
composer audit
php bin/console lint:container
php bin/console doctrine:schema:validate
```

Adapter aux commandes réelles du repository.

---
