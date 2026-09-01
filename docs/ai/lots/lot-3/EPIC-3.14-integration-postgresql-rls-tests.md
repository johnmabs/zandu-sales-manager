# 20. Epic 3.14 — Integration, PostgreSQL & RLS tests

## Objectif

Prouver le Lot 3 sur l’infrastructure réelle.

---

## Étape 3.14.1 — Tests Domain Stock

Couvrir :

```text
initialize once
initialize zero
reject second initialization
adjust positive
adjust negative
reject zero adjustment
reject negative resulting stock
preserve identifiers
```

### Commit proposé

```text
test(inventory): cover stock invariants
```

---

## Étape 3.14.2 — Tests ledger StockMovement

Prouver :

```text
Stock 10
Adjust -3

movement:
previous = 10
quantity = 3
resulting = 7
type = ADJUSTMENT_OUT
```

Et impossibilité d’update/delete métier.

### Commit proposé

```text
test(inventory): verify stock movement ledger
```

---

## Étape 3.14.3 — Atomicité Stock

Injecter échec :

```text
after Stock update
before Movement insert
```

puis :

```text
after Movement
before Audit
```

puis :

```text
after Audit
before Outbox
```

puis avant commit.

Résultat :

```text
rollback total
```

### Commit proposé

```text
test(inventory): verify stock transaction atomicity
```

---

## Étape 3.14.4 — Concurrence Stock

PostgreSQL réel, deux transactions simultanées.

Prouver :

```text
quantityOnHand >= 0
```

et :

```text
ledger
=
état final explicable
```

### Commit proposé

```text
test(inventory): verify stock concurrency
```

---

## Étape 3.14.5 — Tests Domain CashSession

Couvrir :

```text
open
reject second open session
close
calculate expected balance
calculate discrepancy
reject movement after close
reject second close
```

### Commit proposé

```text
test(cash): cover cash session invariants
```

---

## Étape 3.14.6 — Concurrence OpenCashSession

Deux requêtes simultanées sur même CashRegister.

Résultat :

```text
exactly one OPEN session
```

Le contrôle doit être renforcé par PostgreSQL, pas uniquement l’application.

### Commit proposé

```text
test(cash): verify concurrent session opening
```

---

## Étape 3.14.7 — Tests CashMovement

Couvrir :

```text
cash in
cash out
withdrawal
invalid amount
closed session
wrong tenant
wrong store
currency mismatch
expected balance
```

### Commit proposé

```text
test(cash): verify cash movements
```

---

## Étape 3.14.8 — Atomicité Cash

Injecter erreurs entre :

```text
CashMovement insert
Audit
Outbox
CashSession close
```

Aucun effet partiel.

### Commit proposé

```text
test(cash): verify cash transaction atomicity
```

---

## Étape 3.14.9 — Tenant isolation

Créer :

```text
Tenant A
Tenant B
```

Tester :

```text
Tenant B reads Stock A
→ NOT_FOUND
```

```text
Tenant B adjusts Stock A
→ NOT_FOUND
```

```text
Tenant B reads CashSession A
→ NOT_FOUND
```

```text
Tenant B records movement in Session A
→ NOT_FOUND
```

Aucune fuite d’existence.

### Commit proposé

```text
test(tenant): enforce inventory and cash isolation
```

---

## Étape 3.14.10 — RLS

Tester toutes les tables tenant-owned :

```text
inventory.stock
inventory.stock_movement

cash_management.cash_register
cash_management.cash_session
cash_management.cash_movement
```

Vérifier :

- RLS active ;
- FORCE RLS selon convention ;
- transaction-local tenant context ;
- deux connexions ;
- rollback ;
- lecture et écriture cross-tenant impossibles.

### Commit proposé

```text
test(tenant): verify inventory and cash PostgreSQL RLS
```

---

## Étape 3.14.11 — Scope Store

Utilisateur limité :

```text
SELECTED_STORES
→ Store A
```

Peut :

```text
read/adjust Store A selon permissions
```

Ne peut pas :

```text
read/adjust Store B
open register Store B
```

### Commit proposé

```text
test(access): verify inventory and cash store scopes
```

---

## Étape 3.14.12 — StoreClosure

Tester :

```text
Stock > 0
→ blocker
```

```text
CashSession OPEN
→ blocker
```

```text
Stock = 0
+
no open session
→ ces blockers absents
```

### Commit proposé

```text
test(store): verify inventory and cash closure blockers
```

---

## Étape 3.14.13 — Contract tests Lot 4

Tester les contrats :

```text
InventoryStockConsumer
CashMovementRecorder
```

sans Domain Sales.

### Commit proposé

```text
test(contracts): verify Lot 4 inventory and cash contracts
```

---

## Definition of Done — Epic 3.14

- domain tests ;
- persistence tests ;
- real PostgreSQL ;
- concurrency tests ;
- idempotence ;
- rollback ;
- RLS ;
- tenant isolation ;
- Store scopes ;
- StoreClosure ;
- contract tests ;
- architecture tests ;
- CI verte.

---
