# 15. CI minimale du Lot 1

Le pipeline existant du Lot 0 doit rester actif.

Ajouter progressivement :

```text
architecture fitness tests
↓
domain unit tests
↓
authorization tests
↓
PostgreSQL integration tests
↓
tenant isolation tests
↓
invitation security tests
↓
transaction / outbox tests
↓
API contract tests
```

Commits atomiques possibles :

```text
ci(test): add organization domain test job
ci(test): add tenant isolation integration tests
ci(test): add administration API contract tests
```

Ne créer ces commits séparément que si le pipeline nécessite réellement une évolution structurelle.

---
