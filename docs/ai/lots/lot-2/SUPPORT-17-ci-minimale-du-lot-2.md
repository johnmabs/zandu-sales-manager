# 17. CI minimale du Lot 2

Le pipeline existant reste actif.

Ajouter progressivement :

```text
architecture fitness tests
        ↓
Catalog domain tests
        ↓
Pricing domain tests
        ↓
authorization tests
        ↓
PostgreSQL integration tests
        ↓
tenant isolation / RLS tests
        ↓
transaction / outbox tests
        ↓
API contract tests
```

Validations minimales :

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

Adapter les commandes exactes aux scripts déjà présents dans le repository.

---
