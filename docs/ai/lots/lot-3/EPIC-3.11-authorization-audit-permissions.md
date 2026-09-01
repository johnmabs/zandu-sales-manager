# 17. Epic 3.11 — Authorization, audit & permissions

## Objectif

Étendre le modèle d’accès du Lot 1 aux capacités Inventory/Cash réellement disponibles.

---

## Étape 3.11.1 — Permissions Inventory

Ajouter uniquement :

```text
INVENTORY_READ
INVENTORY_INITIALIZE
INVENTORY_ADJUST
STOCK_MOVEMENT_READ
```

Ne pas encore ajouter si inutilisées :

```text
STOCK_TRANSFER_*
STOCK_COUNT_*
PURCHASE_*
```

### Commit proposé

```text
feat(access): add inventory permissions
```

---

## Étape 3.11.2 — Permissions Cash

Ajouter :

```text
CASH_REGISTER_CREATE
CASH_REGISTER_READ
CASH_REGISTER_UPDATE
CASH_REGISTER_MANAGE

CASH_SESSION_OPEN
CASH_SESSION_READ
CASH_SESSION_CLOSE

CASH_MOVEMENT_READ
CASH_IN_RECORD
CASH_OUT_RECORD
CASH_WITHDRAWAL_RECORD
```

Adapter les noms au catalogue existant sans multiplier les synonymes.

### Commit proposé

```text
feat(access): add cash management permissions
```

---

## Étape 3.11.3 — Rôles système

Exemple cible :

```text
ORGANIZATION_OWNER
→ toutes permissions Lot 3

STORE_MANAGER
→ Inventory read/adjust selon politique
→ Cash supervision selon politique

CASHIER
→ CashSession OPEN/CLOSE
→ Cash movement limité
→ Inventory read éventuellement

ACCOUNTANT
→ lecture Cash/Stock selon politique
```

Les Domain modules ne connaissent jamais ces noms de rôles.

### Commit proposé

```text
feat(access): grant inventory and cash permissions
```

---

## Étape 3.11.4 — Audit sensible

Auditer au minimum :

```text
STOCK_INITIALIZED
STOCK_ADJUSTED
CASH_REGISTER_ARCHIVED
CASH_SESSION_OPENED
CASH_SESSION_CLOSED
CASH_IN_RECORDED
CASH_OUT_RECORDED
CASH_WITHDRAWAL_RECORDED
```

Les business ledgers restent distincts du security audit.

`StockMovement` ne remplace pas `SecurityAuditEntry`.

`CashMovement` ne remplace pas `SecurityAuditEntry`.

### Commit proposé

```text
feat(audit): record inventory and cash operations
```

---

## Definition of Done — Epic 3.11

- permission catalog étendu ;
- scopes Store appliqués ;
- rôles mis à jour ;
- AuthorizationService utilisé ;
- audits sensibles ;
- aucun rôle codé dans Domain.

---
