# 15. Epic 3.9 — CashMovement ledger

## Objectif

Enregistrer toute entrée ou sortie d’espèces réelle pendant une session.

---

## Étape 3.9.1 — Ajouter `CashMovement`

Modèle :

```text
CashMovement
├── CashMovementId
├── OrganizationId
├── StoreId
├── CashSessionId
├── CashMovementType type
├── direction
├── Money amount
├── sourceReference?
├── reason?
├── performedBy
├── approvedBy?
└── occurredAt
```

Les types du Lot 3 doivent rester limités aux mouvements réellement utilisables.

Proposition minimale :

```text
CASH_IN
CASH_OUT
CASH_WITHDRAWAL
```

Les futurs types :

```text
SALE_PAYMENT
DEBT_PAYMENT
REFUND
```

sont consommés par les lots correspondants.

### Invariants

```text
amount > 0
```

```text
session.status = OPEN
```

```text
currency = store/session currency
```

```text
direction
= dérivée du type
```

### Commit proposé

```text
feat(cash): add immutable cash movement ledger
```

---

## Étape 3.9.2 — `RecordCashIn`

Command :

```text
RecordCashIn
├── cashSessionId
├── amount
└── reason
```

Préconditions :

- session OPEN ;
- permission ;
- store scope ;
- reason obligatoire selon politique.

Type :

```text
CASH_IN
```

### Commit proposé

```text
feat(cash): add manual cash in
```

---

## Étape 3.9.3 — `RecordCashOut`

Command :

```text
RecordCashOut
├── cashSessionId
├── amount
└── reason
```

Type :

```text
CASH_OUT
```

Permission explicite.

Si seuil d’approbation déjà décidé :

```text
approvedBy
```

doit être renseigné selon policy.

### Commit proposé

```text
feat(cash): add manual cash out
```

---

## Étape 3.9.4 — `CashWithdrawal`

Si distingué dans la baseline :

```text
CashWithdrawal
```

représente une sortie structurée distincte d’un simple `CASH_OUT`.

Ne pas fusionner les types si la distinction métier est utile.

### Commit proposé

```text
feat(cash): add cash withdrawal
```

---

## Étape 3.9.5 — Persistence append-only

Ajouter :

- mapping ;
- migration ;
- repository append-only ;
- RLS ;
- indexes ;
- aucune update/delete métier.

### Commit proposé

```text
feat(cash): persist immutable cash movements
```

---

## Étape 3.9.6 — Idempotence CashMovement

Toute intégration future doit utiliser une référence idempotente.

Préparer :

```text
sourceReference
```

pour :

```text
SALE_PAYMENT / SaleId
REFUND / RefundId
DEBT_PAYMENT / CustomerPaymentId
```

Pour les mouvements manuels API, utiliser la stratégie globale d’idempotency key lorsque retry possible.

### Commit proposé

```text
feat(cash): enforce cash movement idempotence
```

---

## Definition of Done — Epic 3.9

- ledger CashMovement opérationnel ;
- append-only ;
- types manuels ;
- direction cohérente ;
- session OPEN obligatoire ;
- expected balance impacté correctement ;
- idempotence préparée ;
- audit ;
- persistence PostgreSQL ;
- RLS.

---
