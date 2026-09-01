# 16. Epic 3.10 — StoreClosure integration

## Objectif

Brancher les vrais blockers Inventory et Cash au `StoreClosure` préparé au Lot 1.

---

## Étape 3.10.1 — Inventory blocker provider

Implémenter le contrat public attendu par Organization :

```text
StoreClosureBlockerProvider
```

ou son équivalent existant.

Inventory retourne un blocker si :

```text
exists Stock
where organizationId = ?
and storeId = ?
and quantityOnHand > 0
```

Blocker possible :

```text
STOCK_REMAINING
```

avec uniquement les informations non sensibles nécessaires.

### Commit proposé

```text
feat(inventory): provide store closure stock blocker
```

---

## Étape 3.10.2 — Cash blocker provider

Cash Management retourne un blocker si :

```text
CashSession OPEN
```

pour le store.

Blocker :

```text
OPEN_CASH_SESSION
```

### Commit proposé

```text
feat(cash): provide store closure cash blocker
```

---

## Étape 3.10.3 — Store suspendu

Une suspension de Store doit :

```text
refuser
OpenCashSession
InitializeStock
AdjustStock
```

sauf opérations de terminaison/remédiation explicitement autorisées.

Doit rester autorisé selon baseline :

```text
CloseCashSession
```

Un stock existant reste consultable.

### Commit proposé

```text
feat(operations): enforce inventory and cash store guards
```

---

## Definition of Done — Epic 3.10

- Stock restant bloque fermeture ;
- session cash ouverte bloque fermeture ;
- CloseCashSession reste possible pendant suspension ;
- aucun repository externe importé par Organization ;
- contrats publics utilisés ;
- tests StoreClosure consolidés.

---
