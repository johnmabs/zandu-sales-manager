# 36. Rôles système

Mapping à adapter aux policies existantes.

Exemple :

```text
ORGANIZATION_OWNER
→ all

STORE_MANAGER
→ transfers + counts dans scope

CASHIER
→ aucun transfer/count par défaut
   ou STOCK_COUNT_RECORD uniquement si explicitement décidé

ACCOUNTANT
→ lecture inventory/cost selon permissions
```

Domain ne contient jamais de check :

```text
if role == MANAGER
```

---
