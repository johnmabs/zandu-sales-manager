# 15. Epic 4.8 — CompleteSale

## Command

```text
CompleteSale
├── saleId
├── cashSessionId
├── payment
│   └── amount
└── tenderedAmount?
```

L’organisation, le store et l’acteur sont résolus côté serveur.

## Préconditions

```text
Organization ACTIVE
Store ACTIVE
actor autorisé
Sale tenant-safe
Sale completable
Sale non vide
CashSession OPEN
CashSession du même Store
pricing accepté
```

## Paiement

Recommandation du premier vertical slice :

```text
1 Payment CASH
couvrant 100 % du total
```

Le cadrage global permet des split payments, mais ils peuvent rester pour une extension ultérieure afin de ne pas élargir M2.

## Monnaie

Si :

```text
tenderedAmount
```

alors :

```text
tenderedAmount >= total
changeAmount = tenderedAmount - total
```

Le Payment porte le montant de la vente, pas le montant remis avant rendu de monnaie.

## Workflow

```text
BEGIN

load Sale
validate Sale
validate pricing
validate CashSession

create Payment

consume Inventory tracked lines
append StockMovement SALE

confirm Payment
append CashMovement SALE_PAYMENT

Sale.complete(...)
persist Sale

append required audit
append outbox events

COMMIT
```

## BusinessDate

Calculée avec :

```text
Store.timeZone
```

et distincte des timestamps UTC.

## Events

```text
SaleCompleted
PaymentConfirmed
```

Commit :

```text
feat(sales): add CompleteSale cash workflow
```

### DoD Epic 4.8

- coordinated transaction ;
- Payment + Stock + Cash + Sale + Outbox atomiques ;
- BusinessDate ;
- zéro effet partiel.

---
