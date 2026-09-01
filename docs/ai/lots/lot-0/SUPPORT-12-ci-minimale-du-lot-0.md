# 12. CI minimale du Lot 0

Le pipeline doit évoluer progressivement avec les Epics.

Ordre cible :

```text
composer validate
↓
coding standards
↓
static analysis
↓
architecture tests
↓
unit tests
↓
PostgreSQL integration tests
↓
selected concurrency tests
↓
build Docker image
↓
dependency/security checks
```

Commits atomiques possibles :

```text
ci(quality): add Composer validation
ci(quality): add coding standard checks
ci(quality): add static analysis
ci(test): add unit and integration test jobs
ci(architecture): run architecture fitness tests
ci(build): build backend Docker image
```

---
