# 19. Epic 3.13 — Inventory & Cash API

## Objectif

Exposer les capacités administratives et opérationnelles du Lot 3.

---

## Étape 3.13.1 — API Stock

Endpoints recommandés :

```text
GET  /api/stores/{storeId}/stocks
GET  /api/stores/{storeId}/stocks/{productId}

POST /api/stores/{storeId}/stocks/{productId}/initialize
POST /api/stores/{storeId}/stocks/{productId}/adjust
```

Initialisation payload :

```json
{
  "quantity": "100"
}
```

Ajustement :

```json
{
  "delta": "-3",
  "reason": "Damaged items found"
}
```

Les quantités sont des strings décimales dans JSON selon convention Decimal existante.

### Commit proposé

```text
feat(api): expose stock operations
```

---

## Étape 3.13.2 — API StockMovement

Lecture :

```text
GET /api/stores/{storeId}/stock-movements
GET /api/stores/{storeId}/stocks/{productId}/movements
```

Filtres utiles :

```text
productId
type
occurredFrom
occurredTo
```

Aucune API :

```text
PATCH StockMovement
DELETE StockMovement
```

### Commit proposé

```text
feat(api): expose stock movement history
```

---

## Étape 3.13.3 — API CashRegister

Endpoints :

```text
GET    /api/stores/{storeId}/cash-registers
POST   /api/stores/{storeId}/cash-registers
GET    /api/stores/{storeId}/cash-registers/{id}
PATCH  /api/stores/{storeId}/cash-registers/{id}

POST   /api/stores/{storeId}/cash-registers/{id}/activate
POST   /api/stores/{storeId}/cash-registers/{id}/deactivate
POST   /api/stores/{storeId}/cash-registers/{id}/archive
```

### Commit proposé

```text
feat(api): expose cash register management
```

---

## Étape 3.13.4 — API CashSession

Endpoints :

```text
POST /api/stores/{storeId}/cash-registers/{cashRegisterId}/sessions/open

GET  /api/cash-sessions/{id}
GET  /api/stores/{storeId}/cash-sessions

POST /api/cash-sessions/{id}/close
```

Open :

```json
{
  "openingBalance": {
    "amount": "50000",
    "currency": "XAF"
  }
}
```

Close :

```json
{
  "countedClosingBalance": {
    "amount": "73500",
    "currency": "XAF"
  }
}
```

### Commit proposé

```text
feat(api): expose cash session lifecycle
```

---

## Étape 3.13.5 — API CashMovement

Endpoints intentionnels :

```text
GET /api/cash-sessions/{id}/movements

POST /api/cash-sessions/{id}/cash-in
POST /api/cash-sessions/{id}/cash-out
POST /api/cash-sessions/{id}/withdrawals
```

Pas de :

```text
POST /api/cash-movements
```

générique permettant de choisir arbitrairement le type.

Pas de PATCH/DELETE.

### Commit proposé

```text
feat(api): expose cash movement operations
```

---

## Étape 3.13.6 — Contrat d’erreurs

Réutiliser :

```text
400 VALIDATION_ERROR
401 UNAUTHENTICATED
403 FORBIDDEN
404 NOT_FOUND
409 CONFLICT
422 DOMAIN_RULE_VIOLATION
```

Codes métier possibles :

```text
STOCK_NOT_INITIALIZED
STOCK_ALREADY_INITIALIZED
INSUFFICIENT_STOCK
PRODUCT_NOT_INVENTORY_TRACKED
INVALID_STOCK_ADJUSTMENT

CASH_REGISTER_INACTIVE
CASH_SESSION_ALREADY_OPEN
CASH_SESSION_NOT_OPEN
CASH_SESSION_ALREADY_CLOSED
CASH_CURRENCY_MISMATCH
```

### Commit proposé

```text
docs(api): document inventory and cash errors
```

---

## Étape 3.13.7 — OpenAPI

Documenter :

- Decimal/Quantity ;
- Money ;
- permissions ;
- tenant behavior ;
- scopes Store ;
- lifecycle ;
- immutabilité ;
- idempotency ;
- erreurs ;
- exemples.

### Commit proposé

```text
docs(api): document inventory and cash endpoints
```

---

## Definition of Done — Epic 3.13

- API Stock ;
- API StockMovement lecture ;
- API CashRegister ;
- API CashSession ;
- API CashMovement ;
- endpoints intentionnels ;
- pas de CRUD générique ledger ;
- OpenAPI complet ;
- DTO séparés Domain/Doctrine.

---
