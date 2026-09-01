# 6. Epic 1.2 — Store foundation

## Objectif

Permettre à une `Organization` d’exploiter plusieurs boutiques.

---

## Étape 1.2.1 — Ajouter l’aggregate `Store`

Modèle :

```text
Store
├── StoreId id
├── OrganizationId organizationId
├── StoreCode code
├── name
├── status
├── address?
├── timeZone
├── currency
├── locale
├── lifecycle actors and timestamps
└── Version version
```

Contrainte :

```text
UNIQUE (organization_id, store_code)
```

Invariants :

- `organizationId` est immuable ;
- `StoreCode` devient immuable après création ;
- pour le MVP :

```text
Store.currency == Organization.defaultCurrency
```

- les timestamps sont enregistrés en UTC ;
- la journée métier utilise `Store.timeZone` ;
- un store `CLOSED` ne redevient pas `ACTIVE` dans le workflow normal.

### Commit proposé

```text
feat(store): add store aggregate
```

---

## Étape 1.2.2 — Ajouter les value objects Store

Créer selon besoin :

```text
StoreCode
StoreName
StoreAddress
```

Ne pas dupliquer `Currency`, `TimeZone`, `Locale` si les primitives existantes conviennent.

### Commit proposé

```text
feat(store): add store value objects
```

---

## Étape 1.2.3 — Ajouter `StoreRepository`

Le repository doit toujours respecter :

```text
OrganizationId + StoreId
```

Une boutique d’une autre organization ne doit jamais être retournée par un lookup tenant-scoped.

### Commit proposé

```text
feat(store): add store repository contract
```

---

## Étape 1.2.4 — Persistence Doctrine de Store

Ajouter :

- mapping ;
- repository ;
- migration ;
- contrainte unique `(organization_id, store_code)` ;
- activation et policy RLS tenant-safe conformément à l'ADR-0017 ;
- tests PostgreSQL.

### Commit proposé

```text
feat(store): persist store aggregate
```

---

## Étape 1.2.5 — Use cases Store

Créer :

```text
CreateStore
UpdateStore
SuspendStore
ReactivateStore
RequestStoreClosure
CancelStoreClosure
```

Domain events :

```text
StoreCreated
StoreUpdated
StoreSuspended
StoreReactivated
StoreClosureRequested
StoreClosureCancelled
```

### Commits proposés

```text
feat(store): add create store use case
feat(store): add store update use case
feat(store): add store suspension lifecycle
feat(store): add store closure request
```

---

## Étape 1.2.6 — Introduire `StoreClosure`

Créer le process manager :

```text
StoreClosure
├── StoreClosureId
├── OrganizationId
├── StoreId
├── status
├── reason
├── blockers
├── requestedBy
├── requestedAt
├── completedBy?
├── completedAt?
└── Version
```

Statuts :

```text
REQUESTED
IN_PROGRESS
READY
COMPLETED
CANCELLED
```

La fermeture définitive devra pouvoir vérifier plus tard :

```text
CashSession OPEN
StockTransfer in transit
StockCount OPEN / FINALIZING
Purchasing documents open
Stock quantity remaining
```

Ne pas importer les repositories de ces futurs modules.

Préparer un contrat public du type :

```text
StoreClosureBlockerProvider
```

ou une abstraction équivalente dans la couche appropriée.

Le Lot 1 doit pouvoir fonctionner lorsque les modules futurs ne sont pas encore présents.

### Commit proposé

```text
feat(store): add store closure process manager
```

---

## Definition of Done — Epic 1.2

- plusieurs stores par organization ;
- unicité du `StoreCode` dans le tenant ;
- lifecycle testé ;
- recherche cross-tenant impossible ;
- persistence réelle ;
- process manager de fermeture prêt à accueillir les blockers futurs.

---
